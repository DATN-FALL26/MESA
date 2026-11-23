@extends('layouts.auth')

@section('title', 'Gọi món - Bàn ' . $session->table_code . ' | MESA')
@section('heading', 'Gọi món - Bàn ' . $session->table_code)

@section('content')
@php
    // Nhóm hiển thị phục vụ tìm kiếm/lọc trong giao diện; không thay đổi item_type nghiệp vụ.
    $detectMenuGroup = static function ($item): string {
        $rawType = data_get($item, 'item_type');
        $type = $rawType instanceof \BackedEnum ? (string) $rawType->value : (string) ($rawType ?? '');
        $name = mb_strtolower((string) data_get($item, 'name', ''), 'UTF-8');

        if ($type === \App\Enums\ItemType::DRINK->value) return 'drink';
        if ($type === \App\Enums\ItemType::SIDE->value) return 'side';
        if (str_contains($name, 'phở')) return 'pho';
        // Fallback chỉ dành cho chip phân loại UI nếu truy vấn menu chưa trả item_type.
        if ($type === '' && preg_match('/(nước|trà |cà phê|cafe|soda|coca|pepsi|sinh tố)/u', $name)) return 'drink';
        if ($type === '' && preg_match('/(quẩy|ăn kèm)/u', $name)) return 'side';
        return 'main';
    };

    $groupLabels = [
        'pho' => 'Phở',
        'side' => 'Món ăn kèm',
        'drink' => 'Đồ uống',
        'main' => 'Món chính',
    ];
    $groupCounts = ['pho' => 0, 'side' => 0, 'drink' => 0, 'main' => 0];
    foreach ($menuItems as $menuEntry) {
        $groupCounts[$detectMenuGroup($menuEntry)]++;
    }
    $initialItemCount = $order ? $orderItems->sum('quantity') : 0;
@endphp

