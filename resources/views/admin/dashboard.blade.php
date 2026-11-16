@extends('layouts.auth')

@section('heading', $title ?? 'Tổng quan Dashboard Kinh doanh')

@section('content')
<div class="space-y-6">
    {{-- Thanh chọn chi nhánh & bộ lọc thời gian Dashboard --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">Tổng Quan Kinh Doanh</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Số liệu hoạt động trực tuyến theo thời gian thực tại chi nhánh.</p>
        </div>

        <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-wrap items-center gap-3">
            <select name="branch_id" onchange="this.form.submit()"
                    class="rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                @foreach($branches ?? [] as $branch)
                    @php
                        $bId = is_object($branch) ? ($branch->id ?? 1) : 1;
                        $bName = is_object($branch) ? ($branch->name ?? 'Chi nhánh') : 'Chi nhánh';
                    @endphp
                    <option value="{{ $bId }}" {{ (isset($branchId) && $branchId == $bId) ? 'selected' : '' }}>
                        {{ $bName }}
                    </option>
                @endforeach
            </select>

            <select name="filter" onchange="this.form.submit()"
                    class="rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                <option value="week" {{ (isset($filter) && $filter == 'week') ? 'selected' : '' }}>Theo Tuần</option>
                <option value="month" {{ (isset($filter) && $filter == 'month') ? 'selected' : '' }}>Theo Tháng</option>
                <option value="year" {{ (isset($filter) && $filter == 'year') ? 'selected' : '' }}>Theo Năm</option>
            </select>
        </form>
    </div>

    {{-- TẦNG 1: 4 Ô THỐNG KÊ NHANH VỚI 4 MÀU KHÁC NHAU --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        {{-- Ô 1: Doanh thu hôm nay (Tông Xanh Emerald) --}}
        <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/70 p-6 shadow-sm transition-all hover:shadow-md dark:border-emerald-800/60 dark:bg-emerald-950/30">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Doanh thu hôm nay</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </span>
            </div>
            <h3 class="mt-3 text-2xl font-extrabold text-emerald-950 dark:text-white">{{ number_format($doanhThu ?? 0, 0, ',', '.') }} đ</h3>
            <p class="mt-1 text-xs font-medium text-emerald-700/80 dark:text-emerald-300/70">Cập nhật trực tiếp từ quầy thu ngân</p>
        </div>

        {{-- Ô 2: Tổng số đơn hàng (Tông Xanh Indigo) --}}
        <div class="rounded-2xl border border-indigo-200/80 bg-indigo-50/70 p-6 shadow-sm transition-all hover:shadow-md dark:border-indigo-800/60 dark:bg-indigo-950/30">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-400">Tổng số đơn hàng</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/15 text-indigo-600 dark:text-indigo-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z" /></svg>
                </span>
            </div>
            <h3 class="mt-3 text-2xl font-extrabold text-indigo-950 dark:text-white">{{ $tongDon ?? 0 }} đơn</h3>
            <p class="mt-1 text-xs font-medium text-indigo-700/80 dark:text-indigo-300/70">Đơn hoàn tất trong ngày</p>
        </div>

        {{-- Ô 3: Món ăn bán chạy (Tông Cam Amber) --}}
        <div class="rounded-2xl border border-amber-200/80 bg-amber-50/70 p-6 shadow-sm transition-all hover:shadow-md dark:border-amber-800/60 dark:bg-amber-950/30">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">Món ăn bán chạy</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/15 text-amber-600 dark:text-amber-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" /></svg>
                </span>
            </div>
            <h3 class="mt-3 text-xl font-extrabold text-amber-950 dark:text-white truncate">
                {{ $monBanChay->dish->name ?? 'Phở Bò' }}
            </h3>
            <p class="mt-1 text-xs font-semibold text-amber-700 dark:text-amber-400">
                Đã bán: {{ $monBanChay->total_qty ?? 0 }} phần
            </p>
        </div>

        {{-- Ô 4: Món ăn ít được lên món (Tông Đỏ Hồng Rose) --}}
        <div class="rounded-2xl border border-rose-200/80 bg-rose-50/70 p-6 shadow-sm transition-all hover:shadow-md dark:border-rose-800/60 dark:bg-rose-950/30">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400">Ít được lên món</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-500/15 text-rose-600 dark:text-rose-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6 9 12.75l4.286-4.286a11.948 11.948 0 0 1 4.306 6.43l.776 2.898m0 0 3.182-5.511m-3.182 5.51-5.511-3.181" /></svg>
                </span>
            </div>
            <h3 class="mt-3 text-xl font-extrabold text-rose-950 dark:text-white truncate">
                {{ $monItOrder->dish->name ?? 'Phở Gà' }}
            </h3>
            <p class="mt-1 text-xs font-semibold text-rose-700 dark:text-rose-400">
                Chỉ đạt: {{ $monItOrder->total_qty ?? 0 }} phần
            </p>
        </div>
    </div>

    {{-- Biểu đồ phân tích doanh thu --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">Biểu Đồ Tăng Trưởng Doanh Thu</h3>
        <div class="relative h-72 w-full">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    {{-- Sơ đồ bàn ăn & đơn hàng mới --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">Sơ Đồ Trạng Thái Bàn Ăn</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 max-h-80 overflow-y-auto pr-2">
                @forelse($danhSachBan ?? [] as $ban)
                    @php
                        $banName = is_object($ban) ? ($ban->name ?? 'Bàn') : 'Bàn';
                        $banStatus = is_object($ban) ? ($ban->status ?? 'empty') : 'empty';
                        $isBusy = $banStatus == 'busy' || $banStatus == 'occupied';
                    @endphp
                    <div class="p-3 rounded-xl border {{ $isBusy ? 'bg-rose-500/10 border-rose-200 text-rose-700 dark:border-rose-900 dark:text-rose-400' : 'bg-emerald-500/10 border-emerald-200 text-emerald-700 dark:border-emerald-900 dark:text-emerald-400' }} flex flex-col justify-between">
                        <span class="font-bold text-sm">{{ $banName }}</span>
                        <span class="text-xs font-semibold mt-1">{{ $isBusy ? 'Đang phục vụ' : 'Trống' }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Chưa có thông tin bàn ăn.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">Đơn Hàng Gần Đây</h3>
            <div class="space-y-3">
                @forelse($donHangs ?? [] as $dh)
                    @php
                        $dhId = is_object($dh) ? ($dh->id ?? 1) : 1;
                        $dhTotal = is_object($dh) ? ($dh->total_amount ?? 0) : 0;
                    @endphp
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                        <div>
                            <span class="font-bold text-sm text-slate-800 dark:text-white">Đơn #{{ $dhId }}</span>
                            <div class="text-xs text-slate-500 mt-0.5">Thanh toán thành công</div>
                        </div>
                        <span class="font-bold text-xs text-indigo-600 dark:text-indigo-400">{{ number_format($dhTotal, 0, ',', '.') }} đ</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Chưa có đơn hàng nào.</p>
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
            const labels = @json($chartLabels ?? []);
            const data = @json($chartData ?? []);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Doanh thu (VNĐ)',
                        data: data,
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
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.1)' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
@endsection
