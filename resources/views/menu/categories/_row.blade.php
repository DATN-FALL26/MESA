@php
    $blocked = $row->menu_items_count > 0 || (! $isChild && $row->children->isNotEmpty());
    $blockedReason = $row->menu_items_count > 0
        ? 'Danh mục còn món. Hãy tắt hoạt động thay vì xóa.'
        : 'Danh mục còn danh mục con. Hãy xóa danh mục con trước.';
@endphp
<div @class([
    'flex flex-wrap items-center justify-between gap-3 px-5 py-3.5',
    'border-t border-slate-100 bg-slate-50/60 pl-12' => $isChild,
])>
    <div class="flex min-w-0 items-center gap-3">
        @if ($isChild)
            <span class="h-5 w-3 shrink-0 rounded-bl-md border-b border-l border-slate-300" aria-hidden="true"></span>
        @endif
        <div class="min-w-0">
            <p @class(['truncate text-slate-900', 'text-sm font-semibold' => ! $isChild, 'text-sm font-medium' => $isChild])>{{ $row->name }}</p>
            <p class="text-xs text-slate-500">{{ $row->menu_items_count }} món · thứ tự {{ $row->sort_order }}</p>
        </div>
        <x-menu.badge :tone="$row->is_active ? 'success' : 'neutral'" dot>{{ $row->is_active ? 'Hoạt động' : 'Đang tắt' }}</x-menu.badge>
    </div>

    @if ($canManage)
        <div class="flex items-center gap-1">
            @unless ($isChild)
                <a href="{{ route('menu.categories.create', ['parent_id' => $row->id]) }}" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                    <x-menu.icon name="plus" class="size-3.5" /> Thêm danh mục con
                </a>
            @endunless
            <a href="{{ route('menu.categories.edit', $row) }}" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-50">
                <x-menu.icon name="pencil" class="size-3.5" /> Sửa
            </a>
            @if ($blocked)
                <span class="inline-flex cursor-not-allowed items-center rounded-lg px-2 py-1.5 text-slate-300" title="{{ $blockedReason }}">
                    <x-menu.icon name="trash" class="size-4" />
                </span>
            @else
                <x-menu.confirm :action="route('menu.categories.destroy', $row)" title="Xóa danh mục “{{ $row->name }}”?" message="Danh mục chưa có món nào nên xóa được. Thao tác này không hoàn tác." confirm="Xóa danh mục"
                    class="inline-flex items-center rounded-lg px-2 py-1.5 text-slate-500 hover:bg-rose-50 hover:text-rose-600" aria-label="Xóa danh mục {{ $row->name }}">
                    <x-menu.icon name="trash" class="size-4" />
                </x-menu.confirm>
            @endif
        </div>
    @endif
</div>
