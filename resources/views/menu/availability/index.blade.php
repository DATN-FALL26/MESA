@extends('layouts.auth')

@section('title', 'Còn hàng & giá chi nhánh | MESA')
@section('heading', 'Thực đơn')

@section('content')
    @php
        $user = auth()->user();
        $canToggle = $user->hasAnyPermission(['menu.availability.update', 'menu.manage']);
        $canPrice = $user->hasPermission('menu.manage');

        $states = [];
        $counts = ['available' => 0, 'soldout' => 0, 'none' => 0];
        foreach ($items as $item) {
            $row = $rows->get($item->id);
            $state = $row === null ? 'none' : ($row->is_available ? 'available' : 'soldout');
            $states[$item->id] = $state;
            $counts[$state]++;
        }
        $grouped = $items->groupBy(fn ($item) => $item->category?->name ?? 'Chưa phân loại');
    @endphp

    <x-menu.tabs active="availability" />

    <x-menu.page-header title="Còn hàng và giá theo chi nhánh" description="Báo hết món ngay khi bếp hết nguyên liệu và đặt giá riêng cho từng chi nhánh. Thay đổi có hiệu lực ngay với khách đặt qua QR.">
        <x-slot:actions>
            @if ($canToggle && $counts['soldout'] > 0)
                <x-menu.confirm :action="route('menu.availability.reset', $branch)" method="POST" tone="primary"
                    title="Mở bán lại {{ $counts['soldout'] }} món đang hết?" message="Tất cả món đang báo hết tại {{ $branch->name }} sẽ chuyển sang còn hàng." confirm="Mở bán lại tất cả"
                    class="inline-flex items-center gap-2 rounded-lg border border-emerald-300 bg-white px-3.5 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">
                    <x-menu.icon name="check" class="size-4" /> Mở bán lại tất cả ({{ $counts['soldout'] }})
                </x-menu.confirm>
            @endif
        </x-slot:actions>
    </x-menu.page-header>

    {{-- Chọn chi nhánh --}}
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <span class="mr-1 text-sm text-slate-500">Chi nhánh</span>
        @if ($branches->count() <= 5)
            @foreach ($branches as $option)
                <a href="{{ route('menu.availability.index', ['branch_id' => $option->id]) }}"
                    @class([
                        'rounded-full px-3.5 py-1.5 text-sm font-medium ring-1 ring-inset transition-colors',
                        'bg-slate-900 text-white ring-slate-900' => $option->id === $branch->id,
                        'bg-white text-slate-600 ring-slate-300 hover:bg-slate-50' => $option->id !== $branch->id,
                    ])
                    @if ($option->id === $branch->id) aria-current="true" @endif>{{ $option->name }}</a>
            @endforeach
        @else
            <form method="GET" action="{{ route('menu.availability.index') }}">
                <x-menu.select name="branch_id" :old="false" onchange="this.form.submit()" aria-label="Chọn chi nhánh">
                    @foreach ($branches as $option)
                        <option value="{{ $option->id }}" @selected($option->id === $branch->id)>{{ $option->name }}</option>
                    @endforeach
                </x-menu.select>
            </form>
        @endif
    </div>

    {{-- Bộ lọc tức thì: trạng thái và tìm kiếm, không tải lại trang --}}
    <div class="sticky top-0 z-10 -mx-1 mb-4 flex flex-wrap items-center gap-3 bg-slate-100/95 px-1 py-2 backdrop-blur">
        <div class="inline-flex overflow-hidden rounded-lg border border-slate-300 bg-white text-sm shadow-xs" role="group" aria-label="Lọc theo trạng thái">
            @foreach ([['all', 'Tất cả', $items->count()], ['available', 'Còn hàng', $counts['available']], ['soldout', 'Hết hàng', $counts['soldout']], ['none', 'Chưa áp dụng', $counts['none']]] as [$key, $label, $count])
                <button type="button" data-filter="{{ $key }}" @class(['px-3.5 py-2 font-medium transition-colors hover:bg-slate-50', 'border-l border-slate-200' => $key !== 'all'])>
                    {{ $label }} <span class="ml-1 text-xs tabular-nums text-slate-400">{{ $count }}</span>
                </button>
            @endforeach
        </div>
        <div class="relative min-w-52 flex-1 sm:max-w-xs">
            <x-menu.icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <input id="availability-search" type="search" placeholder="Tìm món, gõ không dấu cũng được" autocomplete="off"
                class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 shadow-xs placeholder:text-slate-400 focus:border-indigo-500 focus:outline-hidden focus:ring-2 focus:ring-indigo-200">
        </div>
        <p id="availability-count" class="text-sm text-slate-500" aria-live="polite"></p>
    </div>

    @if ($items->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white">
            <x-menu.empty title="Chưa có món đang kinh doanh" text="Hãy thêm món hoặc bật lại món đang tạm ngừng ở tab Món ăn." icon="squares" />
        </div>
    @else
        <div class="space-y-5" id="availability-list">
            @foreach ($grouped as $categoryName => $categoryItems)
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white" data-category-block>
                    <h3 class="border-b border-slate-200 bg-slate-50 px-5 py-2.5 text-sm font-semibold text-slate-700">{{ $categoryName }}</h3>
                    <div class="divide-y divide-slate-100">
                        @foreach ($categoryItems as $item)
                            @php
                                $row = $rows->get($item->id);
                                $state = $states[$item->id];
                                $override = $row?->price !== null ? (int) $row->price : null;
                                $delta = $override !== null ? $override - (int) $item->base_price : 0;
                            @endphp
                            <div class="grid grid-cols-12 items-center gap-x-4 gap-y-3 px-5 py-3.5" data-row data-state="{{ $state }}"
                                data-search="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($item->name.' '.$item->sku)) }}">
                                <div class="col-span-12 min-w-0 md:col-span-5">
                                    <p @class(['truncate text-sm font-medium', 'text-slate-900' => $state === 'available', 'text-slate-500' => $state !== 'available'])>{{ $item->name }}</p>
                                    <p class="truncate text-xs text-slate-500"><span class="font-mono">{{ $item->sku }}</span></p>
                                </div>

                                <div class="col-span-7 md:col-span-4">
                                    @if ($canPrice)
                                        <form method="POST" action="{{ route('menu.availability.price', [$branch, $item]) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <div class="w-36">
                                                <x-menu.input name="price" id="price-{{ $item->id }}" type="number" min="0" step="1" :value="$override" :old="false" suffix="đ"
                                                    placeholder="{{ (int) $item->base_price }}" aria-label="Giá riêng của {{ $item->name }}" />
                                            </div>
                                            <button type="submit" class="rounded-lg px-2.5 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Lưu</button>
                                        </form>
                                    @else
                                        <p class="text-sm font-medium tabular-nums text-slate-900"><x-menu.money :value="$override ?? $item->base_price" /></p>
                                    @endif
                                    <p class="mt-1 text-xs text-slate-500">
                                        Giá gốc <x-menu.money :value="$item->base_price" />
                                        @if ($override !== null && $delta !== 0)
                                            <x-menu.badge :tone="$delta > 0 ? 'warning' : 'info'" class="ml-1.5">Giá riêng {{ $delta > 0 ? '+' : '−' }}<x-menu.money :value="abs($delta)" /></x-menu.badge>
                                        @endif
                                    </p>
                                </div>

                                <div class="col-span-5 flex justify-end md:col-span-3">
                                    @if ($canToggle)
                                        <form method="POST" action="{{ route('menu.availability.update', [$branch, $item]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <x-menu.switch name="is_available" :checked="$state === 'available'" autosubmit>
                                                <span @class([
                                                    'w-24 text-sm font-medium',
                                                    'text-emerald-700' => $state === 'available',
                                                    'text-rose-600' => $state === 'soldout',
                                                    'text-slate-400' => $state === 'none',
                                                ])>{{ ['available' => 'Còn hàng', 'soldout' => 'Hết hàng', 'none' => 'Chưa áp dụng'][$state] }}</span>
                                            </x-menu.switch>
                                        </form>
                                    @else
                                        <x-menu.badge :tone="['available' => 'success', 'soldout' => 'danger', 'none' => 'neutral'][$state]" dot>
                                            {{ ['available' => 'Còn hàng', 'soldout' => 'Hết hàng', 'none' => 'Chưa áp dụng'][$state] }}
                                        </x-menu.badge>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <p id="availability-empty" class="hidden rounded-xl border border-dashed border-slate-300 bg-white px-5 py-10 text-center text-sm text-slate-500">Không có món nào khớp bộ lọc.</p>
    @endif

    <script>
        (function () {
            var filter = 'all';
            var query = '';
            var rows = document.querySelectorAll('[data-row]');
            var blocks = document.querySelectorAll('[data-category-block]');
            var buttons = document.querySelectorAll('[data-filter]');
            var search = document.getElementById('availability-search');
            var counter = document.getElementById('availability-count');
            var empty = document.getElementById('availability-empty');
            if (!rows.length) return;

            function normalize(text) {
                return text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'd').toLowerCase().trim();
            }

            function apply() {
                var visible = 0;
                rows.forEach(function (row) {
                    var matchState = filter === 'all' || row.dataset.state === filter;
                    var matchText = query === '' || row.dataset.search.indexOf(query) !== -1;
                    var show = matchState && matchText;
                    row.classList.toggle('hidden', !show);
                    if (show) visible++;
                });
                blocks.forEach(function (block) {
                    block.classList.toggle('hidden', !block.querySelector('[data-row]:not(.hidden)'));
                });
                buttons.forEach(function (button) {
                    var active = button.dataset.filter === filter;
                    button.classList.toggle('bg-slate-900', active);
                    button.classList.toggle('text-white', active);
                    button.classList.toggle('text-slate-600', !active);
                });
                if (counter) counter.textContent = visible + '/' + rows.length + ' món';
                if (empty) empty.classList.toggle('hidden', visible !== 0);
            }

            buttons.forEach(function (button) {
                button.addEventListener('click', function () { filter = button.dataset.filter; apply(); });
            });
            if (search) {
                search.addEventListener('input', function () { query = normalize(search.value); apply(); });
            }
            apply();
        })();
    </script>
@endsection
