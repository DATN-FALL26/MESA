@extends('layouts.auth')

@section('content')
<div class="space-y-6">
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

    <!-- Card Tổng quan & Bộ lọc chi nhánh -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Tổng quan Hoạt động</h2>
            <p class="text-xs text-gray-500">Số liệu cập nhật tự động theo ca làm việc trong ngày.</p>
        </div>
        <div class="flex items-center gap-2 w-full md:w-auto">
            <span class="text-xs font-semibold text-gray-600 uppercase tracking-wider">Chi nhánh:</span>
            <select class="bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block p-2.5 outline-none transition">
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- 4 Thẻ Thống Kê Nổi Bật -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Doanh thu -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-indigo-100 relative overflow-hidden transition hover:shadow-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg">Doanh thu hôm nay</span>
                <div class="w-9 h-9 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">💰</div>
            </div>
            <div class="text-2xl font-extrabold text-gray-900">{{ number_format($doanhThu ?? 0) }} đ</div>
            <p class="text-xs text-gray-400 mt-1">Cập nhật trực tiếp</p>
        </div>

        <!-- Tổng đơn -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden transition hover:shadow-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-600 bg-gray-100 px-2.5 py-1 rounded-lg">Tổng đơn hàng</span>
                <div class="w-9 h-9 rounded-xl bg-gray-100 flex items-center justify-center text-gray-600">📦</div>
            </div>
            <div class="text-2xl font-extrabold text-gray-900">{{ $tongDon ?? 0 }} <span class="text-sm font-normal text-gray-500">đơn</span></div>
            <p class="text-xs text-gray-400 mt-1">Trong ngày</p>
        </div>

        <!-- Món nổi bật -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-emerald-100 relative overflow-hidden transition hover:shadow-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-lg">Món bán chạy</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">🔥</div>
            </div>
            <div class="text-lg font-bold text-gray-900 truncate">{{ $monBanChay->dish->name ?? 'Chưa có dữ liệu' }}</div>
            <p class="text-xs text-emerald-600 mt-1 font-medium">Đã bán: {{ $monBanChay->total_qty ?? 0 }} tô</p>
        </div>

        <!-- Món ít order -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-amber-100 relative overflow-hidden transition hover:shadow-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-600 bg-amber-50 px-2.5 py-1 rounded-lg">Món ít order</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">⚠️</div>
            </div>
            <div class="text-lg font-bold text-gray-900 truncate">{{ $monItOrder->dish->name ?? 'Chưa có dữ liệu' }}</div>
            <p class="text-xs text-amber-600 mt-1 font-medium">Đã bán: {{ $monItOrder->total_qty ?? 0 }} tô</p>
        </div>
    </div>

    <!-- Khu vực Biểu đồ & Theo dõi bàn ăn -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Biểu đồ Chart.js (8 cột) -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 lg:col-span-8 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-gray-900 text-base">📊 Biểu đồ doanh thu theo ca</h3>
                    <p class="text-xs text-gray-400">Phân bổ doanh thu sáng, trưa, tối trong ngày</p>
                </div>
                <span class="text-xs bg-indigo-50 text-indigo-600 px-3 py-1 rounded-full font-semibold border border-indigo-100">Trực tiếp</span>
            </div>

            <div class="relative w-full h-72 flex items-center justify-center">
                <canvas id="revenueByShiftChart"></canvas>
            </div>
        </div>

        <!-- Theo Dõi Phục Vụ Bàn Ăn (4 cột) -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 lg:col-span-4 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-900 text-base">🍽️ Theo Dõi Bàn Ăn</h3>
                <span class="text-xs text-gray-500 bg-gray-100 px-2.5 py-1 rounded-lg">Tổng: {{ count($danhSachBan ?? []) }} bàn</span>
            </div>
            <div class="space-y-3 overflow-y-auto max-h-64 pr-1">
                @forelse($danhSachBan as $ban)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100">
                        <span class="font-semibold text-gray-800 text-sm">{{ $ban->name ?? 'Bàn #' . $ban->id }}</span>
                        <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ ($ban->status ?? 'empty') == 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600' }}">
                            {{ ($ban->status ?? 'empty') == 'active' ? 'Đang phục vụ' : 'Trống' }}
                        </span>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center h-48 text-center text-gray-400">
                        <span class="text-3xl mb-2">🪑</span>
                        <p class="text-xs">Chưa cấu hình bàn ăn cho chi nhánh này.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Bảng Đơn Hàng Mới Nhất -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="font-bold text-gray-900 text-base">⚡ Đơn Hàng Mới Nhất</h3>
                <p class="text-xs text-gray-400">Các giao dịch phát sinh gần đây nhất</p>
            </div>
            <a href="{{ route('admin.reports') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition flex items-center gap-1">
                Xem báo cáo chi tiết <span>→</span>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        <th class="pb-3 pl-2">Mã đơn</th>
                        <th class="pb-3">Tổng tiền</th>
                        <th class="pb-3">Trạng thái</th>
                        <th class="pb-3 text-right pr-2">Thời gian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-sm">
                    @forelse($donHangs as $don)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="py-3.5 pl-2 font-medium text-gray-900">#{{ $don->id }}</td>
                            <td class="py-3.5 font-semibold text-indigo-600">{{ number_format($don->total_amount) }} đ</td>
                            <td class="py-3.5">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-600">Hoàn thành</span>
                            </td>
                            <td class="py-3.5 text-right pr-2 text-gray-500 text-xs">{{ $don->created_at }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-10 text-center text-gray-400 text-sm">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="text-2xl mb-1">🛒</span>
                                    <span>Chưa có đơn hàng nào hôm nay.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Khu vực bố trí biểu đồ thống kê mới (Dashboard) -->
<div class="mt-8 space-y-6">
    <div class="flex items-center justify-between">
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Phân tích Lưu lượng Khách & Dòng tiền</h3>
        <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50 px-3 py-1 rounded-full">Dữ liệu thời gian thực</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Biểu đồ khung giờ khách vào (Chiếm 2 cột) -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-800">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">Khung Giờ Khách Vào Đông / Vắng</h4>
                    <p class="text-xs text-slate-500 mt-0.5">Biểu đồ thể hiện mật độ khách hàng theo các mốc thời gian trong ngày</p>
                </div>
            </div>
            <div class="h-64">
                <canvas id="hourlyTrafficChart"></canvas>
            </div>
        </div>

        <!-- Biểu đồ Phương thức thanh toán (Chiếm 1 cột) -->
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-800 flex flex-col justify-between">
            <div>
                <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-1">Phương Thức Thanh Toán</h4>
                <p class="text-xs text-slate-500 mb-4">Tỷ lệ tiền mặt và chuyển khoản QR</p>
            </div>
            <div class="h-48 flex items-center justify-center">
                <canvas id="paymentMethodChart"></canvas>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-around text-xs font-semibold text-slate-600 dark:text-slate-400">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Tiền mặt</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Chuyển khoản</span>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. Biểu đồ khung giờ (Bar Chart)
        const ctxHourly = document.getElementById('hourlyTrafficChart').getContext('2d');
        new Chart(ctxHourly, {
            type: 'bar',
            data: {
                labels: ['8h', '10h', '12h (Trưa)', '14h', '16h', '18h (Tối)', '20h', '22h'],
                datasets: [{
                    label: 'Lượng khách',
                    data: [5, 15, 45, 10, 8, 55, 40, 10],
                    backgroundColor: '#6366f1',
                    borderRadius: 8,
                    barThickness: 'flex'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { grid: { color: 'rgba(200, 200, 200, 0.1)' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // 2. Biểu đồ thanh toán (Doughnut Chart)
        const ctxPayment = document.getElementById('paymentMethodChart').getContext('2d');
        new Chart(ctxPayment, {
            type: 'doughnut',
            data: {
                labels: ['Tiền mặt', 'Chuyển khoản'],
                datasets: [{
                    data: [120, 250],
                    backgroundColor: ['#f59e0b', '#10b981'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                cutout: '70%'
            }
        });
    });
</script>
    </div>
</div>

<!-- Render Chart.js trực tiếp tránh lỗi stack layout -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('revenueByShiftChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Ca Sáng (6h-11h)', 'Ca Trưa (11h-17h)', 'Ca Tối (17h-22h)'],
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: @json($chartData ?? [0, 0, 0]),
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.15)',
                        'rgba(99, 102, 241, 0.15)',
                        'rgba(244, 63, 94, 0.15)'
                    ],
                    borderColor: [
                        'rgb(16, 185, 129)',
                        'rgb(99, 102, 241)',
                        'rgb(244, 63, 94)'
                    ],
                    borderWidth: 2,
                    borderRadius: 8
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
                        grid: { color: 'rgba(243, 244, 246, 1)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    });
</script>
@endsection
