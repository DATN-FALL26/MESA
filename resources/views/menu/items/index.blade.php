@extends('layouts.auth')

@section('title', 'Món ăn - MESA Operations')
@section('heading', 'Quản lý món ăn')

@section('content')
    @php
        $canManage = auth()->user()->hasPermission('menu.manage');
        $hasFilters = $filters['search'] !== '' || $filters['categoryId'] || $filters['itemType'] !== '' || $filters['status'] !== '';
    @endphp

    <div class="space-y-6">
        <x-menu.page-header title="Quản lý món ăn"
            :description="$stats['all'].' món trong thực đơn chung, '.$stats['active'].' đang kinh doanh. Giá riêng và trạng thái còn hàng theo từng chi nhánh chỉnh ở mục Còn hàng & giá chi nhánh.'">
            <x-slot:actions>
                @if ($canManage)
                    <x-menu.button :href="route('menu.items.create')">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span>Thêm món</span>
                    </x-menu.button>
                @endif
            </x-slot:actions>
        </x-menu.page-header>

        <!-- SEARCH & FILTERS -->
        <form method="GET" action="{{ route('menu.items.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 bg-white p-4 rounded-xl border border-slate-200">
            <div class="md:col-span-2 relative">
                <input type="search" name="q" id="item-search" value="{{ $filters['search'] }}" placeholder="Tìm theo tên hoặc mã SKU... (nhấn / để tìm nhanh)" autocomplete="off"
                    class="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>
            <div>
                <select name="category_id" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-emerald-600">
                    <option value="">-- Danh mục --</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($filters['categoryId'] === $category->id)>{{ $category->parent_id ? '— ' : '' }}{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <select name="item_type" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-emerald-600">
                    <option value="">-- Loại món --</option>
                    @foreach ($itemTypes as $type)
                        <option value="{{ $type->value }}" @selected($filters['itemType'] === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
                <select name="status" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-emerald-600">
                    <option value="">-- Trạng thái --</option>
                    <option value="active" @selected($filters['status'] === 'active')>Đang kinh doanh</option>
                    <option value="inactive" @selected($filters['status'] === 'inactive')>Tạm ngừng</option>
                </select>
                @if ($hasFilters)
                    <a href="{{ route('menu.items.index') }}" class="px-3 py-2 text-xs text-slate-600 hover:text-slate-900 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors shrink-0">Làm mới</a>
                @endif
            </div>
        </form>

        <!-- TABLE CONTAINER -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider font-semibold">
                            <th class="py-3 px-4">Món</th>
                            <th class="py-3 px-4">Danh mục</th>
                            <th class="py-3 px-4">Loại / Quầy</th>
                            <th class="py-3 px-4 text-right">Giá gốc</th>
                            <th class="py-3 px-4 text-center">Biến thể</th>
                            <th class="py-3 px-4 text-center">Trạng thái</th>
                            @if ($canManage)
                                <th class="py-3 px-4 text-right">Thao tác</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($items as $item)
                            @php
                                $deleteConfig = [
                                    'action' => route('menu.items.destroy', $item),
                                    'method' => 'DELETE',
                                    'title' => 'Xóa món ăn',
                                    'subtitle' => 'Xóa mềm, lịch sử đơn hàng vẫn được giữ.',
                                    'message' => 'Bạn có chắc chắn muốn xóa món "'.$item->name.'"? Món sẽ ẩn khỏi thực đơn ở mọi chi nhánh.',
                                    'confirm' => 'Xác nhận xóa',
                                ];
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        @if ($item->image_path)
                                            <img src="{{ asset('storage/'.$item->image_path) }}" alt="" class="w-10 h-10 rounded-lg object-cover border border-slate-200 shrink-0">
                                        @else
                                            <div class="w-10 h-10 rounded-lg bg-slate-100 text-slate-400 flex items-center justify-center shrink-0">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.41a2.25 2.25 0 0 1 3.182 0l2.909 2.91m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z" />
                                                </svg>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            @if ($canManage)
                                                <a href="{{ route('menu.items.edit', $item) }}" class="block font-semibold text-slate-900 hover:text-emerald-700 truncate">{{ $item->name }}</a>
                                            @else
                                                <span class="block font-semibold text-slate-900 truncate">{{ $item->name }}</span>
                                            @endif
                                            <span class="font-mono text-[10px] text-slate-400">{{ $item->sku }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-slate-500">{{ $item->category?->name ?? '—' }}</td>
                                <td class="py-3 px-4">
                                    <span class="block text-slate-700">{{ $item->item_type->label() }}</span>
                                    <span class="text-[10px] text-slate-400">{{ $item->station_code->label() }}</span>
                                </td>
                                <td class="py-3 px-4 text-right font-semibold text-slate-900 tabular-nums whitespace-nowrap">{{ number_format((float) $item->base_price, 0, ',', '.') }}đ</td>
                                <td class="py-3 px-4 text-center tabular-nums">{{ $item->variants_count ?: '—' }}</td>
                                <td class="py-3 px-4 text-center">
                                    <x-menu.badge :tone="$item->is_active ? 'success' : 'neutral'">{{ $item->is_active ? 'Đang kinh doanh' : 'Tạm ngừng' }}</x-menu.badge>
                                </td>
                                @if ($canManage)
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <a href="{{ route('menu.items.edit', $item) }}" class="px-2 py-1 text-slate-600 hover:text-emerald-700 font-medium hover:underline">Sửa</a>
                                        <form method="POST" action="{{ route('menu.items.toggle', $item) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="px-2 py-1 text-slate-600 hover:text-amber-700 font-medium hover:underline">{{ $item->is_active ? 'Tạm ngừng' : 'Bật lại' }}</button>
                                        </form>
                                        <button type="button" data-confirm="{{ json_encode($deleteConfig, JSON_UNESCAPED_UNICODE) }}"
                                            class="px-2 py-1 text-slate-500 hover:text-rose-600 font-medium hover:underline">Xóa</button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <x-menu.empty-row :colspan="$canManage ? 7 : 6"
                                :message="$hasFilters ? 'Không tìm thấy món nào phù hợp với bộ lọc.' : 'Chưa có món nào. Hãy thêm món đầu tiên để bắt đầu xây dựng thực đơn.'" />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-menu.pagination :paginator="$items" label="món" />
        </div>
    </div>

    @if ($canManage)
        <x-menu.confirm-modal />
    @endif

    <x-menu.scripts />
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
