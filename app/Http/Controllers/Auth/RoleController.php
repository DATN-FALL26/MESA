<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()
            ->withCount('permissions')
            ->orderBy('name')
            ->paginate(15);

        return view('roles.index', compact('roles'));
    }

    public function create(): View
    {
        return $this->formView(new Role, 'roles.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedRole($request);
        $this->authorizePermissionAssignment($request, $validated);

        DB::transaction(function () use ($validated): void {
            $role = Role::query()->create([
                'code' => strtoupper($validated['code']),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_system' => false,
            ]);

            if (array_key_exists('permission_ids', $validated)) {
                $role->permissions()->sync($validated['permission_ids']);
            }
        });

        return redirect()->route('roles.index')->with('success', 'Đã tạo vai trò.');
    }

    public function edit(Role $role): View
    {
        return $this->formView($role, 'roles.form', $role->permissions()->pluck('permissions.id')->all());
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $this->validatedRole($request, $role);
        $this->authorizePermissionAssignment($request, $validated);

        DB::transaction(function () use ($validated, $role): void {
            $role->fill([
                'code' => $role->is_system ? $role->code : strtoupper($validated['code']),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ])->save();

            if (array_key_exists('permission_ids', $validated)) {
                $role->permissions()->sync($validated['permission_ids']);
            }
        });

        return redirect()->route('roles.index')->with('success', 'Đã cập nhật vai trò.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return back()->withErrors(['role' => 'Không thể xóa vai trò hệ thống.']);
        }

        if ($role->userRoles()->exists()) {
            return back()->withErrors(['role' => 'Không thể xóa vai trò đang được gán cho tài khoản.']);
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Đã xóa vai trò.');
    }

    /**
     * @param  list<int>|null  $selectedPermissionIds
     */
    private function formView(Role $role, string $view, ?array $selectedPermissionIds = null): View
    {
        $permissions = Permission::query()
            ->orderBy('module')
            ->orderBy('name')
            ->get()
            ->groupBy('module');

        return view($view, [
            'role' => $role,
            'permissions' => $permissions,
            'selectedPermissionIds' => $selectedPermissionIds ?? [],
            'canAssignPermissions' => auth()->user()->hasPermission(ConstantHelper::PERM_ROLES_ASSIGN_PERMISSIONS),
        ]);
    }

    /**
     * @return array{code: string, name: string, description?: string|null, permission_ids?: list<int>}
     */
    private function validatedRole(Request $request, ?Role $role = null): array
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z][A-Za-z0-9_-]*$/',
                Rule::unique(Role::class, 'code')->ignore($role?->getKey()),
            ],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'permission_ids' => ['sometimes', 'array'],
            'permission_ids.*' => ['integer', 'distinct', Rule::exists(ConstantHelper::TABLE_PERMISSIONS, 'id')],
            'assign_permissions' => ['sometimes', 'accepted'],
        ]);

        if ($request->boolean('assign_permissions')) {
            $validated['permission_ids'] = array_map('intval', $validated['permission_ids'] ?? []);
        }

        unset($validated['assign_permissions']);

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function authorizePermissionAssignment(Request $request, array $validated): void
    {
        if (array_key_exists('permission_ids', $validated)) {
            abort_unless($request->user()->hasPermission(ConstantHelper::PERM_ROLES_ASSIGN_PERMISSIONS), 403);
        }
    }
}
