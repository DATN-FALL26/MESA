@extends('layouts.auth')

@section('title', 'Vai trò | MESA')
@section('heading', 'Quản lý vai trò')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600">Vai trò là nhóm các quyền được cấp cho tài khoản.</p>
        @if (auth()->user()->hasPermission('roles.create'))
            <a href="{{ route('roles.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Tạo vai trò</a>
        @endif
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Vai trò</th>
                        <th class="px-5 py-3">Quyền</th>
                        <th class="px-5 py-3">Phân loại</th>
                        <th class="px-5 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($roles as $role)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-900">{{ $role->name }}</p>
                                <p class="text-slate-500">{{ $role->code }}{{ $role->description ? ' · '.$role->description : '' }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $role->permissions_count }}</td>
                            <td class="px-5 py-4">
                                @if ($role->is_system)
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-700">Hệ thống</span>
                                @else
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs text-emerald-700">Tùy chỉnh</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-3">
                                    @if (auth()->user()->hasPermission('roles.update'))
                                        <a href="{{ route('roles.edit', $role) }}" class="font-medium text-indigo-700 hover:underline">Sửa</a>
                                    @endif
                                    @if (auth()->user()->hasPermission('roles.delete') && ! $role->is_system)
                                        <form method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('Xóa vai trò này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-red-700 hover:underline">Xóa</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">Chưa có vai trò.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-5 py-4">{{ $roles->links() }}</div>
    </div>
@endsection
