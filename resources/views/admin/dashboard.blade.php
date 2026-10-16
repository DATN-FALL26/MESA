<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title> Dashboard -  Mesa</title>

        <!-- Tailwind CSS -->
        <script src="https://cdn.tailwindcss.com"></script>

        <!-- chart.js -->
         <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </head>

    <body class="bg-gray-100 font-sans p-6">

        <div class="max-w-6xl mx-auto space-y-6">

            <!-- Tieu de -->
            <div class="flex justify-between items-center bg-white p-5 rounded-lg shadow-sm border">
                <div>
                    <h1 class="text-2xl font-bold text-red-700">🍜 Quản Lý Phở MESA</h1>
                    <p class="text-sm text-gray-500">Module Dashboard & Báo cáo thống kê</p>
                </div>
                <span class="bg-green-100 text-green-700 font-semibold px-3 py-1 rounded-full text-sm">Online</span>
             </div>

            <div class="flex items-center gap-3">
             <!-- Chuyen sang trang reports (bao cao) -->
              <a href="admin/reports" class="bg-red-700 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-red-800 transition flex items-center gap-2 shadow-sm">
                Báo cáo thống kê
              </a>
              <span class="bg-green-100 text-green-700 font-semibold px-3 py-1.5 rounded-full text-sm">
                Online
              </span>
            </div>

             <!-- dieu huong modun khac -->
            <div class="bg-white p-5 rounded-lg shadow-sm border mb-6">
                <h3 class="font-bold text-gray-700 mb-4">Chức năng</h3>

                <div class="flex flex-wrap gap-4">

                    <a href="/admin/orders/create" class="bg-red-600 text-white px-4 py-2 rounded-lg font-semibold text-sm hover:bg-red-700 transition">
                        <i class="fa-solid fa-plus mr-1"></i> Tạo Oder
                    </a>

                    <a href="/admin/tables" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold text-sm hover:bg-blue-700 transition">
                        <i class="fa-solid fa-chair mr-1"></i> Quản Lý Bàn
                    </a>

                    <a href="/admin/dishes" class="bg-amber-600 text-white px-4 py-2 rounded-lg font-semibold text-sm hover:bg-amber-700 transition">
                        <i class="fa-solid fa-bowl-food mr-1"></i> Quản Lý Món
                    </a>

                    <a href="/admin/kds" class="bg-gray-800 text-white px-4 py-2 rounded-lg font-semibold text-sm hover:bg-gray-900 transition">
                        <i class="fa-solid fa-fire mr-1"></i> KDS
                    </a>

                    <a href="/admin/reports" class="bg-green-800 text-white px-4 py-2 rounded-lg font-semibold text-sm hover:bg-gray-900 transition">
                        <i class="fa-solid fa-fire mr-1"></i> Báo cáo thống kê
                    </a>
                </div>
            </div>

            <!-- Thong ke -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-red-600">
                    <p class="text-gray-500 text-sm">Doanh thu hôm nay</p>
                    <h2 class="text-2xl font-bold text-gray-800 mt-1"> 1,900,000 VND</h2>
                </div>
                <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-blue-600">
                    <p class="text-gray-500 text-sm">Tổng đơn hàng</p>
                    <h2 class="text-2xl font-bold text-gray-800 mt-1"> 15 Đơn </h2>
                </div>
                <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-amber-600">
                    <p class="text-gray-500 text-sm">Số bàn đang phục vụ </p>
                    <h2 class="text-2xl font-bold text-gray-800 mt-1"> 5 Bàn </h2>
                </div>
             </div>

             <!-- Bieu do va bang don hang -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Bieu do chart.js -->
                <div class="bg-white p-5 rounded-lg shadow-sm border">
                    <h3 class="font-bold text-gray-700 mb-4">Biểu đồ doanh thu theo ca</h3>
                    <canvas id="myChart" height="180"></canvas>
                </div>

                <!-- Bang don hang gan day -->
                <div class="bg-white p-5 rounded-lg shadow-sm border">
                    <h3 class="font-bold text-gray-700 mb-4">Bảng đơn hàng gần đây</h3>
                    <table>
                        <thead>
                            <tr class="bg-white p-5 rounded-lg shadow-sm border">
                                <th class="p-2">Mã đơn</th>
                                <th class="p-2">Vị trí</th>
                                <th class="p-2">Tổng tiền</th>
                                <th class="p-2">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-2" font-bold>DH001</td>
                                <td class="p-2">Bàn 1</td>
                                <td class="p-2">150,000 VND</td>
                                <td class="p-2">
                                    <span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-700">Đã hoàn thành</span>
                                </td>
                            </tr>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-2" font-bold>DH002</td>
                                <td class="p-2">Bàn 2</td>
                                <td class="p-2">200,000 VND</td>
                                <td class="p-2">
                                    <span class="px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-700">Đang xử lý</span>
                                </td>
                            </tr>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-2" font-bold>DH003</td>
                                <td class="p-2">Bàn 3</td>
                                <td class="p-2">180,000 VND</td>
                                <td class="p-2">
                                    <span class="px-2 py-0.5 rounded text-xs bg-red-100 text-red-700">Đã hủy</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Script vẽ biểu đồ bằng Chart.js -->
        <script>
            const ctx = document.getElementById('myChart');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Ca sáng', 'Ca trưa', 'Ca tối'],
                    datasets: [{
                        label: 'Doanh thu (VND)',
                        data: [500000, 800000, 600000],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        </script>
    </body>
</html>