{{-- CSS chỉ áp dụng cho trang Order; giữ nguyên layout và nền navy của MESA. --}}
<style>
    .mesa-pos {
        --mesa-border: #e2e8f0;
        --mesa-muted: #64748b;
        color: #0f172a;
    }
    .mesa-pos .mesa-surface {
        background: #ffffff;
        border: 1px solid var(--mesa-border);
        box-shadow: 0 1px 3px rgba(15, 23, 42, .06), 0 8px 24px rgba(15, 23, 42, .05);
    }
    .mesa-pos .mesa-item {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        transition: border-color .18s ease, background-color .18s ease;
    }
    .mesa-pos .mesa-item:hover { border-color: #93c5fd; background: #f8fbff; }
    .mesa-pos [data-menu-item][hidden] { display: none !important; }
    .mesa-pos .mesa-field {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #0f172a;
        outline: none;
    }
    .mesa-pos .mesa-field::placeholder { color: #94a3b8; }
    .mesa-pos .mesa-field:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, .15); }
    .mesa-pos .mesa-filter,
    .mesa-pos .mesa-mobile-tab {
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #334155;
    }
    .mesa-pos .mesa-filter[aria-pressed="true"],
    .mesa-pos .mesa-mobile-tab[aria-selected="true"] {
        color: #ffffff;
        background: #2563eb;
        border-color: #2563eb;
        box-shadow: 0 2px 10px rgba(37, 99, 235, .20);
    }
    .mesa-pos .mesa-scroll { scrollbar-color: #cbd5e1 transparent; scrollbar-width: thin; }
    .mesa-pos .mesa-scroll::-webkit-scrollbar { width: 6px; }
    .mesa-pos .mesa-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
    @media (max-width: 1279px) {
        .mesa-pos[data-active-tab="menu"] #order-panel { display: none; }
        .mesa-pos[data-active-tab="order"] #menu-panel { display: none; }
    }
    @media (min-width: 1280px) {
        .mesa-pos #menu-panel, .mesa-pos #order-panel { display: block !important; }
    }
</style>

<div id="mesa-pos" data-active-tab="menu" class="mesa-pos mx-auto w-full max-w-[1500px] px-1 pb-44 sm:px-3 xl:pb-8">

    {{-- ĐIỀU HƯỚNG VÀ THÔNG TIN BÀN --}}
    <div class="mb-5 flex flex-col gap-4 lg:mb-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <a href="{{ route('tables.index') }}"
                   class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 transition hover:border-blue-300 hover:bg-blue-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-400"
                   aria-label="Quay lại danh sách bàn">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7M5 12h14"/></svg>
                    <span>Danh sách bàn</span>
                </a>
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>Điểm bán hàng
                </span>
            </div>

            <div class="flex items-start gap-3 sm:gap-4">
                <div class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-100 ring-1 ring-slate-200 sm:flex">
                    <svg class="h-6 w-6 text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="3" width="16" height="14" rx="2"/><path d="M4 9h16M7 17v4m10-4v4M9 13h6"/></svg>
                </div>
                <div class="min-w-0">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-[30px]">Gọi món - Bàn {{ $session->table_code }}</h1>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">Chọn món, cập nhật số lượng và theo dõi trạng thái bếp theo thời gian thực.</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-700 sm:text-sm">
                            <svg class="h-4 w-4 text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            {{ $session->guest_count }} khách
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-700 sm:text-sm">
                            <svg class="h-4 w-4 text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 2 9 5-9 5-9-5 9-5Zm-9 10 9 5 9-5M3 17l9 5 9-5"/></svg>
                            Dining Session #{{ $session->session_id }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TAB TRÊN MOBILE / TABLET --}}
    <div class="mb-3 grid grid-cols-2 gap-2 xl:hidden" role="tablist" aria-label="Chuyển giữa thực đơn và order">
        <button type="button" data-mobile-tab="menu" aria-selected="true" aria-controls="menu-panel" role="tab"
                class="mesa-mobile-tab inline-flex min-h-12 items-center justify-center gap-2 rounded-xl px-3 text-sm font-semibold transition">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 3v7a3 3 0 0 0 6 0V3M7 3v18M20 3v8h-5l5-8Zm0 8v10"/></svg>
            Thực đơn
        </button>
        <button type="button" data-mobile-tab="order" aria-selected="false" aria-controls="order-panel" role="tab"
                class="mesa-mobile-tab inline-flex min-h-12 items-center justify-center gap-2 rounded-xl px-3 text-sm font-semibold transition">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 3h8l3 3v15l-3-2-4 2-4-2-3 2V6l3-3Z"/><path d="M9 10h6M9 14h6"/></svg>
            Order <span id="mesa-tab-count" class="rounded-full bg-slate-900/10 px-2 py-0.5 text-xs">{{ $initialItemCount }}</span>
        </button>
    </div>

    <div class="grid min-w-0 grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_390px] xl:items-start">

        {{-- THỰC ĐƠN: giữ ID này để AJAX replace đúng vùng --}}
        <section id="menu-panel" aria-label="Thực đơn" class="mesa-surface min-w-0 rounded-[22px] p-3 sm:p-5">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 3v7a3 3 0 0 0 6 0V3M7 3v18M20 3v8h-5l5-8Zm0 8v10"/></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Thực đơn</h2>
                        <p class="text-xs text-slate-500 sm:text-sm">Chọn món cho bàn {{ $session->table_code }}</p>
                    </div>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $menuItems->count() }} món</span>
            </div>

            <label class="relative mb-3 block">
                <span class="sr-only">Tìm món theo tên hoặc mô tả</span>
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input data-menu-search type="search" autocomplete="off" placeholder="Tìm món ăn, đồ uống..."
                       class="mesa-field h-11 w-full rounded-xl pl-10 pr-4 text-sm" />
            </label>

            <div class="mesa-scroll mb-4 flex gap-2 overflow-x-auto pb-1" aria-label="Lọc loại món">
                <button type="button" data-menu-category="all" aria-pressed="true" class="mesa-filter inline-flex shrink-0 items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold transition sm:text-sm">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    Tất cả
                </button>
                @foreach ($groupLabels as $group => $label)
                    @if ($groupCounts[$group] > 0)
                        <button type="button" data-menu-category="{{ $group }}" aria-pressed="false" class="mesa-filter inline-flex shrink-0 items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold transition sm:text-sm">
                            @if ($group === 'drink')
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 8h15l-2 13H6L4 8ZM2 8h19M8 3l2 5M14 3l-2 5"/></svg>
                            @elseif ($group === 'side')
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 13h16a7 7 0 0 1-7 7h-2a7 7 0 0 1-7-7ZM3 13h18M7 9l1-4M12 9l1-4M17 9l1-4"/></svg>
                            @elseif ($group === 'pho')
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 13h18a8 8 0 0 1-8 8h-2a8 8 0 0 1-8-8ZM2 13h20M8 9V3M13 9V3"/></svg>
                            @else
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 13h18M5 13a7 7 0 0 1 14 0M12 6V4M4 19h16"/></svg>
                            @endif
                            {{ $label }}
                        </button>
                    @endif
                @endforeach
            </div>

            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                @foreach ($menuItems as $item)
                    @php
                        $menuGroup = $detectMenuGroup($item);
                        $label = $groupLabels[$menuGroup];
                    @endphp
                    <article class="mesa-item flex min-w-0 gap-3 rounded-2xl p-3" data-menu-item
                             data-menu-group="{{ $menuGroup }}"
                             data-menu-search-text="{{ $item->name }} {{ $item->description }}">
                        <div class="h-[104px] w-[104px] shrink-0 overflow-hidden rounded-xl bg-slate-100 sm:h-[114px] sm:w-[114px]">
                            @if ($item->image_path && file_exists(public_path($item->image_path)))
                                <img src="{{ asset($item->image_path) }}" alt="{{ $item->name }}" class="h-full w-full object-cover" loading="lazy" />
                            @else
                                <div class="flex h-full w-full items-center justify-center text-center text-xs text-slate-500">Chưa có ảnh</div>
                            @endif
                        </div>
                        <div class="flex min-w-0 flex-1 flex-col">
                            <div class="mb-1 flex min-w-0 items-start justify-between gap-1">
                                <h3 class="line-clamp-2 text-sm font-bold leading-5 text-slate-900 sm:text-[15px]">{{ $item->name }}</h3>
                            </div>
                            <div class="mb-1 w-fit rounded-md bg-blue-50 px-1.5 py-0.5 text-[10px] font-medium text-blue-700">{{ $label }}</div>
                            <p class="line-clamp-2 text-xs leading-[1.4] text-slate-500">{{ $item->description }}</p>
                            <div class="mt-auto flex flex-wrap items-end justify-between gap-2 pt-2">
                                <span class="text-base font-bold text-red-700">{{ number_format($item->base_price, 0, ',', '.') }}đ</span>
                                @if ($order)
                                    <form action="{{ route('orders.items.add', [$order->id, $item->id]) }}" method="POST" data-order-ajax>
                                        @csrf
                                        <button type="submit" class="inline-flex min-h-9 items-center justify-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-300">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> Thêm
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            <div id="mesa-no-menu-results" class="hidden rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">Không tìm thấy món phù hợp.</div>
        </section>

        {{-- ORDER: giữ ID này để AJAX + Reverb cập nhật --}}
        <aside id="order-panel" aria-label="Order hiện tại"
               data-order-id="{{ $order?->id }}"
               data-order-total="{{ $order?->total_amount ?? 0 }}"
               data-order-count="{{ $initialItemCount }}"
               class="mesa-surface min-w-0 overflow-hidden rounded-[22px] xl:sticky xl:top-20">
            @if ($order)
                <div class="flex flex-col xl:h-[min(790px,calc(100vh-7rem))] xl:min-h-[560px]">
                    <div class="shrink-0 border-b border-slate-200 px-4 py-4 sm:px-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="mb-1 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <svg class="h-4 w-4 text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 3h8l3 3v15l-3-2-4 2-4-2-3 2V6l3-3Z"/><path d="M9 10h6M9 14h6"/></svg>
                                    Order hiện tại
                                </div>
                                <h2 class="break-all text-base font-bold text-slate-900 sm:text-lg">{{ $order->order_no }}</h2>
                            </div>
                            @php
                                $orderBadge = match ($order->status) {
                                    'draft' => ['Đang chọn món', 'bg-blue-50 text-blue-700'],
                                    'sent' => ['Đã gửi bếp', 'bg-blue-50 text-blue-700'],
                                    'served' => ['Đã phục vụ', 'bg-emerald-50 text-emerald-700'],
                                    'completed' => ['Hoàn thành', 'bg-emerald-50 text-emerald-700'],
                                    'cancelled' => ['Đã hủy', 'bg-rose-50 text-rose-700'],
                                    default => [$order->status, 'bg-slate-100 text-slate-600'],
                                };
                            @endphp
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $orderBadge[1] }}">{{ $orderBadge[0] }}</span>
                        </div>
                    </div>

                    <div class="mesa-scroll space-y-0 px-4 py-2 sm:px-5 xl:min-h-0 xl:flex-1 xl:overflow-y-auto">
                        @forelse ($orderItems as $orderItem)
                            @php
                                $isReadyMade = in_array($orderItem->item_type, [\App\Enums\ItemType::DRINK->value, \App\Enums\ItemType::SIDE->value], true);
                                $canEditQuantity = $orderItem->status === 'pending' || $isReadyMade || ($orderItem->status === 'sent' && $orderItem->kitchen_status === 'new');
                                $iconKind = $orderItem->item_type === \App\Enums\ItemType::DRINK->value ? 'drink' : ($orderItem->item_type === \App\Enums\ItemType::SIDE->value ? 'side' : 'main');
                                $statusMeta = match ($orderItem->status) {
                                    'pending' => ['Chưa gửi bếp', 'text-amber-700 bg-amber-50'],
                                    'sent' => ['Đã gửi bếp', 'text-blue-700 bg-blue-50'],
                                    'preparing' => ['Đang làm', 'text-orange-700 bg-orange-50'],
                                    'ready' => ['Sẵn sàng', 'text-emerald-700 bg-emerald-50'],
                                    'served' => ['Đã phục vụ', 'text-emerald-700 bg-emerald-50'],
                                    'cancelled' => ['Đã hủy', 'text-rose-700 bg-rose-50'],
                                    default => [$orderItem->status, 'text-slate-600 bg-slate-100'],
                                };
                            @endphp
                            <div class="border-b border-slate-200 py-4 last:border-b-0">
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700" aria-hidden="true">
                                        @if ($iconKind === 'drink')
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h15l-2 13H6L4 8ZM2 8h19M8 3l2 5M14 3l-2 5"/><path d="M8 13h7"/></svg>
                                        @elseif ($iconKind === 'side')
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18a8 8 0 0 1-8 8h-2a8 8 0 0 1-8-8ZM2 12h20M7 8l1-3M12 8l1-4M17 8l1-3"/></svg>
                                        @else
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 13h18a8 8 0 0 1-8 8h-2a8 8 0 0 1-8-8ZM2 13h20M7 9c0-1.5 1-2 1-3.5S7 3 7 3M12 9c0-1.5 1-2 1-3.5S12 3 12 3M17 9c0-1.5 1-2 1-3.5S17 3 17 3"/></svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-2">
                                            <h3 class="break-words text-sm font-semibold leading-5 text-slate-900">{{ $orderItem->item_name_snapshot }}</h3>
                                            <span class="shrink-0 text-sm font-bold text-slate-900">{{ number_format($orderItem->line_total, 0, ',', '.') }}đ</span>
                                        </div>
                                        <span class="mt-1.5 inline-flex rounded-md px-2 py-0.5 text-[11px] font-medium {{ $statusMeta[1] }}">{{ $statusMeta[0] }}</span>
                                        @if ($canEditQuantity)
                                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                                <form action="{{ route('orders.items.decrease', [$order->id, $orderItem->id]) }}" method="POST" data-order-ajax>
                                                    @csrf
                                                    <button type="submit" aria-label="Giảm {{ $orderItem->item_name_snapshot }}" class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-lg text-slate-700 transition hover:bg-slate-100">−</button>
                                                </form>
                                                <span class="min-w-6 text-center text-sm font-bold">{{ $orderItem->quantity }}</span>
                                                <form action="{{ route('orders.items.add', [$order->id, $orderItem->menu_item_id]) }}" method="POST" data-order-ajax>
                                                    @csrf
                                                    <button type="submit" aria-label="Tăng {{ $orderItem->item_name_snapshot }}" class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-lg font-bold text-white transition hover:bg-blue-500">+</button>
                                                </form>
                                                <span class="text-xs text-slate-500">{{ number_format($orderItem->unit_price_snapshot, 0, ',', '.') }}đ / món</span>
                                            </div>
                                        @else
                                            <p class="mt-2 text-xs text-slate-500">Số lượng: <strong class="text-slate-700">{{ $orderItem->quantity }}</strong> · {{ number_format($orderItem->unit_price_snapshot, 0, ',', '.') }}đ / món</p>
                                        @endif

                                        @if ($canEditQuantity)
                                            <form action="{{ route('orders.items.note', [$order->id, $orderItem->id]) }}" method="POST" data-order-ajax class="mt-3 space-y-2">
                                                @csrf
                                                <label class="sr-only" for="note-{{ $orderItem->id }}">Ghi chú {{ $orderItem->item_name_snapshot }}</label>
                                                <input id="note-{{ $orderItem->id }}" name="note" type="text" value="{{ $orderItem->note }}" placeholder="Ghi chú món..."
                                                       class="mesa-field min-h-10 w-full rounded-lg px-3 text-sm" />
                                                <button type="submit" class="min-h-9 rounded-lg border border-slate-200 bg-slate-100 px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-200">Lưu ghi chú</button>
                                            </form>
                                        @elseif ($orderItem->note)
                                            <p class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-xs leading-5 text-slate-600">Ghi chú: {{ $orderItem->note }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-12 text-center text-sm text-slate-500">Chưa có món nào trong order.</div>
                        @endforelse
                    </div>

                    {{-- BẢNG TỔNG VÀ HÀNH ĐỘNG: giữ nguyên điều kiện nghiệp vụ --}}
                    <div class="shrink-0 border-t border-slate-200 bg-slate-50 p-4 sm:p-5">
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <span class="text-sm font-semibold text-slate-600">Tổng cộng</span>
                            <strong class="text-xl font-bold text-red-700 sm:text-2xl">{{ number_format($order->total_amount, 0, ',', '.') }}đ</strong>
                        </div>
                        @php
                            $hasPending = $orderItems->where('status', 'pending')->count() > 0;
                            $hasKitchenChanges = $orderItems->contains(function ($item) {
                                if (!$item->kitchen_updated_at) return false;
                                $isReadyMade = in_array($item->item_type, [\App\Enums\ItemType::DRINK->value, \App\Enums\ItemType::SIDE->value], true);
                                $canUpdateKitchen = $isReadyMade || ($item->status === 'sent' && $item->kitchen_status === 'new');
                                if (!$canUpdateKitchen) return false;
                                return strtotime($item->updated_at) > strtotime($item->kitchen_updated_at);
                            });
                        @endphp

                        @if ($hasPending)
                            <form action="{{ route('orders.send-kitchen', $order->id) }}" method="POST" data-order-ajax data-kitchen-action class="mt-3">
                                @csrf
                                <button type="submit" class="flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-500">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 18h16M5 18l2-9h10l2 9M9 9V6h6v3M7 4h10"/></svg>
                                    Gửi bếp
                                </button>
                            </form>
                        @elseif ($hasKitchenChanges)
                            <form action="{{ route('orders.update-kitchen', $order->id) }}" method="POST" data-order-ajax data-kitchen-action class="mt-3">
                                @csrf
                                <button type="submit" class="flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-blue-500">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 18h16M5 18l2-9h10l2 9M9 9V6h6v3M7 4h10"/></svg>
                                    Cập nhật bếp
                                </button>
                            </form>
                        @else
                            <button type="button" disabled data-kitchen-disabled class="mt-3 min-h-12 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-4 text-sm font-semibold text-slate-500">Đã gửi bếp</button>
                        @endif

                        {{-- MOD04: giữ nguyên permission, dịch vụ tính tiền và route thanh toán từ bản gốc. --}}
                        @if ($orderItems->whereNotIn('status', ['pending', 'cancelled'])->isNotEmpty() && auth()->user()->hasPermission('payment.request'))
                            @php
                                $paymentSummary = app(\App\Services\Payment\OrderPaymentService::class)
                                    ->summary(\App\Models\Order::query()->findOrFail($order->id));
                            @endphp
                            @if ($paymentSummary['remaining'] > 0)
                                <a href="{{ route('payments.show', $order->id) }}" data-payment-action class="mt-2 flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-red-500">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/></svg>
                                    Thanh toán
                                </a>
                            @else
                                <a href="{{ route('payments.show', $order->id) }}" data-payment-action class="mt-2 flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-500">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    Đã thanh toán
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            @else
                <div class="px-5 py-7 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 3h8l3 3v15l-3-2-4 2-4-2-3 2V6l3-3Z"/><path d="M9 10h6M9 14h6"/></svg>
                    </div>
                    <h2 class="text-lg font-bold text-slate-900">Bàn này chưa có order</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Bắt đầu gọi món để tạo order cho bàn {{ $session->table_code }}.</p>
                    <form action="{{ route('orders.start', $session->session_id) }}" method="POST" data-order-ajax class="mt-5">
                        @csrf
                        <button type="submit" class="min-h-12 w-full rounded-xl bg-blue-600 px-4 font-semibold text-white transition hover:bg-blue-500">Bắt đầu gọi món</button>
                    </form>
                </div>
            @endif
        </aside>
    </div>

    {{-- Thanh truy cập Order trên điện thoại; không tạo bản sao nút gửi bếp/thanh toán. --}}
    <div id="mesa-mobile-bar" class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-3 py-3 shadow-[0_-8px_24px_rgba(15,23,42,.08)] backdrop-blur-xl xl:hidden">
        <div class="mx-auto flex max-w-[700px] items-center gap-3">
            <div class="min-w-0 flex-1">
                <p class="text-[11px] text-slate-500">Tổng cộng</p>
                <strong id="mesa-mobile-total" class="block truncate text-lg font-bold text-red-700">{{ number_format($order?->total_amount ?? 0, 0, ',', '.') }}đ</strong>
            </div>
            <button type="button" id="mesa-mobile-switch" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 text-sm font-bold text-white transition hover:bg-blue-500">
                <span id="mesa-mobile-switch-text">Xem Order ({{ $initialItemCount }})</span>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </button>
        </div>
        <div id="mesa-mobile-actions" class="mx-auto mt-2 hidden max-w-[700px] grid-cols-2 gap-2">
            <button type="button" id="mesa-mobile-kitchen" class="min-h-11 rounded-xl bg-blue-600 px-2 text-sm font-bold text-white transition hover:bg-blue-500">Cập nhật bếp</button>
            <button type="button" id="mesa-mobile-payment" class="min-h-11 rounded-xl bg-red-600 px-2 text-sm font-bold text-white transition hover:bg-red-500">Thanh toán</button>
        </div>
    </div>
