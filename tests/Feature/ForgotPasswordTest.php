<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Mail\Auth\ForgotPasswordOtpMail;
use App\Models\User;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const GENERIC_MESSAGE = 'Nếu email hợp lệ, hướng dẫn khôi phục mật khẩu sẽ được gửi đến địa chỉ email của bạn.';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ReferenceSeeder::class);
        Mail::fake();
    }

    public function test_valid_email_can_reset_password_with_one_time_otp(): void
    {
        $user = $this->createUser();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.otp.form'))
            ->assertSessionHas('status', self::GENERIC_MESSAGE);

        $otp = null;
        Mail::assertSent(ForgotPasswordOtpMail::class, function (ForgotPasswordOtpMail $mail) use (&$otp): bool {
            $otp = $mail->otp;

            return true;
        });
        $this->assertIsString($otp);
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $otp);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);

        $this->post(route('password.otp.verify'), ['otp' => $otp])
            ->assertRedirect(route('password.reset.form'));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        $this->from(route('password.reset.form'))
            ->put(route('password.reset'), [
                'password' => 'BrandNewPassword123!',
                'password_confirmation' => 'BrandNewPassword123!',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Mật khẩu đã được đặt lại thành công. Vui lòng đăng nhập lại.');

        $this->assertTrue(Hash::check(
            'BrandNewPassword123!',
            $user->fresh()->password,
        ));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertGuest();
    }

    public function test_unknown_or_inactive_email_gets_the_same_response_without_sending_mail(): void
    {
        $inactiveUser = $this->createUser([
            'username' => 'inactive-otp-user',
            'email' => 'inactive@example.com',
            'status' => UserStatus::INACTIVE,
        ]);

        foreach (['missing@example.com', $inactiveUser->email] as $email) {
            $this->post(route('password.email'), ['email' => $email])
                ->assertRedirect(route('password.otp.form'))
                ->assertSessionHas('status', self::GENERIC_MESSAGE);
        }

        Mail::assertNothingSent();
    }

    public function test_expired_otp_cannot_be_verified(): void
    {
        $user = $this->createUser();
        $this->post(route('password.email'), ['email' => $user->email]);

        $otp = null;
        Mail::assertSent(ForgotPasswordOtpMail::class, function (ForgotPasswordOtpMail $mail) use (&$otp): bool {
            $otp = $mail->otp;

            return true;
        });

        $this->travel(6)->minutes();

        $this->withSession(['password_reset_email' => $user->email])
            ->post(route('password.otp.verify'), ['otp' => $otp])
            ->assertSessionHasErrors('otp');
    }

    public function test_otp_verification_is_limited_to_five_incorrect_attempts(): void
    {
        $user = $this->createUser();
        $this->post(route('password.email'), ['email' => $user->email]);

        $otp = null;
        Mail::assertSent(ForgotPasswordOtpMail::class, function (ForgotPasswordOtpMail $mail) use (&$otp): bool {
            $otp = $mail->otp;

            return true;
        });

        $this->withSession(['password_reset_email' => $user->email]);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('password.otp.verify'), ['otp' => $otp === '000000' ? '000001' : '000000'])
                ->assertSessionHasErrors('otp');
        }

        $this->post(route('password.otp.verify'), ['otp' => $otp])
            ->assertSessionHasErrors('otp');
    }

    public function test_otp_cannot_be_resent_before_sixty_seconds(): void
    {
        $user = $this->createUser();

        $this->post(route('password.email'), ['email' => $user->email]);
        $this->post(route('password.otp.resend'), ['email' => $user->email]);

        Mail::assertSentCount(1);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createUser(array $attributes = []): User
    {
        return User::query()->create(array_merge([
            'username' => 'otp-user',
            'full_name' => 'OTP User',
            'email' => 'otp-user@example.com',
            'password' => Hash::make('A-placeholder-password-that-is-not-used'),
            'status' => UserStatus::ACTIVE,
        ], $attributes));
    }
}
