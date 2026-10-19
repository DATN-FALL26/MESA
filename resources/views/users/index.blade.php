@extends('layouts.auth')

@section('title', 'Tài khoản | MESA')
@section('heading', 'Quản lý tài khoản')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600">Danh sách tài khoản đăng nhập hệ thống.</p>
        @if (auth()->user()->hasPermission('users.create')
            && auth()->user()->hasPermission('users.assign_roles'))
            <a href="{{ route('users.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Tạo tài khoản</a>
        @endif
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Tài khoản</th>
                        <th class="px-5 py-3">Phòng ban</th>
                        <th class="px-5 py-3">Vai trò</th>
                        <th class="px-5 py-3">Trạng thái</th>
                        <th class="px-5 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-900">{{ $user->full_name }}</p>
                                <p class="text-slate-500">{{ $user->username }} · {{ $user->email ?? 'Chưa có email' }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $user->department?->name ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($user->activeRoles->unique('id') as $role)
                                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">{{ $role->name }}</span>
                                    @empty
                                        <span class="text-amber-700">Chưa gán</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $user->status->label() }}</td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-3">
                                    @if (auth()->user()->hasPermission('users.update'))
                                        <a href="{{ route('users.edit', $user) }}" class="font-medium text-indigo-700 hover:underline">Sửa</a>
                                    @endif
                                    @if (auth()->user()->hasPermission('users.delete') && ! auth()->user()->is($user))
                                        <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Xóa tài khoản này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-red-700 hover:underline">Xóa</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Chưa có tài khoản.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-5 py-4">{{ $users->links() }}</div>
    </div>
@endsection
