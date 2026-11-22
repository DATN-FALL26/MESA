@extends('layouts.auth')

@section('heading', $title ?? 'Tổng quan Dashboard Kinh doanh')

@section('content')
@php
    $recentItemsByOrder = collect();
    if (
        \Illuminate\Support\Facades\Schema::hasTable('order_items') &&
        \Illuminate\Support\Facades\Schema::hasColumn('order_items', 'order_id') &&
        !empty($donHangs)
    ) {
        $orderIds = collect($donHangs)->pluck('id')->filter()->values()->all();
        if (!empty($orderIds)) {
            $q = \Illuminate\Support\Facades\DB::table('order_items')
                ->whereIn('order_items.order_id', $orderIds);

            $qtyCol = \Illuminate\Support\Facades\Schema::hasColumn('order_items', 'quantity')
                ? 'order_items.quantity'
                : '1';

            if (\Illuminate\Support\Facades\Schema::hasTable('menu_items') && \Illuminate\Support\Facades\Schema::hasColumn('order_items', 'menu_item_id')) {
                $q->leftJoin('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id');
                $rows = $q->select(
                    'order_items.order_id',
                    \Illuminate\Support\Facades\DB::raw("COALESCE(menu_items.name, CONCAT('Món #', order_items.menu_item_id)) as item_name"),
                    \Illuminate\Support\Facades\DB::raw($qtyCol . ' as qty')
                )->get();
            } else {
                $rows = $q->select(
                    'order_items.order_id',
                    \Illuminate\Support\Facades\DB::raw("'Món ăn' as item_name"),
                    \Illuminate\Support\Facades\DB::raw($qtyCol . ' as qty')
                )->get();
            }

            $recentItemsByOrder = $rows->groupBy('order_id');
        }
    }
@endphp

