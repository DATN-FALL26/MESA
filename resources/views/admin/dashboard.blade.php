@extends('layouts.auth')

@section('heading', $title ?? 'Tổng quan Dashboard Kinh doanh')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">Tổng Quan Kinh Doanh</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Số liệu hoạt động trực tuyến theo thời gian thực tại chi nhánh.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button onclick="window.location.reload();" class="inline-flex items-center gap-1.5 rounded-xl bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm border border-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.992 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                Làm mới
            </button>

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
    </div>

    {{-- 4 Ô Thống Kê --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/70 p-6 shadow-sm dark:border-emerald-800/60 dark:bg-emerald-950/30">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Doanh thu hôm nay</span>
            <h3 class="mt-3 text-2xl font-extrabold text-emerald-950 dark:text-white">{{ number_format($doanhThu ?? 0, 0, ',', '.') }} đ</h3>
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
                {{ $monBanChay->dish->name ?? 'Chưa có dữ liệu' }}
            </h3>
            <p class="mt-1 text-xs font-semibold text-amber-700 dark:text-amber-400">
                Đã bán: {{ $monBanChay->total_qty ?? 0 }} phần
            </p>
        </div>

        <div class="rounded-2xl border border-rose-200/80 bg-rose-50/70 p-6 shadow-sm dark:border-rose-800/60 dark:bg-rose-950/30">
            <span class="text-xs font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400">Ít được lên món</span>
            <h3 class="mt-3 text-xl font-extrabold text-rose-950 dark:text-white truncate">
                {{ $monItOrder->dish->name ?? 'Chưa có dữ liệu' }}
            </h3>
            <p class="mt-1 text-xs font-semibold text-rose-700 dark:text-rose-400">
                Chỉ đạt: {{ $monItOrder->total_qty ?? 0 }} phần
            </p>
        </div>
    </div>

    {{-- Biểu đồ --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">Biểu Đồ Tăng Trưởng Doanh Thu</h3>
        <div class="relative h-72 w-full">
            <canvas id="revenueChart"></canvas>
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
                    POS & Sơ đồ bàn &rarr;
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 max-h-80 overflow-y-auto pr-2">
                @forelse($danhSachBan ?? [] as $ban)
                    @php
                        $banName = $ban->name ?? $ban->table_name ?? $ban->ten_ban ?? ('Bàn ' . $ban->id);
                        $banStatus = $ban->status ?? 'empty';
                        $isBusy = $banStatus == 'busy' || $banStatus == 'occupied';
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
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                        <div>
                            <span class="font-bold text-sm text-slate-800 dark:text-white">Đơn #{{ $dh->id }}</span>
                            <div class="text-xs text-slate-500 mt-0.5">Thành công</div>
                        </div>
                        <span class="font-bold text-xs text-indigo-600 dark:text-indigo-400">{{ number_format($dh->total_amount ?? 0, 0, ',', '.') }} đ</span>
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
    });
</script>
@endpush
@endsection
