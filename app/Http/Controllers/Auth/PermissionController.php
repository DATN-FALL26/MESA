<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PermissionController extends Controller
{
    private const MODULES = [
        'employee' => 'Nhân viên',
        'role' => 'Vai trò',
        'permission' => 'Quyền',
        'user' => 'Tài khoản',
        'food' => 'Món ăn',
        'category' => 'Danh mục',
        'table' => 'Bàn ăn',
        'invoice' => 'Hóa đơn',
        'order' => 'Đơn hàng',
        'inventory' => 'Kho',
    ];

    private const ACTIONS = [
        'view' => 'Xem',
        'create' => 'Thêm',
        'update' => 'Sửa',
        'delete' => 'Xóa',
        'print' => 'In',
        'approve' => 'Duyệt',
        'export' => 'Xuất dữ liệu',
        'import' => 'Nhập dữ liệu',
    ];

    public function index(Request $request): View
    {
        $modules = Permission::query()
            ->distinct()
            ->orderBy('module')
            ->pluck('module');
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'module' => ['nullable', 'string', 'max:50', Rule::in($modules->all())],
            'usage' => ['nullable', Rule::in(['assigned', 'unassigned'])],
        ]);
        $search = trim($filters['q'] ?? '');
        $module = $filters['module'] ?? '';
        $usage = $filters['usage'] ?? '';

        $permissions = Permission::query()
            ->withCount('roles')
            ->when($search !== '', fn ($query) => $query->where(
                fn ($inner) => $inner
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
            ))
            ->when($module !== '', fn ($query) => $query->where('module', $module))
            ->when($usage === 'assigned', fn ($query) => $query->has('roles'))
            ->when($usage === 'unassigned', fn ($query) => $query->doesntHave('roles'))
            ->orderByDesc('id')
            ->orderBy('module')
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        return view('permissions.index', [
            'permissions' => $permissions,
            'modules' => $modules,
            'filters' => compact('search', 'module', 'usage'),
        ]);
    }

    public function create(): View
    {
        return view('permissions.form', [
            'permission' => new Permission,
            'modules' => self::MODULES,
            'actions' => self::ACTIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $input = $request->only(['module', 'action', 'name', 'description']);
        if (is_string($input['module'] ?? null)) {
            $input['module'] = strtolower($input['module']);
        }

        $validated = Validator::make($input, [
            'module' => ['required', 'string', Rule::in(array_keys(self::MODULES))],
            'action' => ['required', 'string', Rule::in(array_keys(self::ACTIONS))],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $module = strtolower($validated['module']);
        $code = $module.'.'.$validated['action'];

        Validator::make(['code' => $code], [
            'code' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z][a-z0-9_-]*\.[a-z]+$/',
                Rule::unique(Permission::class, 'code'),
            ],
        ], [
            'code.unique' => 'Quyền đã tồn tại',
        ])->validate();

        Permission::query()->create([
            'code' => $code,
            'module' => $module,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

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
