@extends('layouts.auth')

@section('title', 'Còn hàng & giá chi nhánh - MESA Operations')
@section('heading', 'Còn hàng & giá chi nhánh')

@section('content')
    @php
        $user = auth()->user();
        $canToggle = $user->hasAnyPermission(['menu.availability.update', 'menu.manage']);
        $canPrice = $user->hasPermission('menu.manage');

        $stateLabels = ['available' => 'Còn hàng', 'soldout' => 'Hết hàng', 'none' => 'Chưa áp dụng'];
        $stateTones = ['available' => 'success', 'soldout' => 'danger', 'none' => 'neutral'];
        $states = [];
        $counts = ['available' => 0, 'soldout' => 0, 'none' => 0];
        foreach ($items as $item) {
            $row = $rows->get($item->id);
            $state = $row === null ? 'none' : ($row->is_available ? 'available' : 'soldout');
            $states[$item->id] = $state;
            $counts[$state]++;
        }
        $resetConfig = [
            'action' => route('menu.availability.reset', $branch),
            'method' => 'POST',
            'tone' => 'primary',
            'title' => 'Mở bán lại tất cả',
            'subtitle' => $branch->name,
            'message' => 'Chuyển '.$counts['soldout'].' món đang báo hết sang còn hàng tại '.$branch->name.'?',
            'confirm' => 'Mở bán lại',
        ];
    @endphp

    <div class="space-y-6">
        <x-menu.page-header title="Còn hàng & giá theo chi nhánh"
            description="Báo hết món ngay khi bếp hết nguyên liệu và đặt giá riêng cho từng chi nhánh. Thay đổi có hiệu lực ngay với khách đặt qua QR.">
            <x-slot:actions>
                <form method="GET" action="{{ route('menu.availability.index') }}" class="flex items-center gap-2">
                    <label for="branch_id" class="text-xs font-medium text-slate-500">Chi nhánh</label>
                    <select name="branch_id" id="branch_id" onchange="this.form.submit()"
                        class="px-3 py-2 text-xs font-medium rounded-lg border border-slate-300 bg-white focus:outline-none focus:border-emerald-600">
                        @foreach ($branches as $option)
                            <option value="{{ $option->id }}" @selected($option->id === $branch->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </form>
                @if ($canToggle && $counts['soldout'] > 0)
                    <x-menu.button data-confirm="{{ json_encode($resetConfig, JSON_UNESCAPED_UNICODE) }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        <span>Mở bán lại tất cả ({{ $counts['soldout'] }})</span>
                    </x-menu.button>
                @endif
            </x-slot:actions>
        </x-menu.page-header>

        <!-- BỘ LỌC (lọc tức thì, không tải lại trang) -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 bg-white p-4 rounded-xl border border-slate-200">
            <div class="md:col-span-2 relative">
                <input type="search" id="availability-search" placeholder="Tìm món (gõ không dấu cũng được)..." autocomplete="off"
                    class="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>
            <div>
                <select id="availability-category" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-emerald-600">
                    <option value="">-- Danh mục --</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->parent_id ? '— ' : '' }}{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select id="availability-state" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-emerald-600">
                    <option value="">-- Trạng thái ({{ $items->count() }}) --</option>
                    <option value="available">Còn hàng ({{ $counts['available'] }})</option>
                    <option value="soldout">Hết hàng ({{ $counts['soldout'] }})</option>
                    <option value="none">Chưa áp dụng ({{ $counts['none'] }})</option>
                </select>
            </div>
        </div>

        <!-- TABLE CONTAINER -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider font-semibold">
                            <th class="py-3 px-4">Món</th>
                            <th class="py-3 px-4">Danh mục</th>
                            <th class="py-3 px-4 text-right">Giá gốc</th>
                            <th class="py-3 px-4">Giá tại chi nhánh</th>
                            <th class="py-3 px-4 text-center">Trạng thái</th>
                            @if ($canToggle)
                                <th class="py-3 px-4 text-right">Thao tác</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody id="availability-body" class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($items as $item)
                            @php
                                $row = $rows->get($item->id);
                                $state = $states[$item->id];
                                $override = $row?->price !== null ? (int) $row->price : null;
                                $delta = $override !== null ? $override - (int) $item->base_price : 0;
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition-colors" data-row data-state="{{ $state }}" data-category="{{ $item->category_id }}"
                                data-search="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($item->name.' '.$item->sku)) }}">
                                <td class="py-3 px-4">
                                    <span @class(['block font-semibold', 'text-slate-900' => $state === 'available', 'text-slate-400' => $state !== 'available'])>{{ $item->name }}</span>
                                    <span class="font-mono text-[10px] text-slate-400">{{ $item->sku }}</span>
                                </td>
                                <td class="py-3 px-4 text-slate-500">{{ $item->category?->name ?? '—' }}</td>
                                <td class="py-3 px-4 text-right tabular-nums text-slate-600 whitespace-nowrap">{{ number_format((float) $item->base_price, 0, ',', '.') }}đ</td>
                                <td class="py-3 px-4">
                                    @if ($canPrice)
                                        <form method="POST" action="{{ route('menu.availability.price', [$branch, $item]) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <div class="relative w-32">
                                                <input type="number" name="price" min="0" step="1" value="{{ $override }}" placeholder="{{ (int) $item->base_price }}"
                                                    aria-label="Giá riêng của {{ $item->name }}"
                                                    class="w-full pl-3 pr-8 py-1.5 rounded-lg border border-slate-300 text-xs tabular-nums focus:outline-none focus:border-emerald-600">
                                                <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center text-[10px] text-slate-400">đ</span>
                                            </div>
                                            <button type="submit" class="px-2 py-1 text-slate-600 hover:text-emerald-700 font-medium hover:underline">Lưu</button>
                                            @if ($override !== null && $delta !== 0)
                                                <x-menu.badge :tone="$delta > 0 ? 'warning' : 'info'">{{ $delta > 0 ? '+' : '−' }}{{ number_format(abs($delta), 0, ',', '.') }}đ</x-menu.badge>
                                            @endif
                                        </form>
                                    @else
                                        <span class="font-semibold text-slate-900 tabular-nums">{{ number_format((float) ($override ?? $item->base_price), 0, ',', '.') }}đ</span>
                                        @if ($override !== null && $delta !== 0)
                                            <x-menu.badge :tone="$delta > 0 ? 'warning' : 'info'" class="ml-1">Giá riêng</x-menu.badge>
                                        @endif
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <x-menu.badge :tone="$stateTones[$state]">{{ $stateLabels[$state] }}</x-menu.badge>
                                </td>
                                @if ($canToggle)
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <form method="POST" action="{{ route('menu.availability.update', [$branch, $item]) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_available" value="{{ $state === 'available' ? 0 : 1 }}">
                                            @if ($state === 'available')
                                                <button type="submit" class="px-2.5 py-1 rounded-lg border border-rose-200 text-rose-600 font-medium hover:bg-rose-50 transition-colors">Báo hết</button>
                                            @else
                                                <button type="submit" class="px-2.5 py-1 rounded-lg border border-emerald-200 text-emerald-700 font-medium hover:bg-emerald-50 transition-colors">
                                                    {{ $state === 'none' ? 'Áp dụng & mở bán' : 'Mở bán lại' }}
                                                </button>
                                            @endif
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <x-menu.empty-row :colspan="$canToggle ? 6 : 5" message="Chưa có món nào đang kinh doanh. Hãy thêm món hoặc bật lại món ở trang Món ăn." />
                        @endforelse
                        <tr id="availability-empty" class="hidden">
                            <td colspan="{{ $canToggle ? 6 : 5 }}" class="py-10 text-center text-slate-400">Không tìm thấy món nào phù hợp với bộ lọc.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="availability-count" class="px-4 py-3 bg-slate-50 border-t border-slate-200 text-xs text-slate-600" aria-live="polite">
                Hiển thị {{ $items->count() }} / {{ $items->count() }} món
            </div>
        </div>
    </div>

    @if ($canToggle)
        <x-menu.confirm-modal />
    @endif

    <x-menu.scripts />
    <script>
        (function () {
            var rows = document.querySelectorAll('[data-row]');
            if (!rows.length) return;
            var search = document.getElementById('availability-search');
            var category = document.getElementById('availability-category');
            var state = document.getElementById('availability-state');
            var empty = document.getElementById('availability-empty');
            var counter = document.getElementById('availability-count');

            function normalize(text) {
                return text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'd').toLowerCase().trim();
            }

            function apply() {
                var query = normalize(search.value);
                var visible = 0;
                rows.forEach(function (row) {
                    var show = (query === '' || row.dataset.search.indexOf(query) !== -1)
                        && (category.value === '' || row.dataset.category === category.value)
                        && (state.value === '' || row.dataset.state === state.value);
                    row.classList.toggle('hidden', !show);
                    if (show) visible++;
                });
                empty.classList.toggle('hidden', visible !== 0);
                counter.textContent = 'Hiển thị ' + visible + ' / ' + rows.length + ' món';
            }

            search.addEventListener('input', apply);
            category.addEventListener('change', apply);
            state.addEventListener('change', apply);
        })();
    </script>
@endsection
