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
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'role_id' => ['nullable', 'integer', Rule::exists(ConstantHelper::TABLE_ROLES, 'id')],
            'status' => ['nullable', Rule::enum(UserStatus::class)],
        ]);

        $query = User::query()
            ->with(['department', 'activeRoles'])
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->when($filters['role_id'] ?? null, function ($query, $roleId): void {
                $query->whereHas('activeRoles', fn ($roleQuery) => $roleQuery->where('roles.id', $roleId));
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('full_name');

        $users = $query->paginate(15)->withQueryString();
        $roles = Role::query()->orderBy('name')->get(['id', 'name']);
        $statuses = UserStatus::cases();

        return view('users.index', compact('users', 'roles', 'statuses', 'filters'));
    }

    public function create(): View
    {
        abort_unless($this->canCreateAccounts(auth()->user()), 403);

        return $this->formView(new User, 'users.form');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canCreateAccounts($request->user()), 403);

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
            abort_unless($this->canAssignRoles(auth()->user()), 403);
        }

        return $this->formView($user, 'users.form', $selectedRoleIds);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $canAssignRoles = $this->canAssignRoles($request->user());

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
            'canAssignRoles' => $this->canAssignRoles(auth()->user()),
        ]);
    }

    private function canCreateAccounts(User $user): bool
    {
        return $user->hasRole(ConstantHelper::ROLE_ADMIN)
            || ($user->hasPermission(ConstantHelper::PERM_USERS_CREATE)
                && $user->hasPermission(ConstantHelper::PERM_USERS_ASSIGN_ROLES));
    }

    private function canAssignRoles(User $user): bool
    {
        return $user->hasRole(ConstantHelper::ROLE_ADMIN)
            || $user->hasPermission(ConstantHelper::PERM_USERS_ASSIGN_ROLES);
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
