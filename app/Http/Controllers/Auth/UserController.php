<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Enums\BranchStatus;
use App\Enums\ScopeType;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Auth\BranchAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private BranchAccessService $branchAccess) {}

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
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $users = $query->paginate(15)->withQueryString();
        $roles = Role::query()->orderBy('name')->get(['id', 'name']);
        $statuses = UserStatus::cases();

        return view('users.index', compact('users', 'roles', 'statuses', 'filters'));
    }

    public function create(): View
    {
        abort_unless($this->canCreateAccounts(auth()->user()), 403);

        return $this->formView(new User, 'users.form', null, $this->nextEmployeeCode());
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canCreateAccounts($request->user()), 403);

        $validated = $this->validatedUser($request);

        DB::transaction(function () use ($validated, $request): void {
            $user = User::query()->create([
                'username' => $validated['username'],
                'employee_code' => $this->nextEmployeeCode(),
                'full_name' => $validated['full_name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'password' => Hash::make($validated['password']),
                'status' => UserStatus::from($validated['status']),
            ]);

            if (array_key_exists('role_ids', $validated)) {
                $this->syncRoles(
                    $user,
                    $validated['role_assignments'],
                    (int) $request->user()->getKey()
                );
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
                $this->syncRoles(
                    $user,
                    $validated['role_assignments'],
                    (int) $request->user()->getKey()
                );
            }
        });

        return redirect()->route('users.index')->with('success', 'Đã cập nhật tài khoản.');
    }

    /**
     * @param  list<int>|null  $selectedRoleIds
     */
    private function formView(
        User $user,
        string $view,
        ?array $selectedRoleIds = null,
        ?string $employeeCode = null
    ): View {
        $roles = Role::query()->orderBy('name')->get();
        $assignableBranchIds = $this->branchAccess->accessibleBranchIds(
            auth()->user(),
            [ConstantHelper::PERM_USERS_ASSIGN_ROLES]
        );
        $branches = Branch::query()
            ->where('status', BranchStatus::ACTIVE)
            ->when(is_array($assignableBranchIds), fn ($query) => $query->whereIn('id', $assignableBranchIds))
            ->orderBy('name')
            ->get(['id', 'name']);
        $activeAssignments = $user->userRoles()
            ->where('valid_from', '<=', now())
            ->where(fn ($query) => $query->whereNull('valid_to')->orWhere('valid_to', '>=', now()))
            ->get(['role_id', 'scope_type', 'scope_id'])
            ->groupBy('role_id')
            ->map(function ($assignments): array {
                return [
                    'all' => $assignments->contains(
                        fn (UserRole $assignment): bool => $assignment->scope_type === ScopeType::ALL
                    ),
                    'branches' => $assignments
                        ->where('scope_type', ScopeType::BRANCH)
                        ->pluck('scope_id')
                        ->map(fn (mixed $branchId): int => (int) $branchId)
                        ->values()
                        ->all(),
                ];
            })
            ->all();
        $departments = DB::table(ConstantHelper::TABLE_DEPARTMENTS)
            ->where(fn ($query) => $query
                ->where('is_active', true)
                ->orWhere('id', $user->department_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view($view, [
            'user' => $user,
            'roles' => $roles,
            'branches' => $branches,
            'departments' => $departments,
            'statuses' => UserStatus::cases(),
            'selectedRoleIds' => $selectedRoleIds ?? [],
            'selectedRoleAssignments' => $activeAssignments,
            'canAssignRoles' => $this->canAssignRoles(auth()->user()),
            'employeeCode' => $employeeCode,
        ]);
    }

    private function nextEmployeeCode(): string
    {
        $highestNumber = User::withTrashed()
            ->where('employee_code', 'like', 'NV%')
            ->pluck('employee_code')
            ->reduce(function (int $highestNumber, ?string $employeeCode): int {
                if (preg_match('/^NV(\d+)$/i', $employeeCode ?? '', $matches) !== 1) {
                    return $highestNumber;
                }

                return max($highestNumber, (int) $matches[1]);
            }, 0);

        return 'NV'.str_pad((string) ($highestNumber + 1), 4, '0', STR_PAD_LEFT);
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
     *     role_ids?: list<int>,
     *     role_assignments?: list<array{role_id: int, scope_type: ScopeType, scope_id: int|null}>
     * }
     */
    private function validatedUser(Request $request, ?User $user = null): array
    {
        if (($request->exists('role_ids') || $request->exists('role_branch_ids') || $request->exists('role_all'))
            && ! $request->boolean('assign_roles')) {
            abort(403);
        }

        $rules = [
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', Rule::unique(User::class, 'username')->ignore($user?->getKey())],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique(User::class, 'email')->ignore($user?->getKey())],
            'phone' => ['nullable', 'string', 'max:20'],
            'department_id' => ['nullable', 'integer', Rule::exists(ConstantHelper::TABLE_DEPARTMENTS, 'id')],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'password' => [$user === null ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
            'role_ids' => [$request->boolean('assign_roles') ? 'required' : 'sometimes', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'distinct', Rule::exists(ConstantHelper::TABLE_ROLES, 'id')],
            'role_branch_ids' => ['sometimes', 'array'],
            'role_branch_ids.*' => ['array'],
            'role_branch_ids.*.*' => [
                'integer',
                'distinct',
                Rule::exists(ConstantHelper::TABLE_BRANCHES, 'id')->where('status', BranchStatus::ACTIVE->value),
            ],
            'role_all' => ['sometimes', 'array'],
            'role_all.*' => ['boolean'],
            'assign_roles' => [$user === null ? 'required' : 'sometimes', 'accepted'],
        ];

        if ($user !== null) {
            $rules['employee_code'] = [
                'nullable',
                'string',
                'max:30',
                Rule::unique(User::class, 'employee_code')->ignore($user->getKey()),
            ];
        }

        $validated = $request->validate($rules);

        if ($request->boolean('assign_roles')) {
            $validated['role_ids'] = array_map('intval', $validated['role_ids'] ?? []);

            $assignableBranchIds = $this->branchAccess->accessibleBranchIds(
                $request->user(),
                [ConstantHelper::PERM_USERS_ASSIGN_ROLES]
            );
            $adminRoleId = (int) Role::query()
                ->where('code', ConstantHelper::ROLE_ADMIN)
                ->value('id');
            $roleAssignments = [];

            foreach ($validated['role_ids'] as $roleId) {
                $branchIds = array_values(array_unique(array_map(
                    'intval',
                    $validated['role_branch_ids'][$roleId] ?? []
                )));
                $isAllScope = filter_var(
                    $validated['role_all'][$roleId] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                );

                if ($roleId === $adminRoleId) {
                    if (count($validated['role_ids']) !== 1 || ! $isAllScope || $branchIds !== []) {
                        throw ValidationException::withMessages([
                            'role_ids' => 'Vai trò quản trị hệ thống phải được gán riêng với phạm vi toàn chuỗi.',
                        ]);
                    }

                    $roleAssignments[] = [
                        'role_id' => $roleId,
                        'scope_type' => ScopeType::ALL,
                        'scope_id' => null,
                    ];

                    continue;
                }

                if ($isAllScope || $branchIds === []) {
                    throw ValidationException::withMessages([
                        'role_branch_ids' => 'Mỗi vai trò nghiệp vụ phải được gán ít nhất một chi nhánh cụ thể.',
                    ]);
                }

                foreach ($branchIds as $branchId) {
                    abort_unless(
                        $assignableBranchIds === null || in_array($branchId, $assignableBranchIds, true),
                        403,
                        'Bạn không có quyền gán người dùng vào chi nhánh này.'
                    );

                    $roleAssignments[] = [
                        'role_id' => $roleId,
                        'scope_type' => ScopeType::BRANCH,
                        'scope_id' => $branchId,
                    ];
                }
            }

            $validated['role_assignments'] = $roleAssignments;
        }

        unset($validated['assign_roles']);

        return $validated;
    }

    /**
     * @param  list<array{role_id: int, scope_type: ScopeType, scope_id: int|null}>  $desiredAssignments
     */
    private function syncRoles(User $user, array $desiredAssignments, int $grantedBy): void
    {
        $now = now();
        $revokedAt = $now->copy()->subSecond();
        $activeAssignments = $user->userRoles()
            ->where('valid_from', '<=', $now)
            ->where(fn ($query) => $query
                ->whereNull('valid_to')
                ->orWhere('valid_to', '>=', $now))
            ->get();
        $assignmentKey = static fn (int $roleId, ScopeType $scopeType, ?int $scopeId): string =>
            implode(':', [$roleId, $scopeType->value, $scopeId ?? 'null']);
        $desiredKeys = [];

        foreach ($desiredAssignments as $assignment) {
            $desiredKeys[$assignmentKey($assignment['role_id'], $assignment['scope_type'], $assignment['scope_id'])] = true;
        }

        foreach ($activeAssignments as $assignment) {
            $key = $assignmentKey(
                (int) $assignment->role_id,
                $assignment->scope_type,
                $assignment->scope_id === null ? null : (int) $assignment->scope_id
            );
            if (! isset($desiredKeys[$key])) {
                $assignment->update(['valid_to' => $revokedAt]);
            }
        }

        foreach ($desiredAssignments as $desired) {
            $query = UserRole::query()
                ->where('user_id', $user->getKey())
                ->where('role_id', $desired['role_id'])
                ->where('scope_type', $desired['scope_type']);

            if ($desired['scope_id'] === null) {
                $query->whereNull('scope_id');
            } else {
                $query->where('scope_id', $desired['scope_id']);
            }

            $assignment = $query->first();
            $isCurrentlyActive = $assignment !== null
                && $assignment->valid_from->lte($now)
                && ($assignment->valid_to === null || $assignment->valid_to->gte($now));

            if ($assignment !== null && ! $isCurrentlyActive) {
                $assignment->forceFill([
                    'valid_from' => $now,
                    'valid_to' => null,
                    'granted_by' => $grantedBy,
                ])->save();
            } elseif ($assignment === null) {
                UserRole::query()->create([
                    'user_id' => $user->getKey(),
                    'role_id' => $desired['role_id'],
                    'scope_type' => $desired['scope_type'],
                    'scope_id' => $desired['scope_id'],
                    'valid_from' => $now,
                    'valid_to' => null,
                    'granted_by' => $grantedBy,
                ]);
            }
        }
    }
}
