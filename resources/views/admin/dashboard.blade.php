<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard - Phở MESA</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </head>
    <body class="bg-gray-100 font-sans p-6">

        <div class="max-w-6xl mx-auto space-y-6">

            <!-- TIÊU ĐỀ + NÚT CHUYỂN SANG BÁO CÁO -->
            <div class="flex justify-between items-center bg-white p-5 rounded-lg shadow-sm border">
                <a href="route/dashboard" class="flex items-center gap-2 hover:opacity-80 transition cursor-pointer">
                    <img src="/images/logo.png" alt="Logo Phở MESA" class="w-8 h-8">
                    <span class="text-xl font-bold text-red-700">Phở MESA</span>
                </a>
                <div class="flex items-center gap-3">
                    <a href="/admin/reports" class="bg-red-700 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-red-800 transition shadow-sm">
                        📈 Báo cáo chi tiết
                    </a>
                    <span class="bg-green-100 text-green-700 font-semibold px-3 py-1.5 rounded-full text-sm">Online</span>
                </div>
            </div>

            <!-- ĐIỀU HƯỚNG MODULE KHÁC -->
            <div class="bg-white p-5 rounded-lg shadow-sm border">
                <h3 class="font-bold text-gray-700 mb-4">Chức năng hệ thống</h3>
                <div class="flex flex-wrap gap-4">
                    <a href="/admin/orders/create" class="bg-red-600 text-white px-4 py-2 rounded-lg font-semibold text-sm hover:bg-red-700 transition">Tạo Order</a>
                    <a href="/admin/tables" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold text-sm hover:bg-blue-700 transition">Quản Lý Bàn</a>
                    <a href="/admin/dishes" class="bg-amber-600 text-white px-4 py-2 rounded-lg font-semibold text-sm hover:bg-amber-700 transition">Quản Lý Món</a>
                    <a href="/admin/kds" class="bg-gray-800 text-white px-4 py-2 rounded-lg font-semibold text-sm hover:bg-gray-900 transition">KDS (Bếp)</a>
                </div>
            </div>

            <!-- THỐNG KÊ NHANH (KPI CARDS) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-red-600">
                    <p class="text-gray-500 text-sm">Doanh thu hôm nay</p>
                    <h2 class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($doanhThu) }} VND</h2>
                </div>
                <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-blue-600">
                    <p class="text-gray-500 text-sm">Tổng đơn hàng</p>
                    <h2 class="text-2xl font-bold text-gray-800 mt-1">{{ $tongDon }} đơn</h2>
                </div>
                <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-amber-600">
                    <p class="text-gray-500 text-sm">Số bàn đang phục vụ</p>
                    <h2 class="text-2xl font-bold text-gray-800 mt-1">{{ $soBanDangAn }} bàn</h2>
                </div>
            </div>

            <!-- BIỂU ĐỒ & BẢNG ĐƠN HÀNG -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-5 rounded-lg shadow-sm border">
                    <h3 class="font-bold text-gray-700 mb-4">Biểu đồ doanh thu theo ca</h3>
                    <canvas id="myChart" height="180"></canvas>
                </div>

                <div class="bg-white p-5 rounded-lg shadow-sm border overflow-x-auto">
                    <h3 class="font-bold text-gray-700 mb-4">Đơn hàng gần đây</h3>
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b text-gray-600">
                                <th class="p-2">Mã</th>
                                <th class="p-2">Vị trí</th>
                                <th class="p-2">Món</th>
                                <th class="p-2">Tổng tiền</th>
                                <th class="p-2">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($donHangs as $don)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-2 font-bold">#{{ $don->code ?? $don->id }}</td>
                                <td class="p-2">{{ $don->table->name ?? 'Mang về' }}</td>
                                <td class="p-2 font-semibold text-red-600">{{ number_format($don->total_price ?? 0) }} VND</td>
                                <td class="p-2">
                                    <span class="px-2 py-0.5 rounded text-xs {{ $don->status == 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                        {{ $don->status == 'completed' ? 'Đã xong' : 'Đang xử lý' }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="p-4 text-center text-gray-400">Chưa có đơn hàng nào hôm nay.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <script>
            const ctx = document.getElementById('myChart');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Ca sáng', 'Ca trưa', 'Ca tối'],
                    datasets: [{
                        label: 'Doanh thu (VND)',
                        data: @json($chartData ?? [0, 0, 0]),
                        backgroundColor: 'rgba(220, 38, 38, 0.7)',
                        borderColor: 'rgb(220, 38, 38)',
                        borderWidth: 1
                    }]
                },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
            });
        </script>
    </body>
</html>
