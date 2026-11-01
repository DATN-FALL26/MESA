<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($validated['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([
            $field => $validated['login'],
            'password' => $validated['password'],
            'status' => UserStatus::ACTIVE->value,
        ], $request->boolean('remember'))) {
            return back()
                ->withErrors([
                    'login' => 'Tên đăng nhập hoặc mật khẩu không chính xác.',
                ])
                ->onlyInput('login');
        }

        if (! Auth::user()?->activeRoles()->exists()) {
            Auth::logout();

            return back()
                ->withErrors([
                    'login' => 'Tài khoản không có vai trò nhân viên đang hoạt động.',
                ])
                ->onlyInput('login');
        }

        $request->session()->regenerate();
        Auth::user()?->forceFill(['last_login_at' => now()])->save();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
