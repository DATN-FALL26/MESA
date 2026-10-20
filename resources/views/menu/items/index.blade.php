@extends('layouts.auth')

@section('title', 'Món ăn | MESA')
@section('heading', 'Thực đơn')

@section('content')
    @php
        $canManage = auth()->user()->hasPermission('menu.manage');
        $hasFilters = $filters['search'] !== '' || $filters['categoryId'] || $filters['itemType'] !== '' || $filters['status'] !== '';
        $statusTabs = [
            '' => ['Tất cả', $stats['all']],
            'active' => ['Đang kinh doanh', $stats['active']],
            'inactive' => ['Tạm ngừng', $stats['inactive']],
        ];
    @endphp

    <x-menu.tabs active="items" />

    <x-menu.page-header title="Món ăn" description="Thực đơn dùng chung cho cả chuỗi. Giá riêng và trạng thái còn hàng của từng chi nhánh chỉnh ở tab “Còn hàng & giá chi nhánh”.">
        <x-slot:actions>
            @if ($canManage)
                <a href="{{ route('menu.items.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <x-menu.icon name="plus" class="size-4" /> Thêm món
                </a>
            @endif
        </x-slot:actions>
    </x-menu.page-header>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        @foreach ($statusTabs as $key => [$label, $count])
            <a href="{{ request()->fullUrlWithQuery(['status' => $key === '' ? null : $key, 'page' => null]) }}"
                @class([
                    'inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-sm font-medium ring-1 ring-inset transition-colors',
                    'bg-slate-900 text-white ring-slate-900' => $filters['status'] === $key,
                    'bg-white text-slate-600 ring-slate-300 hover:bg-slate-50' => $filters['status'] !== $key,
                ])>
                {{ $label }}
                <span @class(['text-xs tabular-nums', 'text-slate-300' => $filters['status'] === $key, 'text-slate-400' => $filters['status'] !== $key])>{{ $count }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('menu.items.index') }}" class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_14rem_12rem_auto]">
        @if ($filters['status'] !== '')
            <input type="hidden" name="status" value="{{ $filters['status'] }}">
        @endif
        <div class="relative">
            <x-menu.icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <input id="item-search" type="search" name="q" value="{{ $filters['search'] }}" placeholder="Tìm theo tên hoặc SKU   (nhấn / để tìm nhanh)" autocomplete="off"
                class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 shadow-xs placeholder:text-slate-400 focus:border-indigo-500 focus:outline-hidden focus:ring-2 focus:ring-indigo-200">
        </div>
        <x-menu.select name="category_id" :old="false" onchange="this.form.submit()" aria-label="Lọc theo danh mục">
            <option value="">Tất cả danh mục</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected($filters['categoryId'] === $category->id)>{{ $category->parent_id ? '— ' : '' }}{{ $category->name }}</option>
            @endforeach
        </x-menu.select>
        <x-menu.select name="item_type" :old="false" onchange="this.form.submit()" aria-label="Lọc theo loại món">
            <option value="">Tất cả loại</option>
            @foreach ($itemTypes as $type)
                <option value="{{ $type->value }}" @selected($filters['itemType'] === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </x-menu.select>
        <div class="flex items-center gap-2">
            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Tìm</button>
            @if ($hasFilters)
                <a href="{{ route('menu.items.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-800">Xóa lọc</a>
            @endif
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        @if ($items->isEmpty())
            @if ($hasFilters)
                <x-menu.empty title="Không có món nào khớp bộ lọc" text="Thử bỏ bớt điều kiện lọc hoặc tìm bằng từ khác." icon="search">
                    <a href="{{ route('menu.items.index') }}" class="text-sm font-semibold text-indigo-700 hover:underline">Xóa bộ lọc</a>
                </x-menu.empty>
            @else
                <x-menu.empty title="Chưa có món nào" text="Thêm món đầu tiên để bắt đầu xây dựng thực đơn." icon="squares">
                    @if ($canManage)
                        <a href="{{ route('menu.items.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                            <x-menu.icon name="plus" class="size-4" /> Thêm món
                        </a>
                    @endif
                </x-menu.empty>
            @endif
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs font-medium text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Món</th>
                            <th class="px-5 py-3">Loại và quầy</th>
                            <th class="px-5 py-3 text-right">Giá gốc</th>
                            <th class="px-5 py-3 text-center">Biến thể</th>
                            <th class="px-5 py-3">Kinh doanh</th>
                            <th class="px-5 py-3"><span class="sr-only">Thao tác</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($items as $item)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        @if ($item->image_path)
                                            <img src="{{ asset('storage/'.$item->image_path) }}" alt="" class="size-12 shrink-0 rounded-lg object-cover ring-1 ring-slate-200">
                                        @else
                                            <div class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400"><x-menu.icon name="photo" class="size-5" /></div>
                                        @endif
                                        <div class="min-w-0">
                                            @if ($canManage)
                                                <a href="{{ route('menu.items.edit', $item) }}" class="block truncate font-medium text-slate-900 hover:text-indigo-700">{{ $item->name }}</a>
                                            @else
                                                <p class="truncate font-medium text-slate-900">{{ $item->name }}</p>
                                            @endif
                                            <p class="truncate text-xs text-slate-500"><span class="font-mono">{{ $item->sku }}</span> · {{ $item->category?->name ?? 'Chưa phân loại' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-slate-600">
                                    {{ $item->item_type->label() }}
                                    <span class="block text-xs text-slate-500">{{ $item->station_code->label() }}</span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-right font-medium tabular-nums text-slate-900"><x-menu.money :value="$item->base_price" /></td>
                                <td class="px-5 py-3.5 text-center tabular-nums text-slate-600">{{ $item->variants_count ?: '—' }}</td>
                                <td class="px-5 py-3.5">
                                    @if ($canManage)
                                        <form method="POST" action="{{ route('menu.items.toggle', $item) }}">
                                            @csrf
                                            @method('PATCH')
                                            <x-menu.switch name="is_active" :checked="$item->is_active" autosubmit :label="$item->is_active ? 'Đang bán' : 'Tạm ngừng'" />
                                        </form>
                                    @else
                                        <x-menu.badge :tone="$item->is_active ? 'success' : 'neutral'" dot>{{ $item->is_active ? 'Đang bán' : 'Tạm ngừng' }}</x-menu.badge>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    @if ($canManage)
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('menu.items.edit', $item) }}" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-50">
                                                <x-menu.icon name="pencil" class="size-3.5" /> Sửa
                                            </a>
                                            <x-menu.confirm :action="route('menu.items.destroy', $item)" title="Xóa món “{{ $item->name }}”?"
                                                message="Món sẽ ẩn khỏi thực đơn. Lịch sử đơn hàng và báo cáo cũ vẫn được giữ nguyên." confirm="Xóa món"
                                                class="inline-flex items-center rounded-lg px-2 py-1.5 text-slate-500 hover:bg-rose-50 hover:text-rose-600" aria-label="Xóa món {{ $item->name }}">
                                                <x-menu.icon name="trash" class="size-4" />
                                            </x-menu.confirm>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($items->hasPages())
                <div class="border-t border-slate-200 px-5 py-3">{{ $items->links() }}</div>
            @endif
        @endif
    </div>

    <script>
        // Nhấn "/" để nhảy tới ô tìm kiếm (không kích hoạt khi đang gõ ở ô khác).
        document.addEventListener('keydown', function (event) {
            var tag = (event.target.tagName || '').toLowerCase();
            if (event.key === '/' && !['input', 'textarea', 'select'].includes(tag) && !event.target.isContentEditable) {
                event.preventDefault();
                document.getElementById('item-search').focus();
            }
        });
    </script>
@endsection
