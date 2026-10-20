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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->with(['department', 'activeRoles'])
            ->orderBy('full_name')
            ->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->hasPermission(ConstantHelper::PERM_USERS_ASSIGN_ROLES), 403);

        return $this->formView(new User, 'users.form');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission(ConstantHelper::PERM_USERS_ASSIGN_ROLES), 403);

        $validated = $this->validatedUser($request);

        DB::transaction(function () use ($validated, $request): void {
            $user = User::query()->create([
                'username' => $validated['username'],
                'employee_code' => $validated['employee_code'] ?? null,
                'full_name' => $validated['full_name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'password' => Hash::make($validated['password']),
                'status' => UserStatus::from($validated['status']),
            ]);

            if (array_key_exists('role_ids', $validated)) {
                $this->syncRoles($user, $validated['role_ids'], (int) $request->user()->getKey());
            }
        });

        return redirect()->route('users.index')->with('success', 'Đã tạo tài khoản.');
    }

    public function edit(User $user): View
    {
        $selectedRoleIds = $user->activeRoles()->pluck('roles.id')->all();

        if ($selectedRoleIds === []) {
            abort_unless(auth()->user()->hasPermission(ConstantHelper::PERM_USERS_ASSIGN_ROLES), 403);
        }

        return $this->formView($user, 'users.form', $selectedRoleIds);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $canAssignRoles = $request->user()->hasPermission(ConstantHelper::PERM_USERS_ASSIGN_ROLES);

        if (! $canAssignRoles && ! $user->activeRoles()->exists()) {
            abort(403);
        }

        $validated = $this->validatedUser($request, $user);

        if (! array_key_exists('role_ids', $validated) && ! $user->activeRoles()->exists()) {
            return back()->withErrors(['role_ids' => 'Phải gán ít nhất một vai trò cho tài khoản.']);
        }

        if ($request->user()->is($user) && $validated['status'] !== UserStatus::ACTIVE->value) {
            return back()->withErrors(['status' => 'Không thể tự khóa hoặc ngừng hoạt động tài khoản đang đăng nhập.']);
        }

        DB::transaction(function () use ($validated, $request, $user): void {
            $user->fill([
                'username' => $validated['username'],
                'employee_code' => $validated['employee_code'] ?? null,
                'full_name' => $validated['full_name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'status' => UserStatus::from($validated['status']),
            ]);

            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            if (array_key_exists('role_ids', $validated)) {
                $this->syncRoles($user, $validated['role_ids'], (int) $request->user()->getKey());
            }
        });

        return redirect()->route('users.index')->with('success', 'Đã cập nhật tài khoản.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->withErrors(['user' => 'Không thể xóa tài khoản đang đăng nhập.']);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Đã xóa tài khoản.');
    }

    /**
     * @param  list<int>|null  $selectedRoleIds
     */
    private function formView(User $user, string $view, ?array $selectedRoleIds = null): View
    {
        $roles = Role::query()->orderBy('name')->get();
        $departments = DB::table(ConstantHelper::TABLE_DEPARTMENTS)
            ->where(fn ($query) => $query
                ->where('is_active', true)
                ->orWhere('id', $user->department_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view($view, [
            'user' => $user,
            'roles' => $roles,
            'departments' => $departments,
            'statuses' => UserStatus::cases(),
            'selectedRoleIds' => $selectedRoleIds ?? [],
            'canAssignRoles' => auth()->user()->hasPermission(ConstantHelper::PERM_USERS_ASSIGN_ROLES),
        ]);
    }

    /**
     * @return array{
     *     username: string,
     *     employee_code?: string|null,
     *     full_name: string,
     *     email?: string|null,
     *     phone?: string|null,
     *     department_id?: int|null,
     *     status: string,
     *     password?: string|null,
     *     role_ids?: list<int>
     * }
     */
    private function validatedUser(Request $request, ?User $user = null): array
    {
        if ($request->exists('role_ids') && ! $request->boolean('assign_roles')) {
            abort(403);
        }

        $validated = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', Rule::unique(User::class, 'username')->ignore($user?->getKey())],
            'employee_code' => ['nullable', 'string', 'max:30', Rule::unique(User::class, 'employee_code')->ignore($user?->getKey())],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique(User::class, 'email')->ignore($user?->getKey())],
            'phone' => ['nullable', 'string', 'max:20'],
            'department_id' => ['nullable', 'integer', Rule::exists(ConstantHelper::TABLE_DEPARTMENTS, 'id')],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'password' => [$user === null ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
            'role_ids' => [$request->boolean('assign_roles') ? 'required' : 'sometimes', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'distinct', Rule::exists(ConstantHelper::TABLE_ROLES, 'id')],
            'assign_roles' => [$user === null ? 'required' : 'sometimes', 'accepted'],
        ]);

        if ($request->boolean('assign_roles')) {
            $validated['role_ids'] = array_map('intval', $validated['role_ids'] ?? []);
        }

        unset($validated['assign_roles']);

        return $validated;
    }

    /**
     * @param  list<int>  $roleIds
     */
    private function syncRoles(User $user, array $roleIds, int $grantedBy): void
    {
        $roleIds = array_values(array_unique(array_map('intval', $roleIds)));
        $now = now();
        $revokedAt = $now->copy()->subSecond();

        $activeAssignments = $user->userRoles()
            ->where('valid_from', '<=', $now)
            ->where(fn ($query) => $query
                ->whereNull('valid_to')
                ->orWhere('valid_to', '>=', $now))
            ->get();

        $activeRoleIds = $activeAssignments->pluck('role_id')->map(fn ($roleId): int => (int) $roleId)->unique()->all();
        $roleIdsToRevoke = array_values(array_diff($activeRoleIds, $roleIds));

        if ($roleIdsToRevoke !== []) {
            $user->userRoles()
                ->whereIn('role_id', $roleIdsToRevoke)
                ->where('valid_from', '<=', $now)
                ->where(fn ($query) => $query
                    ->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', $now))
                ->update(['valid_to' => $revokedAt, 'updated_at' => $now]);
        }

        foreach (array_diff($roleIds, $activeRoleIds) as $roleId) {
            $assignment = UserRole::query()
                ->where('user_id', $user->getKey())
                ->where('role_id', $roleId)
                ->where('scope_type', 'ALL')
                ->whereNull('scope_id')
                ->first();

            if ($assignment) {
                $assignment->forceFill([
                    'valid_from' => $now,
                    'valid_to' => null,
                    'granted_by' => $grantedBy,
                ])->save();

                continue;
            }

            UserRole::query()->create([
                'user_id' => $user->getKey(),
                'role_id' => $roleId,
                'scope_type' => 'ALL',
                'scope_id' => null,
                'valid_from' => $now,
                'valid_to' => null,
                'granted_by' => $grantedBy,
            ]);
        }
    }
}