<div class="space-y-6">
    {{-- Header & Bộ lọc chi nhánh --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">Tổng Quan Kinh Doanh</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Số liệu hoạt động trực tuyến theo thời gian thực tại chi nhánh.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button onclick="window.location.reload();" type="button" class="inline-flex items-center gap-1.5 rounded-xl bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm border border-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.992 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                Làm mới
            </button>

            <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-wrap items-center gap-3">
                <input type="hidden" name="filter" value="{{ $filter ?? 'week' }}">
                <select name="branch_id" onchange="this.form.submit()"
                        class="rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    @foreach($branches ?? [] as $branch)
                        @php
                            $bId = data_get($branch, 'id', 1);
                            $bName = data_get($branch, 'name', 'Chi nhánh');
                        @endphp
                        <option value="{{ $bId }}" {{ (isset($branchId) && $branchId == $bId) ? 'selected' : '' }}>
                            {{ $bName }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    {{-- Tầng 1: 4 Ô Thống Kê --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/70 p-6 shadow-sm dark:border-emerald-800/60 dark:bg-emerald-950/30">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Doanh thu hôm nay</span>
            <h3 class="mt-3 text-2xl font-extrabold text-emerald-950 dark:text-white">{{ number_format((float) ($doanhThu ?? 0), 0, ',', '.') }} đ</h3>
            <p class="mt-1 text-xs font-medium text-emerald-700/80 dark:text-emerald-300/70">Dữ liệu từ quầy thu ngân</p>
        </div>

        <div class="rounded-2xl border border-indigo-200/80 bg-indigo-50/70 p-6 shadow-sm dark:border-indigo-800/60 dark:bg-indigo-950/30">
            <span class="text-xs font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-400">Tổng số đơn hàng</span>
            <h3 class="mt-3 text-2xl font-extrabold text-indigo-950 dark:text-white">{{ $tongDon ?? 0 }} đơn</h3>
            <p class="mt-1 text-xs font-medium text-indigo-700/80 dark:text-indigo-300/70">Đơn hoàn tất trong ngày</p>
        </div>

        <div class="rounded-2xl border border-amber-200/80 bg-amber-50/70 p-6 shadow-sm dark:border-amber-800/60 dark:bg-amber-950/30">
            <span class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">Món ăn bán chạy</span>
            <h3 class="mt-3 text-xl font-extrabold text-amber-950 dark:text-white truncate">
                {{ data_get($monBanChay, 'dish.name', 'Chưa có dữ liệu') }}
            </h3>
            <p class="mt-1 text-xs font-semibold text-amber-700 dark:text-amber-400">
                Đã bán: {{ data_get($monBanChay, 'total_qty', 0) }} phần
            </p>
        </div>

        <div class="rounded-2xl border border-rose-200/80 bg-rose-50/70 p-6 shadow-sm dark:border-rose-800/60 dark:bg-rose-950/30">
            <span class="text-xs font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400">Ít được lên món</span>
            <h3 class="mt-3 text-xl font-extrabold text-rose-950 dark:text-white truncate">
                {{ data_get($monItOrder, 'dish.name', 'Chưa có dữ liệu') }}
            </h3>
            <p class="mt-1 text-xs font-semibold text-rose-700 dark:text-rose-400">
                Chỉ đạt: {{ data_get($monItOrder, 'total_qty', 0) }} phần
            </p>
        </div>
    </div>

    {{-- Tầng 2: Biểu đồ doanh thu & Cơ cấu thanh toán --}}
    @php
        $cashVal = (float) data_get($paymentStats ?? [], 'cash', 0);
        $transferVal = (float) data_get($paymentStats ?? [], 'transfer', 0);
        $revenueHeadingMap = [
            'week' => 'Biểu Đồ Tăng Trưởng Doanh Thu Theo Tuần',
            'month' => 'Biểu Đồ Tăng Trưởng Doanh Thu Theo Tháng',
            'year' => 'Biểu Đồ Tăng Trưởng Doanh Thu Theo Năm',
        ];
        $revenueDescMap = [
            'week' => 'Các ngày trong tuần (Thứ 2 - Chủ Nhật)',
            'month' => 'Các tuần trong tháng hiện tại',
            'year' => 'Các tháng trong năm hiện tại',
        ];
        $currentFilterKey = $filter ?? 'week';
        $revenueHeading = $revenueHeadingMap[$currentFilterKey] ?? 'Biểu Đồ Tăng Trưởng Doanh Thu Theo Tuần';
        $revenueDesc = $revenueDescMap[$currentFilterKey] ?? 'Các ngày trong tuần (Thứ 2 - Chủ Nhật)';
    @endphp
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">{{ $revenueHeading }}</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $revenueDesc }}</p>
                </div>

                <form method="GET" action="{{ route('admin.dashboard') }}" class="shrink-0">
                    <input type="hidden" name="branch_id" value="{{ $branchId ?? 1 }}">
                    <select name="filter" onchange="this.form.submit()"
                            class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        <option value="week" {{ (($filter ?? 'week') == 'week') ? 'selected' : '' }}>Theo Tuần</option>
                        <option value="month" {{ (($filter ?? 'week') == 'month') ? 'selected' : '' }}>Theo Tháng</option>
                        <option value="year" {{ (($filter ?? 'week') == 'year') ? 'selected' : '' }}>Theo Năm</option>
                    </select>
                </form>
            </div>
            <div class="relative h-72 w-full">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Cơ Cấu Phương Thức Thanh Toán</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tiền mặt so với chuyển khoản</p>
            </div>

            <div class="relative h-52 w-full my-3">
                <canvas id="paymentChart"></canvas>
            </div>

            <div class="space-y-2 border-t border-slate-100 pt-3 text-xs dark:border-slate-800">
                <div class="flex items-center justify-between">
                    <span class="font-medium text-slate-600 dark:text-slate-300">Tiền mặt</span>
                    <span class="font-bold text-slate-900 dark:text-white">{{ number_format($cashVal, 0, ',', '.') }} đ</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="font-medium text-slate-600 dark:text-slate-300">Chuyển khoản / QR</span>
                    <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($transferVal, 0, ',', '.') }} đ</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tầng 3: Đơn hàng gần đây & Chi tiết đơn (full-width) --}}
    <section class="w-full rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Đơn Hàng Gần Đây</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Chi tiết món, thời gian và thanh toán — bấm vào từng đơn để xem đầy đủ</p>
            </div>
        </div>

        <div class="space-y-3">
            @forelse($donHangs ?? [] as $dh)
                @php
                    $dhId = data_get($dh, 'id', '');
                    $dhCode = data_get($dh, 'order_code') ?: data_get($dh, 'code') ?: ('#' . $dhId);
                    $dhAmount = (float) (data_get($dh, 'total_amount') ?? data_get($dh, 'grand_total') ?? data_get($dh, 'amount') ?? 0);
                    $dhStatus = data_get($dh, 'status', '');
                    $dhPayment = data_get($dh, 'payment_method', '');
                    $dhCreated = data_get($dh, 'created_at', '');
                    $dhItems = $recentItemsByOrder->get($dhId, collect());
                @endphp
                <button type="button"
                        onclick="openOrderDetail({{ $dhId }})"
                        class="w-full text-left flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between p-4 rounded-xl bg-slate-50 hover:bg-indigo-50/60 dark:bg-slate-800/50 dark:hover:bg-slate-800 border border-slate-100 hover:border-indigo-200 dark:border-slate-800 transition">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-bold text-sm text-slate-800 dark:text-white">Đơn {{ $dhCode }}</span>
                            <span class="rounded-md bg-white px-2 py-0.5 text-[11px] font-medium text-slate-600 border border-slate-200 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-300">
                                {{ $dhStatus !== '' ? $dhStatus : 'Đã ghi nhận' }}
                            </span>
                            @if($dhPayment !== '')
                                <span class="text-[11px] text-slate-500 dark:text-slate-400">• TT: {{ $dhPayment }}</span>
                            @endif
                            @if($dhCreated !== '')
                                <span class="text-[11px] text-slate-400">• {{ $dhCreated }}</span>
                            @endif
                        </div>

                        @if($dhItems->isNotEmpty())
                            <div class="mt-1.5 text-xs text-slate-600 dark:text-slate-300">
                                <span class="font-medium text-slate-500 dark:text-slate-400">Món:</span>
                                @foreach($dhItems as $idx => $rowItem)
                                    <span>{{ $rowItem->item_name }} (x{{ (int) $rowItem->qty }})</span>{{ !$loop->last ? ', ' : '' }}
                                @endforeach
                            </div>
                        @else
                            <div class="mt-1 text-xs text-slate-400">Bấm để xem chi tiết món của đơn hàng</div>
                        @endif
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <span class="font-bold text-sm text-indigo-600 dark:text-indigo-400">{{ number_format($dhAmount, 0, ',', '.') }} đ</span>
                        <span class="text-xs font-semibold text-slate-400">Chi tiết →</span>
                    </div>
                </button>
            @empty
                <p class="text-sm text-slate-500 py-4 text-center">Chưa có giao dịch đơn hàng nào.</p>
            @endforelse
        </div>
    </section>
</div>

{{-- Modal Chi Tiết Đơn Hàng --}}
<div id="orderDetailModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4" onclick="if(event.target === this) closeOrderDetail()">
    <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-700 dark:bg-slate-900">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
            <div>
                <h4 id="odTitle" class="text-base font-bold text-slate-900 dark:text-white">Chi tiết đơn hàng</h4>
                <p id="odMeta" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"></p>
            </div>
            <button type="button" onclick="closeOrderDetail()" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">Đóng</button>
        </div>

        <div id="odBody" class="mt-4 max-h-72 overflow-y-auto space-y-2 text-sm">
            <p class="text-slate-500 text-center py-4">Đang tải dữ liệu...</p>
        </div>

        <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-800">
            <span class="text-xs font-semibold uppercase text-slate-500">Tổng tiền</span>
            <span id="odTotal" class="text-base font-extrabold text-indigo-600 dark:text-indigo-400">0 đ</span>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function formatVnd(num) {
        return Number(num || 0).toLocaleString('vi-VN') + ' đ';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }

    function closeOrderDetail() {
        const modal = document.getElementById('orderDetailModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openOrderDetail(orderId) {
        if (!orderId) return;
        const modal = document.getElementById('orderDetailModal');
        const titleEl = document.getElementById('odTitle');
        const metaEl = document.getElementById('odMeta');
        const bodyEl = document.getElementById('odBody');
        const totalEl = document.getElementById('odTotal');

        titleEl.textContent = 'Đơn #' + orderId;
        metaEl.textContent = '';
        totalEl.textContent = '0 đ';
        bodyEl.innerHTML = '<p class="text-slate-500 text-center py-4">Đang tải chi tiết đơn hàng...</p>';
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        const url = "{{ route('admin.dashboard.order-detail', ['id' => '__ID__']) }}".replace('__ID__', orderId);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => {
                if (!res.ok) throw new Error('Không thể tải chi tiết đơn');
                return res.json();
            })
            .then(data => {
                titleEl.textContent = 'Chi tiết Đơn ' + (data.code || ('#' + data.id));
                const metaParts = [];
                if (data.status) metaParts.push('Trạng thái: ' + data.status);
                if (data.branch_name) metaParts.push('Chi nhánh: ' + data.branch_name);
                if (data.payment_method) metaParts.push('TT: ' + data.payment_method);
                if (data.created_at) metaParts.push(data.created_at);
                metaEl.textContent = metaParts.join(' • ');

                totalEl.textContent = formatVnd(data.total_amount);

                const items = Array.isArray(data.items) ? data.items : [];
                if (items.length === 0) {
                    bodyEl.innerHTML = '<p class="text-slate-500 text-center py-4">Không có dòng món chi tiết cho đơn này.</p>';
                    return;
                }

                bodyEl.innerHTML = items.map(it => {
                    const qty = Number(it.quantity || 1);
                    const price = Number(it.unit_price || 0);
                    const lineTotal = qty * price;
                    return `
                        <div class="flex items-center justify-between rounded-xl bg-slate-50 dark:bg-slate-800/60 px-3.5 py-2.5 border border-slate-100 dark:border-slate-800">
                            <div>
                                <div class="font-semibold text-slate-800 dark:text-white">${escapeHtml(it.name)}</div>
                                <div class="text-xs text-slate-500">SL: ${qty} × ${formatVnd(price)}</div>
                            </div>
                            <span class="font-bold text-xs text-slate-700 dark:text-slate-200">${formatVnd(lineTotal)}</span>
                        </div>
                    `;
                }).join('');
            })
            .catch(() => {
                bodyEl.innerHTML = '<p class="text-rose-500 text-center py-4">Lỗi khi tải chi tiết đơn hàng.</p>';
            });
    }

    document.addEventListener("DOMContentLoaded", function () {
        const ctx = document.getElementById('revenueChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($chartLabels ?? []),
                    datasets: [{
                        label: 'Doanh thu (VNĐ)',
                        data: @json($chartData ?? []),
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.1)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        const paymentCtx = document.getElementById('paymentChart');
        if (paymentCtx) {
            const cashData = @json((float) data_get($paymentStats ?? [], 'cash', 0));
            const transferData = @json((float) data_get($paymentStats ?? [], 'transfer', 0));
            const hasPaymentData = (cashData + transferData) > 0;

            new Chart(paymentCtx, {
                type: 'doughnut',
                data: {
                    labels: hasPaymentData ? ['Tiền mặt', 'Chuyển khoản / QR'] : ['Chưa có dữ liệu'],
                    datasets: [{
                        data: hasPaymentData ? [cashData, transferData] : [1],
                        backgroundColor: hasPaymentData ? ['#10b981', '#6366f1'] : ['#cbd5e1'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                font: { size: 11 }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
@endsection
