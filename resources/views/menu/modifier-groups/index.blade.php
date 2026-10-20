@extends('layouts.auth')

@section('title', 'Nhóm topping | MESA')
@section('heading', 'Thực đơn')

@section('content')
    @php($canManage = auth()->user()->hasPermission('menu.manage'))

    <x-menu.tabs active="groups" />

    <x-menu.page-header title="Nhóm topping" description="Gom các lựa chọn thêm như thêm thịt, thêm quẩy thành nhóm rồi gán cho từng món.">
        <x-slot:actions>
            @if ($canManage)
                <a href="{{ route('menu.modifier-groups.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <x-menu.icon name="plus" class="size-4" /> Thêm nhóm
                </a>
            @endif
        </x-slot:actions>
    </x-menu.page-header>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        @if ($groups->isEmpty())
            <x-menu.empty title="Chưa có nhóm topping" text="Tạo nhóm đầu tiên, thêm topping vào nhóm rồi gán cho món." icon="squares">
                @if ($canManage)
                    <a href="{{ route('menu.modifier-groups.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        <x-menu.icon name="plus" class="size-4" /> Thêm nhóm
                    </a>
                @endif
            </x-menu.empty>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs font-medium text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Nhóm</th>
                            <th class="px-5 py-3">Khách chọn</th>
                            <th class="px-5 py-3 text-center">Topping</th>
                            <th class="px-5 py-3 text-center">Món đang dùng</th>
                            <th class="px-5 py-3">Trạng thái</th>
                            <th class="px-5 py-3"><span class="sr-only">Thao tác</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($groups as $group)
                            @php($blocked = $group->modifiers_count > 0)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-3.5 font-medium text-slate-900">
                                    @if ($canManage)
                                        <a href="{{ route('menu.modifier-groups.edit', $group) }}" class="hover:text-indigo-700">{{ $group->name }}</a>
                                    @else
                                        {{ $group->name }}
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-slate-600">
                                    {{ $group->min_select === 0 ? 'Không bắt buộc' : 'Bắt buộc chọn '.$group->min_select }}, tối đa {{ $group->max_select }}
                                </td>
                                <td class="px-5 py-3.5 text-center tabular-nums text-slate-600">{{ $group->modifiers_count }}</td>
                                <td class="px-5 py-3.5 text-center tabular-nums text-slate-600">{{ $group->menu_items_count }}</td>
                                <td class="px-5 py-3.5">
                                    <x-menu.badge :tone="$group->is_active ? 'success' : 'neutral'" dot>{{ $group->is_active ? 'Hoạt động' : 'Đang tắt' }}</x-menu.badge>
                                </td>
                                <td class="px-5 py-3.5">
                                    @if ($canManage)
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('menu.modifier-groups.edit', $group) }}" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-50">
                                                <x-menu.icon name="pencil" class="size-3.5" /> Sửa
                                            </a>
                                            @if ($blocked)
                                                <span class="inline-flex cursor-not-allowed items-center rounded-lg px-2 py-1.5 text-slate-300" title="Nhóm còn topping. Hãy xóa các topping trước.">
                                                    <x-menu.icon name="trash" class="size-4" />
                                                </span>
                                            @else
                                                <x-menu.confirm :action="route('menu.modifier-groups.destroy', $group)" title="Xóa nhóm “{{ $group->name }}”?"
                                                    message="Nhóm sẽ bị gỡ khỏi các món đang dùng." confirm="Xóa nhóm"
                                                    class="inline-flex items-center rounded-lg px-2 py-1.5 text-slate-500 hover:bg-rose-50 hover:text-rose-600" aria-label="Xóa nhóm {{ $group->name }}">
                                                    <x-menu.icon name="trash" class="size-4" />
                                                </x-menu.confirm>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($groups->hasPages())
                <div class="border-t border-slate-200 px-5 py-3">{{ $groups->links() }}</div>
            @endif
        @endif
    </div>
@endsection