</div>

<script>
(() => {
    const root = document.querySelector('#mesa-pos');
    if (!root) return;

    let activeCategory = 'all';
    let searchQuery = '';
    let currentRealtimeOrderId = null;

    const normalizeText = (text) => String(text ?? '')
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd').replace(/Đ/g, 'D').toLocaleLowerCase('vi');

    function filterMenu() {
        const panel = document.querySelector('#menu-panel');
        if (!panel) return;
        const input = panel.querySelector('[data-menu-search]');
        if (input && input.value !== searchQuery) input.value = searchQuery;
        let visibleCount = 0;
        panel.querySelectorAll('[data-menu-item]').forEach(card => {
            const categoryOk = activeCategory === 'all' || card.dataset.menuGroup === activeCategory;
            const textOk = normalizeText(card.dataset.menuSearchText).includes(normalizeText(searchQuery.trim()));
            card.hidden = !(categoryOk && textOk);
            if (!card.hidden) visibleCount++;
        });
        panel.querySelectorAll('[data-menu-category]').forEach(button => {
            button.setAttribute('aria-pressed', String(button.dataset.menuCategory === activeCategory));
        });
        const empty = panel.querySelector('#mesa-no-menu-results');
        if (empty) empty.classList.toggle('hidden', visibleCount > 0);
    }

    function setMobileTab(tab, scroll = false) {
        root.dataset.activeTab = tab === 'order' ? 'order' : 'menu';
        root.querySelectorAll('[data-mobile-tab]').forEach(button => {
            button.setAttribute('aria-selected', String(button.dataset.mobileTab === root.dataset.activeTab));
        });
        syncMobileBar();
        if (scroll) root.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function syncMobileBar() {
        const panel = document.querySelector('#order-panel');
        if (!panel) return;
        const count = Number(panel.dataset.orderCount || 0);
        const total = Number(panel.dataset.orderTotal || 0);
        const currency = Number.isFinite(total)
            ? new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(total) + 'đ'
            : '0đ';
        const label = document.querySelector('#mesa-mobile-total');
        const switcher = document.querySelector('#mesa-mobile-switch');
        const tabCount = document.querySelector('#mesa-tab-count');
        if (label) label.textContent = currency;
        if (tabCount) tabCount.textContent = String(count);
        const switchText = document.querySelector('#mesa-mobile-switch-text');
        if (switchText) switchText.textContent = root.dataset.activeTab === 'menu'
            ? `Xem Order (${count})` : 'Xem thực đơn';

        const kitchen = panel.querySelector('form[data-kitchen-action]');
        const disabledKitchen = panel.querySelector('[data-kitchen-disabled]');
        const payment = panel.querySelector('a[data-payment-action]');
        const kitchenButton = document.querySelector('#mesa-mobile-kitchen');
        const paymentButton = document.querySelector('#mesa-mobile-payment');
        const actions = document.querySelector('#mesa-mobile-actions');
        if (kitchenButton) {
            kitchenButton.textContent = kitchen?.querySelector('button[type="submit"]')?.textContent.trim()
                || disabledKitchen?.textContent.trim() || 'Đã gửi bếp';
            kitchenButton.disabled = !kitchen;
            kitchenButton.classList.toggle('opacity-50', !kitchen);
        }
        if (paymentButton) {
            paymentButton.hidden = !payment;
            paymentButton.textContent = payment?.textContent.trim() || 'Thanh toán';
        }
        if (actions) {
            const show = root.dataset.activeTab === 'order' && !!panel.dataset.orderId && (!!kitchen || !!payment);
            actions.classList.toggle('hidden', !show);
            actions.classList.toggle('grid', show);
        }
    }

    function subscribeOrderRealtime() {
        if (!window.Echo) return;
        const orderId = document.querySelector('#order-panel')?.dataset.orderId;
        if (!orderId || String(orderId) === String(currentRealtimeOrderId)) return;
        if (currentRealtimeOrderId) window.Echo.leave('orders.' + currentRealtimeOrderId);
        currentRealtimeOrderId = orderId;
        window.Echo.channel('orders.' + orderId)
            .listen('.kitchen.item.status.changed', async (event) => {
                console.log('Kitchen realtime:', event);
                try { await refreshOrderPanel(); }
                catch (error) { console.error('Realtime Order refresh error:', error); }
            });
    }

    function documentFromHtml(html) {
        return new DOMParser().parseFromString(html, 'text/html');
    }

    async function refreshOrderPanel() {
        const response = await fetch(window.location.href, {
            credentials: 'same-origin', headers: { 'Accept': 'text/html' }
        });
        if (!response.ok) throw new Error('HTTP error: ' + response.status);
        const doc = documentFromHtml(await response.text());
        const fresh = doc.querySelector('#order-panel');
        const current = document.querySelector('#order-panel');
        if (!fresh || !current) throw new Error('Không tìm thấy #order-panel');
        const oldList = current.querySelector('.mesa-scroll');
        const oldScrollTop = oldList?.scrollTop || 0;
        current.replaceWith(fresh);
        const newList = fresh.querySelector('.mesa-scroll');
        if (newList) newList.scrollTop = oldScrollTop;
        subscribeOrderRealtime();
        syncMobileBar();
    }

    // Event delegation: vẫn hoạt động sau khi #menu-panel / #order-panel được thay bằng AJAX.
    document.addEventListener('input', (event) => {
        if (event.target.matches('#menu-panel [data-menu-search]')) {
            searchQuery = event.target.value;
            filterMenu();
        }
    });
    document.addEventListener('click', (event) => {
        const category = event.target.closest('#menu-panel [data-menu-category]');
        if (category) { activeCategory = category.dataset.menuCategory; filterMenu(); }
        const tab = event.target.closest('[data-mobile-tab]');
        if (tab) setMobileTab(tab.dataset.mobileTab);
        if (event.target.closest('#mesa-mobile-switch')) {
            setMobileTab(root.dataset.activeTab === 'menu' ? 'order' : 'menu', true);
        }
        if (event.target.closest('#mesa-mobile-kitchen')) {
            document.querySelector('#order-panel form[data-kitchen-action]')?.requestSubmit();
        }
        if (event.target.closest('#mesa-mobile-payment')) {
            const payment = document.querySelector('#order-panel a[data-payment-action]');
            if (payment) window.location.assign(payment.href);
        }
    });

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-order-ajax]');
        if (!form || !root.contains(form)) return;
        event.preventDefault();
        if (form.dataset.submitting === 'true') return;
        form.dataset.submitting = 'true';
        const button = form.querySelector('button[type="submit"]');
        if (button) { button.disabled = true; button.classList.add('opacity-60', 'cursor-wait'); }
        try {
            const response = await fetch(form.action, {
                method: form.method || 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                redirect: 'follow',
                headers: { 'Accept': 'text/html' }
            });
            if (!response.ok) throw new Error('HTTP error: ' + response.status);
            const doc = documentFromHtml(await response.text());
            const newMenu = doc.querySelector('#menu-panel');
            const newOrder = doc.querySelector('#order-panel');
            const currentMenu = document.querySelector('#menu-panel');
            const currentOrder = document.querySelector('#order-panel');
            if (!newMenu || !newOrder || !currentMenu || !currentOrder) throw new Error('Không tìm thấy menu-panel hoặc order-panel');
            const oldOrderScroll = currentOrder.querySelector('.mesa-scroll')?.scrollTop || 0;
            currentMenu.replaceWith(newMenu);
            currentOrder.replaceWith(newOrder);
            const list = newOrder.querySelector('.mesa-scroll');
            if (list) list.scrollTop = oldOrderScroll;
            filterMenu();
            subscribeOrderRealtime();
            syncMobileBar();
        } catch (error) {
            console.error(error);
            alert('Có lỗi khi cập nhật Order. Vui lòng thử lại.');
        } finally {
            form.dataset.submitting = 'false';
            if (button) { button.disabled = false; button.classList.remove('opacity-60', 'cursor-wait'); }
        }
    });

    filterMenu();
    syncMobileBar();
    // Echo được import qua Vite; đợi full load như flow đã test hoạt động trước đây.
    window.addEventListener('load', subscribeOrderRealtime);
})();
</script>
@endsection
