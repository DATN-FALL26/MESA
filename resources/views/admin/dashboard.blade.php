@extends('layouts.auth')

@section('title', 'Dashboard | MESA')
@section('heading', 'Dashboard tổng hợp')

@section('content')
    @include('admin.partials.system-overview')

<div class="mt-8 space-y-6">
    <!-- Header Dashboard -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <!-- <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Dashboard</h1>
            <p class="text-sm text-gray-500 mt-0.5">Hệ thống giám sát kinh doanh và vận hành theo chi nhánh thời gian thực.</p>
        </div> -->
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-sm">
                <span class="w-2 h-2 mr-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                {{ now()->format('d/m/Y') }}
            </span>
        </div>
    </div>

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Tổng quan Dashboard</h1>
            <p class="text-sm text-slate-500">Thống kê hoạt động kinh doanh nhà hàng trong ngày</p>
        </div>

        <!-- Filter Chi nhánh -->
        <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
            <select name="branch_id" onchange="this.form.submit()" class="bg-white border border-slate-300 rounded-lg px-4 py-2 text-sm font-medium text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- 1. Hàng Thống kê nhanh (Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Card 1: Doanh thu -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Doanh thu hôm nay</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($doanhThu) }} đ</h3>
            </div>
            <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>

        <!-- Card 2: Tổng đơn -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Tổng số đơn</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">{{ $tongDon }} đơn</h3>
            </div>
            <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            </div>
        </div>

        <!-- Card 3: Món bán chạy nhất -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Món bán chạy nhất</p>
                <h3 class="text-base font-bold text-slate-800 mt-1 truncate max-w-[150px]">{{ $monBanChay->dish->name ?? 'N/A' }}</h3>
                <p class="text-xs text-slate-500 mt-0.5">Đã bán: {{ $monBanChay->total_qty ?? 0 }} suất</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
            </div>
        </div>

        <!-- Card 4: Bán ít nhất -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Món bán ít nhất</p>
                <h3 class="text-base font-bold text-slate-800 mt-1 truncate max-w-[150px]">{{ $monItOrder->dish->name ?? 'N/A' }}</h3>
                <p class="text-xs text-slate-500 mt-0.5">Đã bán: {{ $monItOrder->total_qty ?? 0 }} suất</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
            </div>
        </div>
    </div>

    <!-- 2. Bố cục 2 Cột chính -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Cột trái (Chiếm 2 phần): Biểu đồ ca làm việc & Đơn hàng vừa đặt -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Biểu đồ doanh thu theo Ca -->
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                <h3 class="text-base font-bold text-slate-800 mb-4">Doanh Thu Theo Ca Làm Việc</h3>
                <div class="h-64">
                    <canvas id="shiftRevenueChart"></canvas>
                </div>
            </div>

            <!-- Bảng Đơn hàng gần đây -->
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-slate-800">Đơn Hàng Gần Đây</h3>
                    <span class="text-xs text-slate-400">5 đơn mới nhất</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-medium">
                                <th class="pb-3">Mã đơn</th>
                                <th class="pb-3">Tổng tiền</th>
                                <th class="pb-3">Thời gian</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($donHangs as $don)
                                <tr class="hover:bg-slate-50">
                                    <td class="py-3 font-semibold text-slate-700">#{{ $don->id }}</td>
                                    <td class="py-3 text-emerald-600 font-medium">{{ number_format($don->total_amount) }} đ</td>
                                    <td class="py-3 text-slate-400 text-xs">{{ \Carbon\Carbon::parse($don->created_at)->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-4 text-center text-slate-400">Chưa có đơn hàng mới</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cột phải (Chiếm 1 phần): Biểu đồ Thanh toán & Trạng thái Bàn -->
        <div class="space-y-6">

            <!-- Biểu đồ Phương thức thanh toán (Tiền mặt vs Chuyển khoản) -->
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                <h3 class="text-base font-bold text-slate-800 mb-4">Phương Thức Thanh Toán</h3>
                <div class="h-56 relative flex items-center justify-center">
                    <canvas id="paymentMethodChart"></canvas>
                </div>
            </div>

            <!-- Thống kê Trạng thái bàn -->
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                <h3 class="text-base font-bold text-slate-800 mb-4">Trạng Thái Bàn</h3>
                <div class="grid grid-cols-2 gap-3">
                    @foreach($danhSachBan as $ban)
                        <div class="p-3 rounded-lg border flex items-center justify-between {{ ($ban->status ?? '') == 'active' ? 'bg-amber-50 border-amber-200 text-amber-700' : 'bg-slate-50 border-slate-200 text-slate-600' }}">
                            <span class="text-sm font-semibold">{{ $ban->name }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ ($ban->status ?? '') == 'active' ? 'bg-amber-200 text-amber-800' : 'bg-slate-200 text-slate-600' }}">
                                {{ ($ban->status ?? '') == 'active' ? 'Có khách' : 'Trống' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // 1. Biểu đồ Doanh thu theo ca (Cột)
        const shiftCtx = document.getElementById('shiftRevenueChart').getContext('2d');
        new Chart(shiftCtx, {
            type: 'bar',
            data: {
                labels: ['Ca Sáng (6h - 11h)', 'Ca Trưa (11h - 17h)', 'Ca Tối (17h - 22h)'],
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: @json($chartData),
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b'],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // 2. Biểu đồ Phương thức thanh toán (Dùng URL tuyệt đối tránh lỗi route name)
        fetch("/admin/dashboard/api-payment-stats")
            .then(res => res.json())
            .then(res => {
                if(res.status === 'success') {
                    const payCtx = document.getElementById('paymentMethodChart').getContext('2d');
                    new Chart(payCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Tiền mặt', 'Chuyển khoản'],
                            datasets: [{
                                data: [res.data.cash, res.data.transfer],
                                backgroundColor: ['#10b981', '#6366f1']
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom' }
                            }
                        }
                    });
                }
            })
            .catch(err => console.error("Lỗi tải API phương thức thanh toán:", err));
    });
</script>
@endsection
