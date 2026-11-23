@extends('layouts.auth')

@section('heading', $title ?? 'Tổng quan Dashboard Kinh doanh')

@section('content')
@include('admin.partials.system-overview')

<div class="space-y-6">
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

                <select name="filter" onchange="this.form.submit()"
                        class="rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    <option value="week" {{ (($filter ?? 'week') == 'week') ? 'selected' : '' }}>Theo Tuần</option>
                    <option value="month" {{ (($filter ?? 'week') == 'month') ? 'selected' : '' }}>Theo Tháng</option>
                    <option value="year" {{ (($filter ?? 'week') == 'year') ? 'selected' : '' }}>Theo Năm</option>
                </select>
            </form>
        </div>
    </div>

    {{-- 4 Ô Thống Kê --}}
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

    {{-- Tầng biểu đồ: Doanh thu + Cơ cấu thanh toán --}}
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
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">{{ $revenueHeading }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">{{ $revenueDesc }}</p>
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

    {{-- Sơ đồ bàn & Đơn hàng gần đây --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Sơ Đồ Trạng Thái Bàn Ăn</h3>
                </div>

                {{-- Nút dẫn đến POS & Sơ đồ bàn --}}
                <a href="{{ route('tables.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm border border-slate-200 hover:bg-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-700 transition-colors">
                    <svg class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" /></svg>
                    POS &amp; Sơ đồ bàn &rarr;
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 max-h-80 overflow-y-auto pr-2">
                @forelse($danhSachBan ?? [] as $ban)
                    @php
                        $banId = data_get($ban, 'id', '');
                        $banName = data_get($ban, 'name') ?: data_get($ban, 'table_name') ?: data_get($ban, 'ten_ban') ?: ('Bàn ' . $banId);
                        $banStatus = data_get($ban, 'status', 'empty');
                        $isBusy = in_array($banStatus, ['busy', 'occupied', 'serving', 'dang_phuc_vu'], true);
                    @endphp
                    <div class="p-3 rounded-xl border {{ $isBusy ? 'bg-rose-500/10 border-rose-200 text-rose-700' : 'bg-emerald-500/10 border-emerald-200 text-emerald-700' }} flex flex-col justify-between">
                        <span class="font-bold text-sm">{{ $banName }}</span>
                        <span class="text-xs font-semibold mt-1">{{ $isBusy ? 'Đang phục vụ' : 'Trống' }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 col-span-full py-4 text-center">Chưa có thông tin bàn ăn trong Database.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">Đơn Hàng Gần Đây</h3>
            <div class="space-y-3">
                @forelse($donHangs ?? [] as $dh)
                    @php
                        $dhId = data_get($dh, 'id', '');
                        $dhCode = data_get($dh, 'order_code') ?: data_get($dh, 'code') ?: ('#' . $dhId);
                        $dhAmount = (float) (data_get($dh, 'total_amount') ?? data_get($dh, 'grand_total') ?? data_get($dh, 'amount') ?? 0);
                        $dhStatus = data_get($dh, 'status', '');
                    @endphp
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                        <div>
                            <span class="font-bold text-sm text-slate-800 dark:text-white">Đơn {{ $dhCode }}</span>
                            <div class="text-xs text-slate-500 mt-0.5">{{ $dhStatus !== '' ? $dhStatus : 'Đã ghi nhận' }}</div>
                        </div>
                        <span class="font-bold text-xs text-indigo-600 dark:text-indigo-400">{{ number_format($dhAmount, 0, ',', '.') }} đ</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 py-4 text-center">Chưa có giao dịch đơn hàng nào.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
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

            const paymentChart = new Chart(paymentCtx, {
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

            fetch(@json(route('admin.dashboard.payment_stats', ['filter' => $filter ?? 'week']), JSON_UNESCAPED_SLASHES), {
                headers: { 'Accept': 'application/json' }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Không thể tải thống kê thanh toán (${response.status}).`);
                    }

                    return response.json();
                })
                .then(response => {
                    if (response.status !== 'success') {
                        throw new Error('Dữ liệu thống kê thanh toán không hợp lệ.');
                    }

                    const cash = Number(response.data.cash);
                    const transfer = Number(response.data.transfer);
                    if (!Number.isFinite(cash) || !Number.isFinite(transfer)) {
                        throw new Error('Dữ liệu thống kê thanh toán không hợp lệ.');
                    }

                    const hasData = (cash + transfer) > 0;
                    paymentChart.data.labels = hasData ? ['Tiền mặt', 'Chuyển khoản / QR'] : ['Chưa có dữ liệu'];
                    paymentChart.data.datasets[0].data = hasData ? [cash, transfer] : [1];
                    paymentChart.data.datasets[0].backgroundColor = hasData ? ['#10b981', '#6366f1'] : ['#cbd5e1'];
                    paymentChart.update();
                })
                .catch(error => console.error('Lỗi tải thống kê thanh toán:', error));
        }
    });
</script>
@endpush
@endsection
