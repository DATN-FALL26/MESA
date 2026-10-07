@extends('layouts.auth')

@section('title', 'Quyền | MESA')
@section('heading', 'Quản lý quyền')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600">Permission là quyền cụ thể; Role gom nhiều Permission.</p>
        @if (auth()->user()->hasPermission('permissions.create'))
            <a href="{{ route('permissions.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Tạo quyền</a>
        @endif
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Mã quyền</th>
                        <th class="px-5 py-3">Tên quyền</th>
                        <th class="px-5 py-3">Module</th>
                        <th class="px-5 py-3">Vai trò sử dụng</th>
                        <th class="px-5 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($permissions as $permission)
                        <tr>
                            <td class="px-5 py-4 font-mono text-xs text-slate-700">{{ $permission->code }}</td>
                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-900">{{ $permission->name }}</p>
                                @if ($permission->description)
                                    <p class="text-slate-500">{{ $permission->description }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $permission->module }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $permission->roles_count }}</td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-3">
                                    @if (auth()->user()->hasPermission('permissions.update'))
                                        <a href="{{ route('permissions.edit', $permission) }}" class="font-medium text-indigo-700 hover:underline">Sửa</a>
                                    @endif
                                    @if (auth()->user()->hasPermission('permissions.delete'))
                                        <form method="POST" action="{{ route('permissions.destroy', $permission) }}" onsubmit="return confirm('Xóa quyền này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-red-700 hover:underline">Xóa</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Chưa có quyền.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-5 py-4">{{ $permissions->links() }}</div>
    </div>
@endsection
