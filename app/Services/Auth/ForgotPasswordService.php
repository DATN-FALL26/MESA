<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\UserStatus;
use App\Mail\Auth\ForgotPasswordOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

class ForgotPasswordService
{
    private const OTP_TTL_SECONDS = 300;

    private const RESEND_TTL_SECONDS = 60;

    private const MAX_OTP_ATTEMPTS = 5;

    private const MAX_SENDS_PER_IP = 10;

    private const SEND_IP_TTL_SECONDS = 3600;

    private const RESET_TICKET_TTL_SECONDS = 600;

    public function sendOtp(string $email, string $ipAddress): void
    {
        $email = mb_strtolower(trim($email));
        $emailKey = $this->key('email', $email);

        $ipRateLimitKey = $this->key('send-ip', $ipAddress);
        if (RateLimiter::tooManyAttempts($ipRateLimitKey, self::MAX_SENDS_PER_IP)) {
            return;
        }
        RateLimiter::hit($ipRateLimitKey, self::SEND_IP_TTL_SECONDS);

        if (! Cache::add('password-reset:resend:'.$emailKey, true, self::RESEND_TTL_SECONDS)) {
            return;
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('status', UserStatus::ACTIVE->value)
            ->first();

        if (! $user || ! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpHash = $this->otpHash($email, $otp);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => $otpHash, 'created_at' => now()],
        );

        try {
            Mail::to($user->email)->send(new ForgotPasswordOtpMail(
                otp: $otp,
                recipientName: $user->full_name,
            ));
        } catch (TransportExceptionInterface $exception) {
            DB::table('password_reset_tokens')
                ->where('email', $user->email)
                ->where('token', $otpHash)
                ->delete();

            Log::warning('Password reset OTP email delivery failed.', [
                'exception' => $exception::class,
            ]);
        }
    }

    public function verifyOtp(string $email, string $otp): ?string
    {
        $email = mb_strtolower(trim($email));
        $emailKey = $this->key('email', $email);
        $attemptsKey = 'password-reset:otp-attempts:'.$emailKey;

        if (RateLimiter::tooManyAttempts($attemptsKey, self::MAX_OTP_ATTEMPTS)) {
            return null;
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('status', UserStatus::ACTIVE->value)
            ->first();

        $resetRecord = $user
            ? DB::table('password_reset_tokens')->where('email', $user->email)->first()
            : null;

        $valid = $resetRecord
            && $resetRecord->created_at
            && now()->diffInSeconds($resetRecord->created_at) <= self::OTP_TTL_SECONDS
            && hash_equals((string) $resetRecord->token, $this->otpHash($email, $otp));

        if (! $valid) {
            RateLimiter::hit($attemptsKey, self::OTP_TTL_SECONDS);

            return null;
        }

        $deleted = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->where('token', $resetRecord->token)
            ->delete();

        if ($deleted !== 1) {
            return null;
        }

        $ticket = Str::random(64);
        $ticketKey = $this->ticketKey($ticket);

        if (! Cache::add($ticketKey, (int) $user->getKey(), self::RESET_TICKET_TTL_SECONDS)) {
            return null;
        }

        RateLimiter::clear($attemptsKey);

        return $ticket;
    }

    public function resetPassword(string $ticket, string $password): bool
    {
        $ticketKey = $this->ticketKey($ticket);
        $userId = Cache::get($ticketKey);
        $consumedKey = 'password-reset:consumed:'.$this->key('ticket', $ticket);

        if (! is_numeric($userId) || ! Cache::add($consumedKey, true, self::RESET_TICKET_TTL_SECONDS)) {
            return false;
        }

        try {
            $userWasReset = DB::transaction(function () use ($userId, $password): bool {
                $user = User::query()
                    ->whereKey((int) $userId)
                    ->where('status', UserStatus::ACTIVE->value)
                    ->first();

                if (! $user) {
                    return false;
                }

                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                return true;
            });
        } catch (Throwable $exception) {
            Cache::forget($consumedKey);
            throw $exception;
        }

        if (! $userWasReset) {
            Cache::forget($consumedKey);

            return false;
        }

        Cache::forget($ticketKey);

        return true;
    }

    private function otpHash(string $email, string $otp): string
    {
        return hash_hmac('sha256', $email.'|'.$otp, (string) config('app.key'));
    }

    private function key(string $purpose, string $value): string
    {
        return hash_hmac('sha256', $purpose.'|'.$value, (string) config('app.key'));
    }

    private function ticketKey(string $ticket): string
    {
        return 'password-reset:ticket:'.$this->key('ticket', $ticket);
    }
}
