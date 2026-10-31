@extends('layouts.auth')

@section('title', 'Tài khoản | MESA')
@section('heading', 'Quản lý tài khoản')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600">Danh sách tài khoản đăng nhập hệ thống.</p>
        @if (auth()->user()->hasRole(\App\Helpers\ConstantHelper::ROLE_ADMIN) ||
                (auth()->user()->hasPermission(\App\Helpers\ConstantHelper::PERM_USERS_CREATE) &&
                    auth()->user()->hasPermission(\App\Helpers\ConstantHelper::PERM_USERS_ASSIGN_ROLES)))
            <a href="{{ route('users.create') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
                Thêm tài khoản mới
            </a>
        @endif
    </div>

    <form method="GET" action="{{ route('users.index') }}"
        class="mb-5 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-5">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-[minmax(16rem,1.6fr)_minmax(12rem,1fr)_minmax(12rem,1fr)_auto] xl:items-end">
            <div>
                <label for="q" class="mb-1.5 block text-sm font-medium text-slate-700">Tìm kiếm</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path stroke-linecap="round" d="m16 16 4 4"></path>
                    </svg>
                    <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}"
                        placeholder="Tên, email hoặc tên đăng nhập"
                        class="w-full rounded-lg border-slate-300 pl-9 text-sm shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <div>
                <label for="role_id" class="mb-1.5 block text-sm font-medium text-slate-700">Vai trò</label>
                <select id="role_id" name="role_id"
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Tất cả vai trò</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected((string) ($filters['role_id'] ?? '') === (string) $role->id)>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="status" class="mb-1.5 block text-sm font-medium text-slate-700">Trạng thái</label>
                <select id="status" name="status"
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Tất cả trạng thái</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-2 sm:col-span-2 sm:flex-row xl:col-span-1">
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Lọc tài khoản
                </button>
                <a href="{{ route('users.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Đặt lại
                </a>
            </div>
        </div>
    </form>

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
                                        <span
                                            class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">{{ $role->name }}</span>
                                    @empty
                                        <span class="text-amber-700">Chưa gán</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $user->status->label() }}</td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-3">
                                    @if (auth()->user()->hasPermission('users.update'))
                                        <a href="{{ route('users.edit', $user) }}"
                                            class="font-medium text-indigo-700 hover:underline">Sửa</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-slate-500">Chưa có tài khoản.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-5 py-4">{{ $users->links() }}</div>
    </div>
@endsection
