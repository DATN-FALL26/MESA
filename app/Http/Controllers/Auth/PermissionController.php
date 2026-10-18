<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(): View
    {
        $permissions = Permission::query()
            ->withCount('roles')
            ->orderBy('module')
            ->orderBy('code')
            ->paginate(20);

        return view('permissions.index', compact('permissions'));
    }

    public function create(): View
    {
        return view('permissions.form', ['permission' => new Permission]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedPermission($request);

        Permission::query()->create($validated);

        return redirect()->route('permissions.index')->with('success', 'Đã tạo quyền.');
    }

    public function edit(Permission $permission): View
    {
        return view('permissions.form', compact('permission'));
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $permission->update($this->validatedPermission($request, $permission));

        return redirect()->route('permissions.index')->with('success', 'Đã cập nhật quyền.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        if ($permission->roles()->exists()) {
            return back()->withErrors(['permission' => 'Hãy gỡ quyền khỏi các vai trò trước khi xóa.']);
        }

        $permission->delete();

        return redirect()->route('permissions.index')->with('success', 'Đã xóa quyền.');
    }

    /**
     * @return array{code: string, module: string, name: string, description?: string|null}
     */
    private function validatedPermission(Request $request, ?Permission $permission = null): array
    {
        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*$/',
                Rule::unique(Permission::class, 'code')->ignore($permission?->getKey()),
            ],
            'module' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_-]*$/'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
