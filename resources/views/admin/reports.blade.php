<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Báo Cáo Thống Kê - Phở MESA</title>

        <!-- Tailwind CSS -->
        <script src="https://cdn.tailwindcss.com"></script>
        <!-- Chart.js -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </head>

    <body class="bg-gray-100 font-sans p-6">

        <div class="max-w-6xl mx-auto space-y-6">

            <!-- TIÊU ĐỀ BÁO CÁO + NÚT QUAY LẠI DASHBOARD -->
            <div class="flex justify-between items-center bg-white p-5 rounded-lg shadow-sm border">

                <div>
                    <h1 class="text-2xl font-bold text-red-800">Báo Cáo Thống Kê</h1>
                    <p class="text-sm text-gray-500">Hệ thống phân tích kinh doanh Phở MESA</p>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Nút quay về Dashboard -->
                     <a href="/dashboard" class="bg-gray-700 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-gray-800 transition flex items-center gap-2 shadow-sm">
                        Quay về Dashboard
                     </a>
                </div>

            </div>

            <!-- BỘ LỌC THỜI GIAN VÀ BÁO CÁO CHI TIẾT -->\
            <div class="bg-white p-6 rounded-lg shadow-sm border space-y-6">

                <!-- Thanh chọn bộ lọc thời gian -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b pb-4">
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">Bộ lọc thời gian</h2>
                        <p class="text-xs text-gray-500">Chọn khoảng thời gian để xem báo cáo tương ứng</p>
                    </div>

                    <div class="flex items-center gap-2 text-sm">
                         <span class="text-gray-600 font-medium">Từ ngày:</span>
                        <input type="date" value="2026-10-01" class="border rounded px-2 py-1.5 text-gray-700">
                        <span class="text-gray-600 font-medium">Đến ngày:</span>
                        <input type="date" value="2026-10-16" class="border rounded px-2 py-1.5 text-gray-700">
                        <button class="bg-red-700 text-white px-4 py-1.5 rounded font-semibold hover:bg-red-800 transition">Tải Báo Cáo</button>
                    </div>
                </div>

                <!-- 3 Thẻ tổng quan báo cáo trong kỳ -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                        <p class="text-xs text-red-600 font-bold uppercase tracking-wider">Tổng Doanh Thu Trong Kỳ</p>
                        <h3 class="text-2x1 font-black text-red-700 mt-1">34,950,000 VND</h3>
                    </div>
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                        <p class="text-xs text-blue-600 font-bold uppercase tracking-wider">Tổng Số Tô Phở Bán Ra</p>
                        <h3 class="text-2xl font-black text-blue-700 mt-1">680 Tô</h3>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                        <p class="text-xs text-green-600 font-bold uppercase tracking-wider">Giá Trị Trung Bình / Đơn</p>
                        <h3 class="text-2xl font-black text-green-700 mt-1">115,000 VND</h3>
                    </div>
                </div>

                <!-- Biểu đồ xu hướng doanh thu -->
                <div>
                    <h3 class="font-bold text-gray-700 mb-3 text-sm">Biểu đồ xu hướng doanh thu (7 ngày gần nhất)</h3>
                    <div class="bg-gray-50 p-4 rounded-lg border">
                        <canvas id="reportChart" height="90"></canvas>
                    </div>
                </div>

                <!-- Bảng báo cáo chi tiết theo món ăn -->
                <div>
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-bold text-gray-700 text-sm">Thống kê chi tiết món ăn bán chạy</h3>
                        <button class="text-xs bg-green-700 text-white px-3 py-1.5 rounded font-bold hover:bg-green-800 transition">
                            📥 Xuất Báo Cáo Excel
                        </button>
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="bg-gray-100 border-b text-gray-700">
                                    <th class="p-3">STT</th>
                                    <th class="p-3">Tên món phở / Đồ uống</th>
                                    <th class="p-3">Số lượng đã bán</th>
                                    <th class="p-3">Tổng doanh thu</th>
                                    <th class="p-3">Tỷ trọng doanh thu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-3">1</td>
                                    <td class="p-3 font-semibold text-gray-800">Phở Tái Nạm Đặc Biệt</td>
                                    <td class="p-3">320 tô</td>
                                    <td class="p-3 font-bold text-red-700">16,000,000 VND</td>
                                    <td class="p-3">45.7%</td>
                                </tr>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-3">2</td>
                                    <td class="p-3 font-semibold text-gray-800">Phở Gà Đùi Ta</td>
                                    <td class="p-3">210 tô</td>
                                    <td class="p-3 font-bold text-red-700">9,450,000 VND</td>
                                    <td class="p-3">27.0%</td>
                                </tr>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-3">3</td>
                                    <td class="p-3 font-semibold text-gray-800">Phở Bò Chín Gầu</td>
                                    <td class="p-3">150 tô</td>
                                    <td class="p-3 font-bold text-red-700">7,500,000 VND</td>
                                    <td class="p-3">21.5%</td>
                                </tr>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-3">4</td>
                                    <td class="p-3 font-semibold text-gray-800">Quẩy giòn & Trứng chần</td>
                                    <td class="p-3">400 phần</td>
                                    <td class="p-3 font-bold text-red-700">2,000,000 VND</td>
                                    <td class="p-3">5.8%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <!-- Script vẽ biểu đồ đường Line Chart cho Báo Cáo -->
        <script>
            const ctx = document.getElementById('reportChart');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['10/10', '11/10', '12/10', '13/10', '14/10', '15/10', '16/10'],
                    datasets: [{
                        label: 'Doanh thu (VND)',
                        data: [3200000, 4100000, 3900000, 4500000, 4800000, 5200000, 4900000],
                        fill: true,
                        backgroundColor: 'rgba(220, 38, 38, 0.1)',
                        borderColor: 'rgb(220, 38, 38)',
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        </script>


    </body>
</html>
