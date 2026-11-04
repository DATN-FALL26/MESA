@extends('layouts.auth')

@section('title', 'Tài khoản | MESA')
@section('heading', 'Quản lý tài khoản')

@section('content')
    @php
        $hasFilters = ($filters['q'] ?? '') !== '' || ($filters['role_id'] ?? '') !== '' || ($filters['status'] ?? '') !== '';
        $canCreate = auth()->user()->hasRole(\App\Helpers\ConstantHelper::ROLE_ADMIN) ||
            (auth()->user()->hasPermission(\App\Helpers\ConstantHelper::PERM_USERS_CREATE) &&
                auth()->user()->hasPermission(\App\Helpers\ConstantHelper::PERM_USERS_ASSIGN_ROLES));
    @endphp

    <div class="space-y-6">
        <x-menu.page-header title="Quản lý tài khoản"
            description="Tìm kiếm, lọc và quản lý tài khoản đăng nhập hệ thống.">
            <x-slot:actions>
                @if ($canCreate)
                    <x-menu.button :href="route('users.create')">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span>Thêm tài khoản</span>
                    </x-menu.button>
                @endif
            </x-slot:actions>
        </x-menu.page-header>

        <form method="GET" action="{{ route('users.index') }}"
            class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-4">
            <div class="relative md:col-span-2">
                <label for="user-search" class="sr-only">Tìm tài khoản</label>
                <input id="user-search" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                    placeholder="Tìm theo tên, email hoặc tên đăng nhập..." autocomplete="off"
                    class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-xs focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600">
                <svg class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24"
                    stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>

            <div>
                <label for="role_id" class="sr-only">Vai trò</label>
                <select id="role_id" name="role_id" onchange="this.form.submit()"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-emerald-600 focus:outline-none">
                    <option value="">Tất cả vai trò</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected((string) ($filters['role_id'] ?? '') === (string) $role->id)>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <label for="status" class="sr-only">Trạng thái</label>
                <select id="status" name="status" onchange="this.form.submit()"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-emerald-600 focus:outline-none">
                    <option value="">Tất cả trạng thái</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
                <button type="submit"
                    class="shrink-0 rounded-lg bg-slate-800 px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-slate-900">
                    Lọc
                </button>
                @if ($hasFilters)
                    <a href="{{ route('users.index') }}"
                        class="shrink-0 rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900">
                        Làm mới
                    </a>
                @endif
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xs">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 font-semibold uppercase tracking-wider text-slate-500">
                            <th class="px-4 py-3">Tài khoản</th>
                            <th class="px-4 py-3">Phòng ban</th>
                            <th class="px-4 py-3">Vai trò</th>
                            <th class="px-4 py-3 text-center">Trạng thái</th>
                            <th class="px-4 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($users as $user)
                            <tr class="transition-colors hover:bg-slate-50/60">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 font-semibold text-emerald-700">
                                            {{ mb_strtoupper(mb_substr($user->full_name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-slate-900">{{ $user->full_name }}</p>
                                            <p class="truncate text-[10px] text-slate-500">
                                                {{ $user->username }} · {{ $user->email ?? 'Chưa có email' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-500">{{ $user->department?->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse ($user->activeRoles->unique('id') as $role)
                                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-medium text-emerald-700">
                                                {{ $role->name }}
                                            </span>
                                        @empty
                                            <span class="text-[10px] font-medium text-amber-700">Chưa gán</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-[10px] font-medium',
                                        'bg-emerald-50 text-emerald-700' => $user->status === \App\Enums\UserStatus::ACTIVE,
                                        'bg-slate-100 text-slate-600' => $user->status !== \App\Enums\UserStatus::ACTIVE,
                                    ])>
                                        {{ $user->status->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-3">
                                        @if (auth()->user()->hasPermission('users.update'))
                                            <a href="{{ route('users.edit', $user) }}" class="font-semibold text-emerald-700 hover:text-emerald-900">Sửa</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-slate-500">
                                    {{ $hasFilters ? 'Không tìm thấy tài khoản phù hợp.' : 'Chưa có tài khoản.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-menu.pagination :paginator="$users" label="tài khoản" />
        </div>
    </div>
@endsection
