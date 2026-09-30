<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();

    $request->session()->regenerate();

    $user = $request->user();

    // Điều hướng thông minh dựa vào Role của người dùng
    if ($user->role === 'ceo') {
        return redirect()->intended(route('ceo.dashboard'));
    } elseif ($user->role === 'manager') {
        return redirect()->intended(route('manager.dashboard'));
    } elseif ($user->role === 'chef') {
        return redirect()->intended(route('kitchen.orders'));
    } elseif ($user->role === 'warehouse') {
        return redirect()->intended(route('warehouse.inventory'));
    } elseif ($user->role === 'staff') {
        return redirect()->intended(route('staff.tables'));
    } elseif ($user->role === 'cashier') {
        return redirect()->intended(route('dashboard')); // Sau này có route riêng cho cashier thì thay vào đây
    }

    // Mặc định cho Khách hàng (customer) hoặc tài khoản khác về dashboard chung
    return redirect()->intended(route('dashboard'));
}

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
