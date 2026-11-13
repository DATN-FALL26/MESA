@extends('layouts.auth')

@section('title', 'Dashboard | MESA')
@section('heading', 'Dashboard tổng hợp')

@section('content')
<div class="container-fluid px-6 py-4">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Tổng quan Dashboard Kinh doanh</h1>
            <p class="text-sm text-gray-500">Thống kê hoạt động kinh doanh nhà hàng theo thời gian thực</p>
        </div>
        <div class="flex items-center gap-3 mt-3 md:mt-0">
            <form method="GET" action="{{ route('admin.dashboard') }}" id="branchForm" class="flex items-center gap-3">
                <input type="hidden" name="filter" value="{{ $filter ?? 'week' }}">
                <select name="branch_id" onchange="this.form.submit()" class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5">
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Doanh thu hôm nay</p>
                <h4 class="text-xl font-bold text-gray-800 mt-1">{{ number_format($doanhThu, 0, ',', '.') }} đ</h4>
            </div>
            <div class="p-3 bg-emerald-500 text-white rounded-xl shadow-sm shadow-emerald-200"><i class="fas fa-wallet text-lg"></i></div>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Tổng số đơn</p>
                <h4 class="text-xl font-bold text-gray-800 mt-1">{{ $tongDon }} đơn</h4>
            </div>
            <div class="p-3 bg-blue-500 text-white rounded-xl shadow-sm shadow-blue-200"><i class="fas fa-receipt text-lg"></i></div>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Món bán chạy nhất</p>
                <h4 class="text-base font-bold text-gray-800 mt-1 truncate max-w-[150px]">{{ $monBanChay->dish->name ?? 'N/A' }}</h4>
                <span class="text-xs text-emerald-500 font-medium">Đã bán: {{ $monBanChay->total_qty ?? 0 }} tô</span>
            </div>
            <div class="p-3 bg-amber-500 text-white rounded-xl shadow-sm shadow-amber-200"><i class="fas fa-fire text-lg"></i></div>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Món bán ít nhất</p>
                <h4 class="text-base font-bold text-gray-800 mt-1 truncate max-w-[150px]">{{ $monItOrder->dish->name ?? 'N/A' }}</h4>
                <span class="text-xs text-rose-500 font-medium">Đã bán: {{ $monItOrder->total_qty ?? 0 }} tô</span>
            </div>
            <div class="p-3 bg-rose-500 text-white rounded-xl shadow-sm shadow-rose-200"><i class="fas fa-arrow-down text-lg"></i></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
        <div class="lg:col-span-8 bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="text-base font-bold text-gray-800">Biểu đồ Phân tích Doanh thu</h3>
                    <p class="text-xs text-gray-400">Xem theo tuần, tháng hoặc năm</p>
                </div>
                <select id="timeFilterSelect" class="bg-gray-50 border border-gray-300 text-gray-700 text-xs rounded-lg p-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="week" {{ ($filter ?? 'week') == 'week' ? 'selected' : '' }}>Tuần này (7 ngày)</option>
                    <option value="month" {{ ($filter ?? 'week') == 'month' ? 'selected' : '' }}>Tháng này (Các tuần)</option>
                    <option value="year" {{ ($filter ?? 'week') == 'year' ? 'selected' : '' }}>Năm này (12 tháng)</option>
                </select>
            </div>
            <div class="relative h-72"><canvas id="revenueChart"></canvas></div>
        </div>
        <div class="lg:col-span-4 bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between">
            <h3 class="text-base font-bold text-gray-800 mb-1">Phương thức Thanh toán</h3>
            <div class="relative h-56 flex items-center justify-center"><canvas id="paymentMethodChart"></canvas></div>
            <div class="flex justify-center gap-6 mt-4 text-xs font-medium text-gray-600">
                <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-emerald-500"></span> Tiền mặt</span>
                <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-indigo-600"></span> Chuyển khoản</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-7 bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-base font-bold text-gray-800 mb-4">Đơn Hàng Gần Đây</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b text-xs font-semibold text-gray-400 uppercase">
                            <th class="py-2.5 px-3">Mã đơn</th>
                            <th class="py-2.5 px-3">Tổng tiền</th>
                            <th class="py-2.5 px-3 text-right">Thời gian</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-100">
                        @forelse($donHangs as $don)
                        <tr>
                            <td class="py-3 px-3 font-medium text-indigo-600">#{{ $don->id }}</td>
                            <td class="py-3 px-3 font-semibold text-emerald-600">{{ number_format($don->total_amount, 0, ',', '.') }} đ</td>
                            <td class="py-3 px-3 text-right text-xs text-gray-400">
                                {{ \Carbon\Carbon::parse($don->created_at)->diffForHumans() }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center py-4 text-gray-400 text-xs">Chưa có đơn hàng nào</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="lg:col-span-5 bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-base font-bold text-gray-800 mb-4">Trạng Thái Bàn</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach($danhSachBan as $ban)
                <div class="p-3 rounded-xl border flex flex-col justify-between {{ $ban->status == 'active' ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-gray-50 border-gray-200 text-gray-500' }}">
                    <span class="font-bold text-sm">{{ $ban->name }}</span>
                    <span class="text-xs font-semibold mt-2 px-2 py-0.5 rounded-full inline-block w-max {{ $ban->status == 'active' ? 'bg-amber-200 text-amber-900' : 'bg-gray-200 text-gray-600' }}">
                        {{ $ban->status == 'active' ? 'Có khách' : 'Trống' }}
                    </span>
                </div>
                @endforeach
            </div>

        </div>
    </div>
</div>

<<<<<<< HEAD
<form method="GET" action="{{ route('admin.dashboard') }}" id="filterForm" style="display: none;">
    <input type="hidden" name="filter" id="filterInput" value="{{ $filter ?? 'week' }}">
    <input type="hidden" name="branch_id" value="{{ $branchId ?? 1 }}">
</form>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.getElementById('timeFilterSelect').addEventListener('change', function() {
        document.getElementById('filterInput').value = this.value;
        document.getElementById('filterForm').submit();
    });

    const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctxRevenue, {
        type: 'bar',
        data: {
            labels: @json($chartLabels),
            datasets: [{ label: 'Doanh thu (VNĐ)', data: @json($chartData), backgroundColor: '#4f46e5', borderRadius: 6 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                x: { grid: { display: false } }
            }
        }
    });

    const ctxPayment = document.getElementById('paymentMethodChart').getContext('2d');
    new Chart(ctxPayment, {
        type: 'doughnut',
        data: { labels: ['Tiền mặt', 'Chuyển khoản'], datasets: [{ data: [120, 250], backgroundColor: ['#10b981', '#4f46e5'], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, cutout: '75%' }
=======
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
>>>>>>> ec82c5b08279a3957e413ae2d6242835ae535df1
    });
</script>
@endsection
