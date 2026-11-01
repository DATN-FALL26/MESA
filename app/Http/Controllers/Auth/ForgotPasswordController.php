<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Services\Auth\ForgotPasswordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    private const GENERIC_MESSAGE = 'Nếu email hợp lệ, hướng dẫn khôi phục mật khẩu sẽ được gửi đến địa chỉ email của bạn.';

    public function __construct(
        private readonly ForgotPasswordService $forgotPasswordService,
    ) {}

    public function showRequestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(SendOtpRequest $request): RedirectResponse
    {
        $email = mb_strtolower(trim($request->validated('email')));

        $this->forgotPasswordService->sendOtp($email, (string) $request->ip());

        $request->session()->forget(['password_reset_ticket', 'password_reset_email']);
        $request->session()->put('password_reset_email', $email);

        return redirect()
            ->route('password.otp.form')
            ->with('status', self::GENERIC_MESSAGE);
    }

    public function showVerifyForm(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('password_reset_email')) {
            return redirect()->route('password.request');
        }

        return view('auth.verify-otp', [
            'email' => $request->session()->get('password_reset_email'),
            'otpExpiresAt' => $this->forgotPasswordService->otpExpiresAt(
                (string) $request->session()->get('password_reset_email'),
            ),
        ]);
    }

    public function verifyOtp(VerifyOtpRequest $request): RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');

        if (! is_string($email)) {
            return redirect()->route('password.request');
        }

        $ticket = $this->forgotPasswordService->verifyOtp($email, $request->validated('otp'));

        if ($ticket === null) {
            return back()->withErrors([
                'otp' => 'Mã OTP không hợp lệ, đã hết hạn hoặc đã vượt quá số lần thử.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->forget('password_reset_email');
        $request->session()->put('password_reset_ticket', $ticket);

        return redirect()->route('password.reset.form');
    }

    public function resendOtp(SendOtpRequest $request): RedirectResponse
    {
        $email = mb_strtolower(trim($request->validated('email')));

        $this->forgotPasswordService->sendOtp($email, (string) $request->ip());

        $request->session()->forget('password_reset_ticket');
        $request->session()->put('password_reset_email', $email);

        return redirect()
            ->route('password.otp.form')
            ->with('status', self::GENERIC_MESSAGE);
    }

    public function showResetForm(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('password_reset_ticket')) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password');
    }

    public function showResetResult(Request $request): View|RedirectResponse
    {
        $result = $request->session()->get('password_reset_result');

        if (! is_array($result)
            || ! is_bool($result['success'] ?? null)
            || ! is_string($result['message'] ?? null)
            || ! is_string($result['redirectUrl'] ?? null)
            || ! is_string($result['destination'] ?? null)) {
            return redirect()->route('password.request');
        }

        return view('auth.password-reset-result', $result);
    }

    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $ticket = $request->session()->get('password_reset_ticket');

        if (! is_string($ticket)
            || ! $this->forgotPasswordService->resetPassword($ticket, $request->validated('password'))) {
            $request->session()->forget(['password_reset_ticket', 'password_reset_email']);

            return redirect()->route('password.reset.result')->with('password_reset_result', [
                'success' => false,
                'message' => 'Không thể cập nhật mật khẩu. Yêu cầu khôi phục đã hết hạn; vui lòng thực hiện lại.',
                'redirectUrl' => route('password.request'),
                'destination' => 'trang quên mật khẩu',
            ]);
        }

        $request->session()->forget(['password_reset_ticket', 'password_reset_email']);

        return redirect()->route('password.reset.result')->with('password_reset_result', [
            'success' => true,
            'message' => 'Cập nhật mật khẩu thành công. Vui lòng đăng nhập bằng mật khẩu mới.',
            'redirectUrl' => route('login'),
            'destination' => 'trang đăng nhập',
        ]);
    }
}
