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
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-medium text-slate-500">Mã bàn</p>
                                    <h3 data-detail-code class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">{{ $selectedTable?->code ?? '—' }}</h3>
                                </div>
                                <span data-detail-status class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold">
                                    <span data-detail-status-label>{{ $statusLabels[$selectedTableStatus] ?? 'Chưa chọn' }}</span>
                                </span>
                            </div>
                            <div data-detail-session class="{{ $openSessionsByTableId->has($selectedTable?->id ?? 0) ? '' : 'hidden ' }}mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-3">
                                <p class="text-sm font-semibold text-emerald-950">Phiên #<span data-detail-session-id>{{ $openSessionsByTableId->get($selectedTable?->id ?? 0)?->id }}</span></p>
                                <p class="mt-1 text-xs text-emerald-900"><span data-detail-guest-count>{{ $openSessionsByTableId->get($selectedTable?->id ?? 0)?->guest_count }}</span> khách · Nhân viên: <span data-detail-employee>{{ $openSessionsByTableId->get($selectedTable?->id ?? 0)?->openedByUser?->full_name ?? '—' }}</span></p>
                                <button type="button" data-transfer-table class="mt-3 w-full rounded-lg border border-emerald-800 px-3 py-2 text-sm font-semibold text-emerald-950 transition hover:bg-emerald-100">Chuyển sang bàn khác</button>
                                <button type="button" data-close-session class="mt-3 w-full rounded-lg bg-emerald-900 px-3 py-2 text-sm font-semibold text-white transition hover:bg-emerald-950 disabled:cursor-wait disabled:opacity-60">Đóng phiên, trả bàn trống</button>
                                <p data-session-error class="mt-2 hidden text-xs font-medium text-rose-700" role="alert"></p>
                            </div>
                            <div data-transfer-history class="mt-4 border-t border-slate-200 pt-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-800">Lịch sử chuyển bàn gần đây</p>
                                <ul data-transfer-history-list class="mt-2 space-y-2">
                                    @foreach ($tableTransferHistoryByTableId->get($selectedTable?->id, collect()) as $history)
                                        <li class="rounded-lg bg-slate-50 px-2.5 py-2 text-xs text-slate-700">
                                            <span class="font-semibold">{{ $history['from'] }} → {{ $history['to'] }}</span>
                                            <span class="mt-1 block">{{ $history['actor'] }} · {{ $history['at'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                                <p data-transfer-history-empty class="{{ $tableTransferHistoryByTableId->get($selectedTable?->id, collect())->isEmpty() ? '' : 'hidden ' }}mt-2 text-xs text-slate-500">Chưa có lịch sử chuyển bàn.</p>
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
                            <div class="mt-2 grid grid-cols-2 gap-2">
                                <button type="button" data-open-table class="{{ $selectedTable && $selectedTable->status === \App\Enums\TableStatus::AVAILABLE ? '' : 'hidden ' }}rounded-xl bg-emerald-800 px-3 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-900">Mở phiên</button>
                            </div>
                        </div>
                        <p class="mt-3 text-xs leading-5 text-slate-500">Bàn đang sử dụng hoặc đã đặt không thể mở phiên mới.</p>
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
                    if ([openModal, transferModal].every((item) => item.classList.contains('hidden'))) {
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
                    detail.querySelector('[data-detail-session-id]').textContent = card.dataset.sessionId;
                    detail.querySelector('[data-detail-guest-count]').textContent = card.dataset.sessionGuestCount;
                    detail.querySelector('[data-detail-employee]').textContent = card.dataset.sessionEmployee || '—';
                    const transferHistory = JSON.parse(card.dataset.tableTransferHistory || '[]');
                    const historyList = detail.querySelector('[data-transfer-history-list]');
                    const historyEmpty = detail.querySelector('[data-transfer-history-empty]');
                    historyList.replaceChildren();
                    transferHistory.forEach((entry) => {
                        const item = document.createElement('li');
                        item.className = 'rounded-lg bg-white/80 px-2.5 py-2 text-xs text-slate-700';
                        const tables = document.createElement('span');
                        tables.className = 'font-semibold';
                        tables.textContent = `${entry.from} → ${entry.to}`;
                        const actorAndTime = document.createElement('span');
                        actorAndTime.className = 'mt-1 block';
                        actorAndTime.textContent = `${entry.actor} · ${entry.at}`;
                        item.append(tables, actorAndTime);
                        historyList.append(item);
                    });
                    historyEmpty.classList.toggle('hidden', transferHistory.length > 0);
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
                        throw new Error(validationMessage || payload.message || `Yêu cầu không thành công (HTTP ${response.status}).`);
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
                    transferTarget.replaceChildren(new Option('Chọn bàn đích', ''));
                    const availableTables = cards.filter((item) => item !== card && item.dataset.status === 'available');
                    availableTables.forEach((table) => {
                        transferTarget.append(new Option(
                            `${table.dataset.code} · ${table.dataset.seats} chỗ · ${table.dataset.areaName}`,
                            table.dataset.tableId,
                        ));
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
                detail.querySelector('[data-transfer-table]').addEventListener('click', () => openTransferForTable(selectedCard));
                detail.querySelector('[data-close-session]').addEventListener('click', async (event) => {
                    if (!selectedCard?.dataset.sessionId) return;
                    if (!window.confirm(`Đóng phiên #${selectedCard.dataset.sessionId} và trả bàn ${selectedCard.dataset.code} về trạng thái Trống?`)) return;
                    const button = event.currentTarget;
                    button.disabled = true;
                    setError(detail.querySelector('[data-session-error]'));
                    try {
                        const response = await fetch(`/api/tables/${selectedCard.dataset.tableId}/dining-session/close`, {
                            method: 'POST',
                            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        });
                        await readJson(response);
                        window.location.reload();
                    } catch (error) {
                        setError(detail.querySelector('[data-session-error]'), error.message);
                    } finally {
                        button.disabled = false;
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
                        submitButton.disabled = transferTarget.options.length <= 1;
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
                [openModal, transferModal].forEach((modal) => modal.addEventListener('click', (event) => {
                    if (event.target === modal) hideModal(modal);
                }));
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        hideModal(openModal);
                        hideModal(transferModal);
                    }
                });

                updateDetail(selectedCard);
                updateCounts();
                applyFilters();
            })();
        </script>
    @endpush
@endsection
