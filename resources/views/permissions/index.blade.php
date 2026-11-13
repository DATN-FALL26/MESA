@extends('layouts.auth')

@section('title', 'Danh mục quyền | MESA')
@section('heading', 'Danh mục quyền')

@section('content')
    @php
        $hasFilters = $filters['search'] !== '' || $filters['module'] !== '' || $filters['usage'] !== '';
    @endphp

    <div class="space-y-6">
        <x-menu.page-header title="Danh mục quyền"
            description="Tra cứu quyền chức năng theo mã, tên, module hoặc trạng thái sử dụng.">
            <x-slot:actions>
                @if (auth()->user()->hasPermission('permissions.create'))
                    <x-menu.button :href="route('permissions.create')">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span>Tạo quyền</span>
                    </x-menu.button>
                @endif
            </x-slot:actions>
        </x-menu.page-header>

        <form method="GET" action="{{ route('permissions.index') }}"
            class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-4">
            <div class="relative md:col-span-2">
                <label for="permission-search" class="sr-only">Tìm quyền</label>
                <input id="permission-search" type="search" name="q" value="{{ $filters['search'] }}"
                    placeholder="Tìm theo mã, tên, module hoặc mô tả..." autocomplete="off"
                    class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-xs focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600">
                <svg class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24"
                    stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>
            <div>
                <label for="permission-module" class="sr-only">Module</label>
                <select id="permission-module" name="module" onchange="this.form.submit()"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-emerald-600 focus:outline-none">
                    <option value="">Tất cả module</option>
                    @foreach ($modules as $module)
                        <option value="{{ $module }}" @selected($filters['module'] === $module)>{{ $module }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <label for="permission-usage" class="sr-only">Trạng thái sử dụng</label>
                <select id="permission-usage" name="usage" onchange="this.form.submit()"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-emerald-600 focus:outline-none">
                    <option value="">Mọi trạng thái</option>
                    <option value="assigned" @selected($filters['usage'] === 'assigned')>Đã gán vai trò</option>
                    <option value="unassigned" @selected($filters['usage'] === 'unassigned')>Chưa gán vai trò</option>
                </select>
                <button type="submit"
                    class="shrink-0 rounded-lg bg-slate-800 px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-slate-900">
                    Lọc
                </button>
                @if ($hasFilters)
                    <a href="{{ route('permissions.index') }}"
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
                            <th class="px-4 py-3">Mã quyền</th>
                            <th class="px-4 py-3">Tên quyền</th>
                            <th class="px-4 py-3">Module</th>
                            <th class="px-4 py-3 text-center">Vai trò sử dụng</th>
                            <th class="px-4 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($permissions as $permission)
                            <tr class="transition-colors hover:bg-slate-50/60">
                                <td class="px-4 py-3 font-mono text-[10px] text-slate-500">{{ $permission->code }}</td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900">{{ $permission->name }}</p>
                                    @if ($permission->description)
                                        <p class="text-[10px] text-slate-500">{{ $permission->description }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-medium text-emerald-700">{{ $permission->module }}</span>
                                </td>
                                <td class="px-4 py-3 text-center text-slate-600">{{ $permission->roles_count }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-3">
                                        @if (auth()->user()->hasPermission('permissions.update'))
                                            <a href="{{ route('permissions.edit', $permission) }}" class="font-semibold text-emerald-700 hover:text-emerald-900">Sửa</a>
                                        @endif
                                        @if (auth()->user()->hasPermission('permissions.delete'))
                                            <form method="POST" action="{{ route('permissions.destroy', $permission) }}"
                                                onsubmit="return confirm('Xóa quyền này?')">
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
                                <td colspan="5" class="px-4 py-10 text-center text-slate-500">
                                    {{ $hasFilters ? 'Không tìm thấy quyền phù hợp.' : 'Chưa có quyền.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-menu.pagination :paginator="$permissions" label="quyền" />
        </div>
    </div>
@endsection
