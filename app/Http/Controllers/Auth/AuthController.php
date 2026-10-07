<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'alpha_dash',
                Rule::unique(User::class, 'username'),
            ],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique(User::class, 'email')],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'username.alpha_dash' => 'Tên đăng nhập chỉ được chứa chữ cái, số, dấu gạch dưới hoặc dấu gạch ngang.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $customerRole = Role::query()
                ->where('code', ConstantHelper::ROLE_CUSTOMER)
                ->first();

            if ($customerRole === null) {
                throw new \RuntimeException('Default customer role is missing. Run the ReferenceSeeder first.');
            }

            $requiredPermissions = [
                ConstantHelper::PERM_PROFILE_VIEW,
                ConstantHelper::PERM_PROFILE_UPDATE,
                ConstantHelper::PERM_DASHBOARD_VIEW,
            ];

            if ($customerRole->permissions()->whereIn('code', $requiredPermissions)->count() !== count($requiredPermissions)) {
                throw new \RuntimeException('Default customer permissions are missing. Run the ReferenceSeeder first.');
            }

            $user = User::query()->create([
                'username' => $validated['username'],
                'full_name' => $validated['full_name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'status' => UserStatus::ACTIVE,
            ]);

            UserRole::query()->create([
                'user_id' => $user->getKey(),
                'role_id' => $customerRole->getKey(),
                'scope_type' => 'ALL',
                'scope_id' => null,
                'valid_from' => now(),
                'granted_by' => null,
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with(
            'success',
            'Tài khoản khách hàng đã được tạo với vai trò Khách hàng.'
        );
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
