@extends('layouts.auth')

@section('title', 'Vai trò | MESA')
@section('heading', 'Quản lý vai trò')

@section('content')
    @php
        $hasFilters = $filters['search'] !== '' || $filters['type'] !== '';
    @endphp

    <div class="space-y-6">
        <x-menu.page-header title="Quản lý vai trò"
            description="Tìm kiếm, lọc và quản lý các nhóm quyền được cấp cho tài khoản.">
            <x-slot:actions>
                @if (auth()->user()->hasPermission('roles.create'))
                    <x-menu.button :href="route('roles.create')">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span>Tạo vai trò</span>
                    </x-menu.button>
                @endif
            </x-slot:actions>
        </x-menu.page-header>

        <form method="GET" action="{{ route('roles.index') }}"
            class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-4">
            <div class="relative md:col-span-2">
                <label for="role-search" class="sr-only">Tìm vai trò</label>
                <input id="role-search" type="search" name="q" value="{{ $filters['search'] }}"
                    placeholder="Tìm theo tên, mã hoặc mô tả vai trò..." autocomplete="off"
                    class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-xs focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600">
                <svg class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24"
                    stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>
            <div>
                <label for="role-type" class="sr-only">Loại vai trò</label>
                <select id="role-type" name="type" onchange="this.form.submit()"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-emerald-600 focus:outline-none">
                    <option value="">Tất cả vai trò</option>
                    <option value="system" @selected($filters['type'] === 'system')>Vai trò hệ thống</option>
                    <option value="custom" @selected($filters['type'] === 'custom')>Vai trò tùy chỉnh</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit"
                    class="shrink-0 rounded-lg bg-slate-800 px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-slate-900">
                    Lọc
                </button>
                @if ($hasFilters)
                    <a href="{{ route('roles.index') }}"
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
                            <th class="px-4 py-3">Vai trò</th>
                            <th class="px-4 py-3 text-center">Số quyền</th>
                            <th class="px-4 py-3">Phân loại</th>
                            <th class="px-4 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($roles as $role)
                            <tr class="transition-colors hover:bg-slate-50/60">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900">{{ $role->name }}</p>
                                    <p class="font-mono text-[10px] text-slate-400">
                                        {{ $role->code }}{{ $role->description ? ' · '.$role->description : '' }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-center text-slate-600">{{ $role->permissions_count }}</td>
                                <td class="px-4 py-3">
                                    @if ($role->is_system)
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-medium text-slate-700">Hệ thống</span>
                                    @else
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-medium text-emerald-700">Tùy chỉnh</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-3">
                                        @if (auth()->user()->hasPermission('roles.update'))
                                            <a href="{{ route('roles.edit', $role) }}" class="font-semibold text-emerald-700 hover:text-emerald-900">Sửa</a>
                                        @endif
                                        @if (auth()->user()->hasPermission('roles.delete') && ! $role->is_system)
                                            <form method="POST" action="{{ route('roles.destroy', $role) }}"
                                                onsubmit="return confirm('Xóa vai trò này?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-semibold text-red-600 hover:text-red-800">Xóa</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-slate-500">
                                    {{ $hasFilters ? 'Không tìm thấy vai trò phù hợp.' : 'Chưa có vai trò.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-menu.pagination :paginator="$roles" label="vai trò" />
        </div>
    </div>
@endsection
