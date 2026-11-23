@php
    $allTables = $tables->flatten();
    $tableStatusValue = fn ($table) => $table->status instanceof \App\Enums\TableStatus
        ? $table->status->value
        : (string) $table->status;
    $statusLabels = [
        'available' => 'Trống',
        'occupied' => 'Đang dùng',
        'reserved' => 'Đã đặt',
        'cleaning' => 'Cần dọn',
    ];
    $statusDefinitions = [
        ['key' => 'available', 'label' => 'Trống', 'icon' => 'M5.25 6.75h13.5M5.25 12h13.5M5.25 17.25h13.5'],
        ['key' => 'occupied', 'label' => 'Đang dùng', 'icon' => 'M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0'],
        ['key' => 'reserved', 'label' => 'Đã đặt', 'icon' => 'M8.25 6.75h7.5M8.25 10.5h7.5m-7.5 3.75h4.5M6.75 3.75h10.5A2.25 2.25 0 0 1 19.5 6v12a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 18V6a2.25 2.25 0 0 1 2.25-2.25Z'],
        ['key' => 'cleaning', 'label' => 'Cần dọn', 'icon' => 'M4.5 19.5h15m-13.5-3 7.5-12 7.5 12h-15Z'],
    ];
    $statusCounts = $allTables->countBy(fn ($table) => $tableStatusValue($table));
    $selectedTable = $allTables->first();
    $selectedTableStatus = $selectedTable ? $tableStatusValue($selectedTable) : null;
@endphp

@extends('layouts.auth')

@section('title', 'Bàn phục vụ | MESA')
@section('heading', 'Bàn phục vụ')

