@extends('layouts.auth')

@section('title', 'Nhóm topping - MESA Operations')
@section('heading', 'Nhóm topping')

@section('content')
    @php
        $canManage = auth()->user()->hasPermission('menu.manage');
        $hasFilters = $filters['search'] !== '' || $filters['status'] !== '';
        $createConfig = [
            'action' => route('menu.modifier-groups.store'),
            'title' => 'Thêm nhóm topping',
            'submit' => 'Lưu nhóm',
            'values' => ['min_select' => 0, 'max_select' => 1, 'is_active' => true],
        ];
    @endphp

    <div class="space-y-6">
        <x-menu.page-header title="Nhóm topping"
            description="Gom các lựa chọn thêm như thêm thịt, thêm quẩy thành nhóm, rồi gán nhóm cho từng món ở trang sửa món.">
            <x-slot:actions>
                @if ($canManage)
                    <x-menu.button data-modal-open="group-modal" data-config="{{ json_encode($createConfig, JSON_UNESCAPED_UNICODE) }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span>Thêm nhóm</span>
                    </x-menu.button>
                @endif
            </x-slot:actions>
        </x-menu.page-header>

        <form method="GET" action="{{ route('menu.modifier-groups.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 bg-white p-4 rounded-xl border border-slate-200">
            <div class="md:col-span-2 relative">
                <input type="search" name="q" value="{{ $filters['search'] }}" placeholder="Tìm theo tên nhóm..." autocomplete="off"
                    class="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>
            <div>
                <select name="status" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-emerald-600">
                    <option value="">-- Trạng thái --</option>
                    <option value="active" @selected($filters['status'] === 'active')>Đang hoạt động</option>
                    <option value="inactive" @selected($filters['status'] === 'inactive')>Đang tắt</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 text-xs font-semibold bg-slate-800 hover:bg-slate-900 text-white rounded-lg transition-colors shrink-0">Lọc</button>
                @if ($hasFilters)
                    <a href="{{ route('menu.modifier-groups.index') }}" class="px-3 py-2 text-xs text-slate-600 hover:text-slate-900 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors shrink-0">Làm mới</a>
                @endif
            </div>
        </form>

        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider font-semibold">
                            <th class="py-3 px-4">Tên nhóm</th>
                            <th class="py-3 px-4">Khách chọn</th>
                            <th class="py-3 px-4 text-center">Topping</th>
                            <th class="py-3 px-4 text-center">Món đang dùng</th>
                            <th class="py-3 px-4 text-center">Trạng thái</th>
                            @if ($canManage)
                                <th class="py-3 px-4 text-right">Thao tác</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($groups as $group)
                            @php
                                $editConfig = [
                                    'action' => route('menu.modifier-groups.update', $group),
                                    'method' => 'PUT',
                                    'title' => 'Chỉnh sửa nhóm topping',
                                    'submit' => 'Lưu thay đổi',
                                    'values' => [
                                        'name' => $group->name,
                                        'min_select' => $group->min_select,
                                        'max_select' => $group->max_select,
                                        'is_active' => $group->is_active,
                                    ],
                                ];
                                $deleteConfig = [
                                    'action' => route('menu.modifier-groups.destroy', $group),
                                    'method' => 'DELETE',
                                    'title' => 'Xóa nhóm topping',
                                    'subtitle' => 'Nhóm sẽ bị gỡ khỏi các món đang dùng.',
                                    'message' => 'Bạn có chắc chắn muốn xóa nhóm "'.$group->name.'"?',
                                    'confirm' => 'Xác nhận xóa',
                                ];
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4 font-semibold text-slate-900">
                                    @if ($canManage)
                                        <a href="{{ route('menu.modifier-groups.edit', $group) }}" class="hover:text-emerald-700">{{ $group->name }}</a>
                                    @else
                                        {{ $group->name }}
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-slate-500">
                                    {{ $group->min_select === 0 ? 'Không bắt buộc' : 'Bắt buộc chọn '.$group->min_select }}, tối đa {{ $group->max_select }}
                                </td>
                                <td class="py-3 px-4 text-center tabular-nums">{{ $group->modifiers_count }}</td>
                                <td class="py-3 px-4 text-center tabular-nums">{{ $group->menu_items_count }}</td>
                                <td class="py-3 px-4 text-center">
                                    <x-menu.badge :tone="$group->is_active ? 'success' : 'neutral'">{{ $group->is_active ? 'Hoạt động' : 'Đang tắt' }}</x-menu.badge>
                                </td>
                                @if ($canManage)
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <a href="{{ route('menu.modifier-groups.edit', $group) }}" class="px-2 py-1 text-slate-600 hover:text-emerald-700 font-medium hover:underline">Topping</a>
                                        <button type="button" data-modal-open="group-modal" data-config="{{ json_encode($editConfig, JSON_UNESCAPED_UNICODE) }}"
                                            class="px-2 py-1 text-slate-600 hover:text-emerald-700 font-medium hover:underline">Sửa</button>
                                        @if ($group->modifiers_count > 0)
                                            <span class="px-2 py-1 text-slate-300 font-medium cursor-not-allowed" title="Nhóm còn topping, hãy xóa các topping trước.">Xóa</span>
                                        @else
                                            <button type="button" data-confirm="{{ json_encode($deleteConfig, JSON_UNESCAPED_UNICODE) }}"
                                                class="px-2 py-1 text-slate-500 hover:text-rose-600 font-medium hover:underline">Xóa</button>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <x-menu.empty-row :colspan="$canManage ? 6 : 5"
                                :message="$hasFilters ? 'Không tìm thấy nhóm nào phù hợp với bộ lọc.' : 'Chưa có nhóm topping nào. Hãy tạo nhóm đầu tiên rồi thêm topping vào nhóm.'" />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-menu.pagination :paginator="$groups" label="nhóm" />
        </div>
    </div>

    @if ($canManage)
        @include('menu.modifier-groups._group-modal')
        <x-menu.confirm-modal />
    @endif

    <x-menu.scripts />
@endsection