@section('content')
    <div class="mx-auto max-w-[1600px] space-y-6" data-table-manager>
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="mb-2 inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Điểm bán hàng
                </div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">Bàn phục vụ</h1>
                <p class="mt-1.5 text-sm text-slate-500">Theo dõi bàn và thao tác phục vụ tại chi nhánh.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if (isset($branches) && $branches->count() > 1)
                    <form method="GET" action="{{ route('tables.index') }}" class="inline-flex items-center">
                        <select name="branch_id" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600">
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected($branch->id === $currentBranchId)>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
                <button type="button" data-refresh-tables class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-wait disabled:opacity-60">
                    <svg data-refresh-icon class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 12a8.25 8.25 0 0 1-14.09 5.84M3.75 12A8.25 8.25 0 0 1 17.84 6.16M3.75 16.5v-4.5h4.5m12-4.5v4.5h-4.5" /></svg>
                    Làm mới
                </button>
            </div>
        </section>

        <section class="grid grid-cols-2 gap-3 xl:grid-cols-4" aria-label="Thống kê trạng thái bàn">
            @foreach ($statusDefinitions as $definition)
                @php
                    $statStyles = [
                        'available' => ['icon' => 'bg-emerald-100 text-emerald-900', 'value' => 'text-emerald-900'],
                        'occupied' => ['icon' => 'bg-slate-200 text-slate-900', 'value' => 'text-slate-900'],
                        'reserved' => ['icon' => 'bg-sky-100 text-sky-900', 'value' => 'text-sky-900'],
                        'cleaning' => ['icon' => 'bg-amber-100 text-amber-900', 'value' => 'text-amber-900'],
                    ];
                    $statStyle = $statStyles[$definition['key']];
                @endphp
                <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium text-slate-500 sm:text-sm">{{ $definition['label'] }}</p>
                            <p data-status-count="{{ $definition['key'] }}" class="mt-2 text-2xl font-semibold tracking-tight {{ $statStyle['value'] }}">{{ $statusCounts->get($definition['key'], 0) }}</p>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $statStyle['icon'] }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $definition['icon'] }}" /></svg>
                        </span>
                    </div>
                    <p class="mt-2 text-xs text-slate-500"><span data-status-summary="{{ $definition['key'] }}">{{ $statusCounts->get($definition['key'], 0) }}</span> bàn trong chi nhánh</p>
                </article>
            @endforeach
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-4 sm:p-5">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex min-w-0 items-center gap-1 overflow-x-auto pb-1" role="tablist" aria-label="Lọc theo khu vực">
                        <button type="button" data-area-filter="all" aria-selected="true" class="shrink-0 rounded-lg bg-slate-900 px-3.5 py-2 text-sm font-semibold text-white transition">Tất cả khu vực</button>
                        @foreach ($areas as $area)
                            <button type="button" data-area-filter="{{ $area->id }}" aria-selected="false" class="shrink-0 rounded-lg px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                                {{ $area->name }}
                            </button>
                        @endforeach
                        @if ($allTables->contains(fn ($table) => $table->area_id === null))
                            <button type="button" data-area-filter="unassigned" aria-selected="false" class="shrink-0 rounded-lg px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">Khu vực chung</button>
                        @endif
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <label class="relative block min-w-0 sm:w-64">
                            <span class="sr-only">Tìm bàn theo mã</span>
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10.75" cy="10.75" r="6.75" /><path stroke-linecap="round" d="m16 16 4.25 4.25" /></svg>
                            <input type="search" data-table-search placeholder="Tìm mã bàn..." class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-500 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10">
                        </label>
                        <select data-status-filter aria-label="Lọc theo trạng thái" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm font-medium text-slate-800 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10">
                            <option value="all">Mọi trạng thái</option>
                            @foreach ($statusLabels as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="grid gap-0 2xl:grid-cols-[minmax(0,1fr)_320px]">
                <div class="min-w-0 p-4 sm:p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">Danh sách bàn</h2>
                            <p class="mt-0.5 text-xs text-slate-500"><span data-visible-table-count>{{ $allTables->count() }}</span> / {{ $allTables->count() }} bàn hiển thị</p>
                        </div>
                        <div class="hidden items-center gap-3 text-[11px] text-slate-500 sm:flex">
                            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Trống</span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-slate-500"></span>Đang dùng</span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-sky-500"></span>Đã đặt</span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Cần dọn</span>
                        </div>
                    </div>

                    @forelse ($tables as $areaName => $areaTables)
                        @php
                            $sectionAreaId = $areaTables->first()?->area_id;
                        @endphp
                        <section data-area-section="{{ $sectionAreaId ?? 'unassigned' }}" class="mb-7 last:mb-0">
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-slate-800">{{ $areaName }}</h3>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">{{ $areaTables->count() }} bàn</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                                @foreach ($areaTables as $table)
                                    @include('tables.pos.partials.table-card', [
                                        'table' => $table,
                                        'selected' => $selectedTable?->id === $table->id,
                                        'openSession' => $openSessionsByTableId->get($table->id),
                                        'orderSummary' => $tableOrderSummariesByTableId->get($table->id),
                                        'transferHistory' => $tableTransferHistoryByTableId->get($table->id, collect()),
                                    ])
                                @endforeach
                            </div>
                        </section>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 px-5 py-12 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5V6.75A2.25 2.25 0 0 1 6.75 4.5h10.5a2.25 2.25 0 0 1 2.25 2.25v3.75M3.75 19.5v-6A2.25 2.25 0 0 1 6 11.25h12a2.25 2.25 0 0 1 2.25 2.25v6M3.75 15h16.5" /></svg>
                            </div>
                            <h3 class="mt-4 text-sm font-semibold text-slate-900">Chưa có bàn trong sơ đồ</h3>
                            <p class="mt-1 text-sm text-slate-500">Hiện chưa có bàn nào được cấu hình cho chi nhánh này.</p>
                        </div>
                    @endforelse

                    <div data-no-results class="hidden rounded-2xl border border-dashed border-slate-200 px-5 py-10 text-center">
                        <p class="text-sm font-semibold text-slate-700">Không tìm thấy bàn phù hợp</p>
                        <p class="mt-1 text-xs text-slate-500">Thử đổi khu vực, trạng thái hoặc từ khóa tìm kiếm.</p>
                    </div>
                </div>

                <aside class="border-t border-slate-200 bg-slate-100 p-4 sm:p-5 2xl:border-l 2xl:border-t-0" aria-label="Chi tiết bàn đang chọn">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">Chi tiết bàn</h2>
                            <p class="mt-0.5 text-xs text-slate-500">Chọn một bàn để xem thông tin.</p>
                        </div>
                        <span class="rounded-lg bg-white p-2 text-slate-400 ring-1 ring-slate-200">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 11v5m0-8h.01" /></svg>
                        </span>
                    </div>

                    <div data-table-detail @class(['hidden' => !$selectedTable])>
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3">
                                <div>
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Chi tiết bàn đang chọn</p>
                                    <h3 data-detail-code class="mt-1 text-xl font-semibold tracking-tight text-slate-950">{{ $selectedTable?->code ?? '—' }}</h3>
                                </div>
                                <span data-detail-status class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold">
                                    <span data-detail-status-label>{{ $statusLabels[$selectedTableStatus] ?? 'Chưa chọn' }}</span>
                                </span>
                            </div>
                            <div class="p-4">
                            <div data-detail-session class="{{ $openSessionsByTableId->has($selectedTable?->id ?? 0) ? '' : 'hidden ' }}rounded-xl border border-emerald-200 bg-emerald-50 p-3.5">
                                <p class="text-sm font-semibold text-emerald-950">Phiên #<span data-detail-session-id>{{ $openSessionsByTableId->get($selectedTable?->id ?? 0)?->id }}</span></p>
                                <p class="mt-1 text-xs text-emerald-900"><span data-detail-guest-count>{{ $openSessionsByTableId->get($selectedTable?->id ?? 0)?->guest_count }}</span> khách · Nhân viên: <span data-detail-employee>{{ $openSessionsByTableId->get($selectedTable?->id ?? 0)?->openedByUser?->full_name ?? '—' }}</span></p>
                                <button type="button" data-order-session class="mt-3 w-full rounded-lg bg-blue-600 px-3 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">Gọi món</button>
                                <div class="mt-2 grid grid-cols-2 gap-2">
                                    <button type="button" data-update-session class="rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs font-semibold text-slate-800">Cập nhật</button>
                                    <button type="button" data-merge-session class="rounded-lg border border-sky-300 bg-white px-2.5 py-2 text-xs font-semibold text-sky-900">Gộp bàn</button>
                                    <button type="button" data-transfer-table class="rounded-lg border border-emerald-300 bg-white px-2.5 py-2 text-xs font-semibold text-emerald-950">Chuyển bàn</button>
                                    <button type="button" data-close-session class="rounded-lg bg-slate-800 px-2.5 py-2 text-xs font-semibold text-white">Đóng phiên</button>
                                </div>
                                <p data-session-error class="mt-2 hidden text-xs font-medium text-rose-700" role="alert"></p>
                            </div>
                            <div data-empty-session class="{{ $openSessionsByTableId->has($selectedTable?->id ?? 0) ? 'hidden ' : '' }}">
                                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3.5">
                                    <p class="text-sm font-semibold text-slate-900">Bàn <span data-empty-code>{{ $selectedTable?->code ?? '—' }}</span> đang sẵn sàng</p>
                                    <p class="mt-1 text-xs text-slate-500">Sức chứa tiêu chuẩn: <span data-empty-seats>{{ $selectedTable?->seats ?? '—' }}</span> chỗ</p>
                                    <button type="button" data-open-table class="{{ $selectedTable && $selectedTable->status === \App\Enums\TableStatus::AVAILABLE ? '' : 'hidden ' }}mt-3 w-full rounded-lg bg-emerald-700 px-3 py-2.5 text-sm font-semibold text-white">Mở phiên phục vụ mới</button>
                                </div>
                            </div>
                            <div data-clean-session class="{{ $selectedTable && $selectedTable->status === \App\Enums\TableStatus::CLEANING ? '' : 'hidden ' }}rounded-xl border border-amber-200 bg-amber-50 p-4 text-center">
                                <p class="text-sm font-semibold text-slate-900">Bàn cần dọn</p>
                                <p class="mt-1 text-xs text-slate-500">Xác nhận khi nhân viên đã dọn bàn xong.</p>
                                <button type="button" data-clean-table class="mt-3 w-full rounded-lg bg-amber-600 px-3 py-2.5 text-sm font-semibold text-white">Xác nhận đã dọn xong</button>
                            </div>
                            <div data-order-summary class="{{ $openSessionsByTableId->has($selectedTable?->id ?? 0) ? '' : 'hidden ' }}mt-4 border-t border-slate-200 pt-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-800">Món đã gọi</p>
                                <div data-order-summary-content class="mt-2 space-y-2">
                                    @php
                                        $selectedOrder = $tableOrderSummariesByTableId->get($selectedTable?->id);
                                    @endphp
                                    @if ($selectedOrder)
                                        @php
                                            $remaining = max(0, (float) $selectedOrder['total'] - (float) $selectedOrder['paid']);
                                        @endphp
                                        <p class="text-xs text-slate-500">{{ $selectedOrder['number'] }}</p>
                                        <p class="text-xs font-semibold {{ $remaining === 0.0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                            {{ $remaining === 0.0 ? 'Đã thanh toán đủ' : 'Còn phải trả '.number_format($remaining, 0, ',', '.').'đ' }}
                                        </p>
                                        <p class="text-xs text-slate-500">Tổng order: {{ number_format($selectedOrder['total'], 0, ',', '.') }}đ</p>
                                        @forelse ($selectedOrder['items'] as $item)
                                            <div class="rounded-lg bg-slate-50 px-2.5 py-2 text-xs text-slate-700">
                                                <span class="font-semibold">{{ $item['quantity'] }}x {{ $item['name'] }}</span>
                                                @if ($item['variant'])
                                                    <span class="text-slate-500"> · {{ $item['variant'] }}</span>
                                                @endif
                                                <span class="float-right font-medium">{{ number_format($item['line_total'], 0, ',', '.') }}đ</span>
                                                @if ($item['note'])
                                                    <span class="mt-1 block text-slate-500">Ghi chú: {{ $item['note'] }}</span>
                                                @endif
                                            </div>
                                        @empty
                                            <p class="text-xs text-slate-500">Chưa có món nào trong order.</p>
                                        @endforelse
                                    @else
                                        <p class="text-xs text-slate-500">Phiên chưa có order.</p>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-5 divide-y divide-slate-200 border-t border-slate-200">
                                <div class="flex items-center justify-between gap-3 py-3 text-sm">
                                    <span class="text-slate-500">Khu vực</span>
                                    <span data-detail-area class="text-right font-medium text-slate-800">{{ $selectedTable?->area?->name ?? '—' }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3 py-3 text-sm">
                                    <span class="text-slate-500">Số chỗ ngồi</span>
                                    <span data-detail-seats class="font-medium text-slate-800">{{ $selectedTable?->seats ?? '—' }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3 py-3 text-sm">
                                    <span class="text-slate-500">Trạng thái</span>
                                    <span data-detail-status-text class="font-medium text-slate-800">{{ $statusLabels[$selectedTableStatus] ?? '—' }}</span>
                                </div>
                            </div>
                            </div>
                        </div>
                    </div>

                    <div data-detail-empty @class(['hidden' => (bool) $selectedTable]) class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-10 text-center">
                        <p class="text-sm font-medium text-slate-600">Chưa chọn bàn</p>
                        <p class="mt-1 text-xs text-slate-400">Thông tin sẽ hiển thị tại đây.</p>
                    </div>
                </aside>
            </div>
        </section>
    </div>

    @include('tables.pos.partials.open-session-modal')
    @include('tables.pos.partials.transfer-session-modal')
    @include('tables.pos.partials.update-session-modal')
    @include('tables.pos.partials.merge-session-modal')
    @include('tables.pos.partials.close-session-modal')

    @push('scripts')
        <script>
            (() => {
                const manager = document.querySelector('[data-table-manager]');
                if (!manager) return;

                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                const cards = [...manager.querySelectorAll('[data-table-card]')];
                const areaFilters = [...manager.querySelectorAll('[data-area-filter]')];
                const searchInput = manager.querySelector('[data-table-search]');
                const statusFilter = manager.querySelector('[data-status-filter]');
                const detail = manager.querySelector('[data-table-detail]');
                const detailEmpty = manager.querySelector('[data-detail-empty]');
                const openModal = document.querySelector('[data-modal="open-session"]');
                const transferModal = document.querySelector('[data-modal="transfer-session"]');
                const updateModal = document.querySelector('[data-modal="update-session"]');
                const mergeModal = document.querySelector('[data-modal="merge-session"]');
                const closeModal = document.querySelector('[data-modal="close-session"]');
                const openSessionForm = openModal.querySelector('[data-open-session-form]');
                const transferForm = transferModal.querySelector('[data-transfer-form]');
                const transferTarget = transferForm.elements.namedItem('target_table_id');
                const statusLabels = {
                    available: 'Trống',
                    occupied: 'Đang dùng',
                    reserved: 'Đã đặt',
                    cleaning: 'Cần dọn',
                };
                const statusClasses = {
                    available: ['border-emerald-300', 'bg-emerald-100', 'text-emerald-900'],
                    occupied: ['border-slate-300', 'bg-slate-200', 'text-slate-900'],
                    reserved: ['border-sky-300', 'bg-sky-100', 'text-sky-900'],
                    cleaning: ['border-amber-300', 'bg-amber-100', 'text-amber-900'],
                };
                const dotClasses = {
                    available: 'bg-emerald-500',
                    occupied: 'bg-slate-500',
                    reserved: 'bg-sky-500',
                    cleaning: 'bg-amber-500',
                };
                let selectedCard = cards[0] || null;
                let activeArea = 'all';

                const showModal = (modal) => {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.classList.add('overflow-hidden');
                    modal.querySelector('input:not([type="hidden"]), select, button')?.focus();
                };

                const hideModal = (modal) => {
                    if (!modal) return;
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    if ([openModal, transferModal, updateModal, mergeModal, closeModal].every((item) => item.classList.contains('hidden'))) {
                        document.body.classList.remove('overflow-hidden');
                    }
                };

                const setError = (element, message = '') => {
                    element.textContent = message;
                    element.classList.toggle('hidden', !message);
                };

                const statusBadgeClasses = (status) => statusClasses[status] || statusClasses.occupied;

                const updateCardStatus = (card, status, openSession = null) => {
                    card.dataset.status = status;
                    const badge = card.querySelector('[data-card-status]');
                    const dot = card.querySelector('[data-card-status-dot]');
                    const label = card.querySelector('[data-card-status-label]');
                    card.dataset.sessionId = openSession?.id || '';
                    card.dataset.sessionGuestCount = openSession?.guest_count || '';
                    card.dataset.sessionEmployee = openSession?.employee_name || '';
                    const sessionLine = card.querySelector('[data-card-session]');
                    sessionLine.classList.toggle('hidden', !openSession);
                    card.querySelector('[data-card-session-id]').textContent = openSession?.id || '';
                    card.querySelector('[data-card-session-guest-count]').textContent = openSession?.guest_count || '';
                    badge.classList.remove(...Object.values(statusClasses).flat());
                    badge.classList.add(...statusBadgeClasses(status));
                    dot.className = `h-1.5 w-1.5 rounded-full ${dotClasses[status] || 'bg-slate-500'}`;
                    label.textContent = statusLabels[status] || 'Không rõ';
                };

                const updateCounts = () => {
                    const counts = { available: 0, occupied: 0, reserved: 0, cleaning: 0 };
                    cards.forEach((card) => {
                        if (Object.hasOwn(counts, card.dataset.status)) counts[card.dataset.status]++;
                    });
                    Object.entries(counts).forEach(([status, count]) => {
                        manager.querySelector(`[data-status-count="${status}"]`).textContent = count;
                        manager.querySelector(`[data-status-summary="${status}"]`).textContent = count;
                    });
                };

                const updateDetail = (card) => {
                    selectedCard = card;
                    cards.forEach((item) => {
                        const selected = item === card;
                        item.querySelector('[data-card-select]').setAttribute('aria-pressed', String(selected));
                        item.classList.toggle('border-emerald-600', selected);
                        item.classList.toggle('bg-emerald-50', selected);
                        item.classList.toggle('ring-2', selected);
                        item.classList.toggle('ring-emerald-200', selected);
                        if (!selected) {
                            item.classList.remove('border-emerald-600', 'bg-emerald-50', 'ring-2', 'ring-emerald-200');
                            item.classList.add('border-slate-300', 'bg-white');
                        } else {
                            item.classList.remove('border-slate-300', 'bg-white');
                        }
                    });

                    if (!card) {
                        detail.classList.add('hidden');
                        detailEmpty.classList.remove('hidden');
                        return;
                    }

                    detail.classList.remove('hidden');
                    detailEmpty.classList.add('hidden');
                    detail.querySelector('[data-detail-code]').textContent = card.dataset.code;
                    detail.querySelector('[data-detail-area]').textContent = card.dataset.areaName;
                    detail.querySelector('[data-detail-seats]').textContent = card.dataset.seats;
                    detail.querySelector('[data-detail-status-label]').textContent = statusLabels[card.dataset.status] || 'Không rõ';
                    detail.querySelector('[data-detail-status-text]').textContent = statusLabels[card.dataset.status] || 'Không rõ';
                    const badge = detail.querySelector('[data-detail-status]');
                    badge.className = `inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold ${statusBadgeClasses(card.dataset.status).join(' ')}`;
                    const hasOpenSession = Boolean(card.dataset.sessionId);
                    detail.querySelector('[data-detail-session]').classList.toggle('hidden', !hasOpenSession);
                    detail.querySelector('[data-empty-session]').classList.toggle('hidden', card.dataset.status !== 'available');
                    detail.querySelector('[data-clean-session]').classList.toggle('hidden', card.dataset.status !== 'cleaning');
                    detail.querySelector('[data-empty-code]').textContent = card.dataset.code;
                    detail.querySelector('[data-empty-seats]').textContent = card.dataset.seats;
                    detail.querySelector('[data-detail-session-id]').textContent = card.dataset.sessionId;
                    detail.querySelector('[data-detail-guest-count]').textContent = card.dataset.sessionGuestCount;
                    detail.querySelector('[data-detail-employee]').textContent = card.dataset.sessionEmployee || '—';
                    const orderSummaryContent = detail.querySelector('[data-order-summary-content]');
                    const orderSummary = JSON.parse(card.dataset.orderSummary || 'null');
                    const orderSummaryBlock = detail.querySelector('[data-order-summary]');
                    orderSummaryBlock.classList.toggle('hidden', !hasOpenSession);
                    if (orderSummaryContent) {
                        orderSummaryContent.replaceChildren();
                        if (!orderSummary) {
                            const empty = document.createElement('p');
                            empty.className = 'text-xs text-slate-500';
                            empty.textContent = 'Phiên chưa có order.';
                            orderSummaryContent.append(empty);
                        } else {
                            const orderInfo = document.createElement('p');
                            orderInfo.className = 'text-xs text-slate-500';
                            orderInfo.textContent = orderSummary.number;
                            orderSummaryContent.append(orderInfo);
                            const remaining = Math.max(0, orderSummary.total - orderSummary.paid);
                            const paymentStatus = document.createElement('p');
                            paymentStatus.className = `text-xs font-semibold ${remaining === 0 ? 'text-emerald-700' : 'text-rose-700'}`;
                            paymentStatus.textContent = remaining === 0
                                ? 'Đã thanh toán đủ'
                                : `Còn phải trả ${new Intl.NumberFormat('vi-VN').format(remaining)}đ`;
                            orderSummaryContent.append(paymentStatus);
                            const total = document.createElement('p');
                            total.className = 'text-xs text-slate-500';
                            total.textContent = `Tổng order: ${new Intl.NumberFormat('vi-VN').format(orderSummary.total)}đ`;
                            orderSummaryContent.append(total);
                            if (orderSummary.items.length === 0) {
                                const empty = document.createElement('p');
                                empty.className = 'text-xs text-slate-500';
                                empty.textContent = 'Chưa có món nào trong order.';
                                orderSummaryContent.append(empty);
                            }
                            orderSummary.items.forEach((entry) => {
                                const item = document.createElement('div');
                                item.className = 'rounded-lg bg-slate-50 px-2.5 py-2 text-xs text-slate-700';
                                const name = document.createElement('span');
                                name.className = 'font-semibold';
                                name.textContent = `${entry.quantity}x ${entry.name}${entry.variant ? ` · ${entry.variant}` : ''}`;
                                const lineTotal = document.createElement('span');
                                lineTotal.className = 'float-right font-medium';
                                lineTotal.textContent = `${new Intl.NumberFormat('vi-VN').format(entry.line_total)}đ`;
                                item.append(name, lineTotal);
                                if (entry.note) {
                                    const note = document.createElement('span');
                                    note.className = 'mt-1 block text-slate-500';
                                    note.textContent = `Ghi chú: ${entry.note}`;
                                    item.append(note);
                                }
                                orderSummaryContent.append(item);
                            });
                        }
                    }
                    detail.querySelector('[data-open-table]').classList.toggle('hidden', card.dataset.status !== 'available');
                };

                const applyFilters = () => {
                    const query = searchInput.value.trim().toLocaleLowerCase('vi');
                    const status = statusFilter.value;
                    let visible = 0;

                    cards.forEach((card) => {
                        const matchesArea = activeArea === 'all' || card.dataset.areaId === activeArea || (activeArea === 'unassigned' && !card.dataset.areaId);
                        const matchesQuery = card.dataset.code.toLocaleLowerCase('vi').includes(query);
                        const matchesStatus = status === 'all' || card.dataset.status === status;
                        const isVisible = matchesArea && matchesQuery && matchesStatus;
                        card.classList.toggle('hidden', !isVisible);
                        if (isVisible) visible++;
                    });

                    manager.querySelector('[data-visible-table-count]').textContent = visible;
                    manager.querySelector('[data-no-results]').classList.toggle('hidden', cards.length === 0 || visible > 0);
                    manager.querySelectorAll('[data-area-section]').forEach((section) => {
                        const hasVisibleCards = [...section.querySelectorAll('[data-table-card]')].some((card) => !card.classList.contains('hidden'));
                        section.classList.toggle('hidden', !hasVisibleCards);
                    });
                };

                const readJson = async (response) => {
                    const responseText = await response.text();
                    let payload;
                    try {
                        payload = responseText ? JSON.parse(responseText) : {};
                    } catch {
                        throw new Error(`Máy chủ trả về phản hồi không hợp lệ (HTTP ${response.status}).`);
                    }

                    if (!response.ok) {
                        const validationMessage = payload.errors ? Object.values(payload.errors).flat().join(' ') : '';
                        const error = new Error(validationMessage || payload.message || `Yêu cầu không thành công (HTTP ${response.status}).`);
                        error.status = response.status;
                        throw error;
                    }
                    return payload;
                };

                const openSessionForTable = async (card) => {
                    if (!card || card.dataset.status !== 'available') return;
                    updateDetail(card);
                    openSessionForm.reset();
                    openSessionForm.elements.namedItem('guest_count').value = 2;
                    setError(openModal.querySelector('[data-open-error]'));
                    openModal.querySelector('[data-open-title]').textContent = `Mở phiên - bàn ${card.dataset.code}`;
                    showModal(openModal);
                    const employeeSelect = openSessionForm.elements.namedItem('opened_by');
                    employeeSelect.innerHTML = '<option value="">Tài khoản đang đăng nhập</option>';
                    try {
                        const response = await fetch(@json(route('api.dining-sessions.employees')), { headers: { Accept: 'application/json' } });
                        const result = await readJson(response);
                        result.data.forEach((employee) => {
                            const option = document.createElement('option');
                            option.value = employee.id;
                            option.textContent = employee.full_name;
                            employeeSelect.append(option);
                        });
                    } catch (error) {
                        setError(openModal.querySelector('[data-open-error]'), error.message);
                    }
                };

                const openTransferForTable = (card) => {
                    if (!card?.dataset.sessionId) return;
                    transferForm.reset();
                    setError(transferModal.querySelector('[data-transfer-error]'));
                    transferModal.querySelector('[data-transfer-title]').textContent = `Chuyển phiên #${card.dataset.sessionId} từ bàn ${card.dataset.code}`;
                    transferTarget.value = '';
                    transferModal.querySelector('[data-transfer-source-code]').textContent = card.dataset.code;
                    transferModal.querySelector('[data-transfer-source-info]').textContent = `${card.dataset.seats} chỗ · ${card.dataset.sessionGuestCount || 0} khách`;
                    transferModal.querySelector('[data-transfer-target-placeholder]').textContent = 'Chưa chọn';
                    transferModal.querySelector('[data-transfer-target-card]').className = 'rounded-xl border border-dashed border-slate-300 bg-slate-100 p-3';
                    const availableTables = cards.filter((item) => item !== card && item.dataset.status === 'available');
                    const grid = transferModal.querySelector('[data-transfer-target]');
                    grid.replaceChildren();
                    const grouped = availableTables.reduce((groups, table) => {
                        (groups[table.dataset.areaName] ||= []).push(table);
                        return groups;
                    }, {});
                    Object.entries(grouped).forEach(([areaName, tables]) => {
                        const section = document.createElement('div');
                        section.className = 'col-span-2 rounded-xl border border-slate-200 bg-white p-2.5 sm:col-span-3 lg:col-span-4';
                        section.innerHTML = `<p class="mb-2 text-xs font-bold text-slate-700">${areaName} · ${tables.length} bàn trống</p>`;
                        const tableGrid = document.createElement('div');
                        tableGrid.className = 'grid grid-cols-2 gap-2 sm:grid-cols-3';
                        tables.forEach((table) => {
                            const button = document.createElement('button');
                            button.type = 'button';
                            button.dataset.targetTableId = table.dataset.tableId;
                            button.className = 'rounded-xl border border-slate-200 bg-slate-50 p-3 text-left';
                            button.innerHTML = `<span class="block text-sm font-bold text-slate-950">${table.dataset.code}</span><span class="mt-1 block text-xs text-slate-500">${table.dataset.seats} chỗ</span>`;
                            button.addEventListener('click', () => {
                                transferTarget.value = table.dataset.tableId;
                                grid.querySelectorAll('[data-target-table-id]').forEach((item) => item.className = 'rounded-xl border border-slate-200 bg-slate-50 p-3 text-left');
                                button.className = 'rounded-xl border-2 border-orange-400 bg-orange-50 p-3 text-left';
                                transferModal.querySelector('[data-transfer-target-card]').className = 'rounded-xl border-2 border-orange-400 bg-orange-50 p-3';
                                transferModal.querySelector('[data-transfer-target-placeholder]').textContent = `${table.dataset.code} · ${table.dataset.seats} chỗ`;
                                transferModal.querySelector('[data-transfer-submit]').disabled = false;
                            });
                            tableGrid.append(button);
                        });
                        section.append(tableGrid);
                        grid.append(section);
                    });
                    transferModal.querySelector('[data-transfer-no-target]').classList.toggle('hidden', availableTables.length > 0);
                    transferModal.querySelector('[data-transfer-submit]').disabled = availableTables.length === 0;
                    showModal(transferModal);
                };

                areaFilters.forEach((button) => button.addEventListener('click', () => {
                    activeArea = button.dataset.areaFilter;
                    areaFilters.forEach((item) => {
                        const active = item === button;
                        item.setAttribute('aria-selected', String(active));
                        item.className = `shrink-0 rounded-lg px-3.5 py-2 text-sm transition ${
                            active
                                ? 'bg-slate-900 font-semibold text-white'
                                : 'font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                        }`;
                    });
                    applyFilters();
                }));

                searchInput.addEventListener('input', applyFilters);
                statusFilter.addEventListener('change', applyFilters);
                cards.forEach((card) => {
                    card.querySelector('[data-card-select]').addEventListener('click', () => updateDetail(card));
                });

                manager.querySelector('[data-open-table]')?.addEventListener('click', () => openSessionForTable(selectedCard));
                detail.querySelector('[data-clean-table]')?.addEventListener('click', async () => {
                    if (!selectedCard || selectedCard.dataset.status !== 'cleaning') return;
                    if (!window.confirm(`Xác nhận bàn ${selectedCard.dataset.code} đã dọn xong?`)) return;
                    try {
                        const response = await fetch(`/api/tables/${selectedCard.dataset.tableId}/cleaned`, {
                            method: 'POST',
                            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        });
                        await readJson(response);
                        window.location.reload();
                    } catch (error) {
                        setError(detail.querySelector('[data-session-error]'), error.message);
                    }
                });
                detail.querySelector('[data-order-session]')?.addEventListener('click', () => {
    if (!selectedCard?.dataset.sessionId) return;

    window.location.href = `/orders/session/${selectedCard.dataset.sessionId}`;
});
                detail.querySelector('[data-transfer-table]').addEventListener('click', () => openTransferForTable(selectedCard));
                detail.querySelector('[data-close-session]').addEventListener('click', async (event) => {
                    if (!selectedCard?.dataset.sessionId) return;
                    closeModal.querySelector('[data-close-title]').textContent = `Phiên #${selectedCard.dataset.sessionId} · Bàn ${selectedCard.dataset.code}`;
                    closeModal.querySelector('[data-close-session-form]').reset();
                    setError(closeModal.querySelector('[data-close-error]'));
                    showModal(closeModal);
                });
                closeModal.querySelector('[data-close-session-form]').addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const button = closeModal.querySelector('[data-close-submit]');
                    button.disabled = true;
                    try {
                        const response = await fetch(`/api/tables/${selectedCard.dataset.tableId}/dining-session/close`, {
                            method: 'POST',
                            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                            body: JSON.stringify({ reason: 'Khách đã thanh toán và rời bàn' }),
                        });
                        await readJson(response);
                        window.location.reload();
                    } catch (error) {
                        setError(closeModal.querySelector('[data-close-error]'), error.message);
                    } finally {
                        button.disabled = false;
                    }
                });

                detail.querySelector('[data-update-session]').addEventListener('click', async () => {
                    if (!selectedCard?.dataset.sessionId) return;
                    const form = updateModal.querySelector('[data-update-session-form]');
                    form.reset();
                    updateModal.querySelector('[data-update-title]').textContent = `Phiên #${selectedCard.dataset.sessionId} · Bàn ${selectedCard.dataset.code}`;
                    form.elements.namedItem('guest_count').value = selectedCard.dataset.sessionGuestCount || 1;
                    form.elements.namedItem('note').value = selectedCard.dataset.sessionNote || '';
                    const employeeSelect = form.elements.namedItem('opened_by');
                    employeeSelect.replaceChildren();
                    try {
                        const response = await fetch('/api/dining-sessions/employees', { headers: { Accept: 'application/json' } });
                        const payload = await readJson(response);
                        payload.data.forEach((employee) => employeeSelect.append(new Option(employee.full_name, employee.id)));
                        if (selectedCard.dataset.sessionOpenedBy) employeeSelect.value = selectedCard.dataset.sessionOpenedBy;
                    } catch (error) {
                        setError(updateModal.querySelector('[data-update-error]'), error.message);
                    }
                    showModal(updateModal);
                });
                updateModal.querySelector('[data-update-session-form]').addEventListener('submit', async (event) => {
                    event.preventDefault();
                    if (!selectedCard?.dataset.sessionId) return;
                    const form = event.currentTarget;
                    const button = updateModal.querySelector('[data-update-submit]');
                    button.disabled = true;
                    setError(updateModal.querySelector('[data-update-error]'));
                    const submit = async (allowOvercapacity = false) => {
                        const response = await fetch(`/api/dining-sessions/${selectedCard.dataset.sessionId}`, {
                            method: 'PUT',
                            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                            body: JSON.stringify({
                                guest_count: Number(form.elements.namedItem('guest_count').value),
                                opened_by: Number(form.elements.namedItem('opened_by').value) || null,
                                note: form.elements.namedItem('note').value,
                                allow_overcapacity: allowOvercapacity,
                            }),
                        });
                        return readJson(response);
                    };
                    try {
                        try {
                            await submit(false);
                        } catch (error) {
                            if (error.status === 422 && window.confirm(error.message + ' Bạn có muốn tiếp tục vượt sức chứa?')) {
                                await submit(true);
                            } else {
                                throw error;
                            }
                        }
                        window.location.reload();
                    } catch (error) {
                        setError(updateModal.querySelector('[data-update-error]'), error.message);
                    } finally {
                        button.disabled = false;
                    }
                });

                detail.querySelector('[data-merge-session]').addEventListener('click', () => {
                    if (!selectedCard?.dataset.sessionId) return;
                    const target = mergeModal.querySelector('[data-merge-target]');
                    target.replaceChildren();
                    const availableTables = cards.filter((item) => item !== selectedCard && item.dataset.status === 'available');
                    const grouped = availableTables.reduce((groups, table) => {
                        (groups[table.dataset.areaName] ||= []).push(table);
                        return groups;
                    }, {});
                    Object.entries(grouped).forEach(([areaName, tables]) => {
                        const section = document.createElement('div');
                        section.className = 'col-span-2 rounded-xl border border-slate-200 bg-white p-2.5 sm:col-span-3';
                        section.innerHTML = `<p class="mb-2 text-xs font-bold text-slate-700">${areaName} · ${tables.length} bàn trống</p>`;
                        const grid = document.createElement('div');
                        grid.className = 'grid grid-cols-2 gap-2';
                        tables.forEach((table) => {
                            const label = document.createElement('label');
                            label.className = 'cursor-pointer';
                            const checkbox = document.createElement('input');
                            checkbox.type = 'checkbox';
                            checkbox.name = 'target_table_ids[]';
                            checkbox.value = table.dataset.tableId;
                            checkbox.dataset.seats = table.dataset.seats;
                            checkbox.className = 'peer sr-only';
                            const card = document.createElement('span');
                            card.className = 'flex min-h-[82px] flex-col justify-between rounded-xl border border-slate-200 bg-slate-50 p-3 transition peer-checked:border-emerald-400 peer-checked:bg-emerald-50 peer-checked:ring-1 peer-checked:ring-emerald-200';
                            card.innerHTML = `
                                <span class="flex items-start justify-between gap-2">
                                    <span class="text-sm font-bold text-slate-950">${table.dataset.code}</span>
                                    <span data-merge-card-badge class="rounded-md border border-emerald-200 bg-emerald-100 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700">Trống</span>
                                </span>
                                <span class="text-xs text-slate-500">${table.dataset.seats} chỗ</span>
                            `;
                            checkbox.addEventListener('change', () => {
                                const selected = [...target.querySelectorAll('input:checked')];
                                const seats = selected.reduce((sum, item) => sum + Number(item.dataset.seats || 0), 0);
                                const badge = card.querySelector('[data-merge-card-badge]');
                                badge.textContent = checkbox.checked ? 'Đã chọn' : 'Trống';
                                badge.className = checkbox.checked
                                    ? 'rounded-md border border-emerald-300 bg-emerald-600 px-1.5 py-0.5 text-[9px] font-bold text-white'
                                    : 'rounded-md border border-emerald-200 bg-emerald-100 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700';
                                mergeModal.querySelector('[data-merge-source-badge]').textContent = `${selectedCard.dataset.code}: ${selectedCard.dataset.seats} chỗ`;
                                mergeModal.querySelector('[data-merge-selected-count]').textContent = selected.length ? `${selected.length} bàn · ${seats} chỗ` : 'Chưa chọn bàn';
                                mergeModal.querySelector('[data-merge-capacity]').textContent = `Khách hiện tại: ${selectedCard.dataset.sessionGuestCount || 0} · Tổng sức chứa: ${Number(selectedCard.dataset.seats || 0) + seats}`;
                                mergeModal.querySelector('[data-merge-submit]').disabled = selected.length === 0;
                            });
                            label.append(checkbox, card);
                            grid.append(label);
                        });
                        section.append(grid);
                        target.append(section);
                    });
                    mergeModal.querySelector('[data-merge-title]').textContent = `Phiên #${selectedCard.dataset.sessionId} · Bàn ${selectedCard.dataset.code}`;
                    mergeModal.querySelector('[data-merge-source-badge]').textContent = `${selectedCard.dataset.code}: ${selectedCard.dataset.seats} chỗ`;
                    mergeModal.querySelector('[data-merge-capacity]').textContent = `Khách hiện tại: ${selectedCard.dataset.sessionGuestCount || 0} · Tổng sức chứa: ${selectedCard.dataset.seats || 0}`;
                    mergeModal.querySelector('[data-merge-submit]').disabled = true;
                    showModal(mergeModal);
                });
                mergeModal.querySelector('[data-merge-session-form]').addEventListener('submit', async (event) => {
                    event.preventDefault();
                    if (!selectedCard?.dataset.sessionId) return;
                    const target = mergeModal.querySelector('[data-merge-target]');
                    const targetIds = [...target.querySelectorAll('input[type="checkbox"]:checked')].map((checkbox) => Number(checkbox.value));
                    if (targetIds.length === 0) return;
                    const button = mergeModal.querySelector('[data-merge-submit]');
                    button.disabled = true;
                    const submit = async (allowOvercapacity = false) => {
                        const response = await fetch(`/api/tables/${selectedCard.dataset.tableId}/dining-session/merge`, {
                            method: 'POST',
                            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                            body: JSON.stringify({ target_table_ids: targetIds, allow_overcapacity: allowOvercapacity }),
                        });
                        return readJson(response);
                    };
                    try {
                        try {
                            await submit(false);
                        } catch (error) {
                            if (error.status === 422 && window.confirm(error.message + ' Bạn có muốn gộp vượt sức chứa?')) {
                                await submit(true);
                            } else {
                                throw error;
                            }
                        }
                        window.location.reload();
                    } catch (error) {
                        setError(mergeModal.querySelector('[data-merge-error]'), error.message);
                    } finally {
                        button.disabled = targetIds.length === 0;
                    }
                });

                transferForm.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    if (!selectedCard?.dataset.sessionId || !transferTarget.value) return;
                    const submitButton = transferModal.querySelector('[data-transfer-submit]');
                    submitButton.disabled = true;
                    setError(transferModal.querySelector('[data-transfer-error]'));
                    try {
                        const response = await fetch(`/api/tables/${selectedCard.dataset.tableId}/dining-session/transfer`, {
                            method: 'POST',
                            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                            body: JSON.stringify({ target_table_id: Number(transferTarget.value) }),
                        });
                        await readJson(response);
                        window.location.reload();
                    } catch (error) {
                        setError(transferModal.querySelector('[data-transfer-error]'), error.message);
                    } finally {
                        submitButton.disabled = !transferTarget.value;
                    }
                });

                manager.querySelector('[data-refresh-tables]').addEventListener('click', async (event) => {
                    const button = event.currentTarget;
                    button.disabled = true;
                    button.querySelector('[data-refresh-icon]').classList.add('animate-spin');
                    try {
                        const response = await fetch(@json(route('api.tables.status')), { headers: { Accept: 'application/json' } });
                        const result = await readJson(response);
                        result.data.forEach((table) => {
                            const card = cards.find((item) => item.dataset.tableId === String(table.id));
                            if (card) {
                                updateCardStatus(
                                    card,
                                    typeof table.status === 'object' ? table.status.value : table.status,
                                    table.open_session,
                                );
                            }
                        });
                        updateCounts();
                        if (selectedCard) updateDetail(selectedCard);
                        applyFilters();
                    } catch (error) {
                        window.toast?.error(error.message, 'Không thể làm mới');
                    } finally {
                        button.disabled = false;
                        button.querySelector('[data-refresh-icon]').classList.remove('animate-spin');
                    }
                });

                openSessionForm.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    if (!selectedCard) return;
                    const submitButton = openModal.querySelector('[data-open-submit]');
                    submitButton.disabled = true;
                    setError(openModal.querySelector('[data-open-error]'));
                    try {
                        const response = await fetch(@json(route('api.dining-sessions.store')), {
                            method: 'POST',
                            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                            body: JSON.stringify({
                                table_id: Number(selectedCard.dataset.tableId),
                                guest_count: Number(openSessionForm.elements.namedItem('guest_count').value),
                                opened_by: openSessionForm.elements.namedItem('opened_by').value || null,
                            }),
                        });
                        await readJson(response);
                        window.location.reload();
                    } catch (error) {
                        setError(openModal.querySelector('[data-open-error]'), error.message);
                    } finally {
                        submitButton.disabled = false;
                    }
                });

                document.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', () => {
                    hideModal(button.closest('[data-modal]'));
                }));
                [openModal, transferModal, updateModal, mergeModal, closeModal].forEach((modal) => modal.addEventListener('click', (event) => {
                    if (event.target === modal) hideModal(modal);
                }));
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        hideModal(openModal);
                        hideModal(transferModal);
                        hideModal(updateModal);
                        hideModal(mergeModal);
                        hideModal(closeModal);
                    }
                });

                updateDetail(selectedCard);
                updateCounts();
                applyFilters();
            })();
        </script>
    @endpush
@endsection
