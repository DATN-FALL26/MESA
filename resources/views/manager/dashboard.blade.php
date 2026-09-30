<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MESA - Quản Trị Cơ Sở (Branch Manager)</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome cho Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans antialiased">

    <div class="flex h-screen overflow-hidden">
        
        <!-- SIDEBAR THANH MENU BÊN TRÁI (Dành riêng cho Quản lý chi nhánh) -->
        <aside class="w-64 bg-slate-800 text-slate-300 flex flex-col hidden md:flex shadow-xl">
            <!-- Logo / Brand -->
            <div class="h-16 flex items-center px-6 bg-slate-900 text-white font-bold text-xl tracking-wider">
                <i class="fa-solid fa-store text-blue-500 mr-2"></i> MESA <span class="text-xs text-blue-400 ml-2 border border-blue-400 px-1.5 py-0.5 rounded">MANAGER</span>
            </div>

            <!-- Menu Links -->
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                <a href="#" class="flex items-center px-4 py-3 text-white bg-blue-600 rounded-lg shadow-md transition">
                    <i class="fa-solid fa-chart-line w-6"></i> Tổng Quan Cơ Sở
                </a>
                <a href="#" class="flex items-center px-4 py-3 hover:bg-slate-700 hover:text-white rounded-lg transition">
                    <i class="fa-solid fa-chair w-6"></i> Quản Lý Bàn Ăn
                </a>
                <a href="#" class="flex items-center px-4 py-3 hover:bg-slate-700 hover:text-white rounded-lg transition">
                    <i class="fa-solid fa-clipboard-list w-6"></i> Giám Sát Đơn Hàng
                </a>
                <a href="#" class="flex items-center px-4 py-3 hover:bg-slate-700 hover:text-white rounded-lg transition">
                    <i class="fa-solid fa-id-badge w-6"></i> Nhân Viên Tại Ca
                </a>
                <a href="#" class="flex items-center px-4 py-3 hover:bg-slate-700 hover:text-white rounded-lg transition">
                    <i class="fa-solid fa-box-archive w-6"></i> Đề Xuất Nhập Kho
                </a>
            </nav>

            <!-- User Info & Logout Footer -->
            <div class="p-4 bg-slate-900 border-t border-slate-700 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                        Q
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white">Trần Văn Quản Lý</p>
                        <p class="text-xs text-blue-300">Cơ sở: Cầu Giấy</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-slate-400 hover:text-red-400 transition" title="Đăng xuất">
                        <i class="fa-solid fa-right-from-bracket text-lg"></i>
                    </button>
                </form>
            </div>
        </aside>

        <!-- MAIN CONTENT BÊN PHẢI -->
        <div class="flex-1 flex flex-col overflow-hidden">
            
            <!-- Top Header -->
            <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
                <div class="flex items-center space-x-3">
                    <span class="text-gray-800 font-semibold text-lg">Khu vực quản lý: <span class="text-blue-600">I-Pho Chi nhánh 1 (Cầu Giấy)</span></span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-xs bg-blue-50 text-blue-700 font-medium px-3 py-1 rounded-full border border-blue-200">
                        <i class="fa-solid fa-circle text-[8px] mr-1 text-blue-500"></i> Ca trực: Sáng (08:00 - 16:00)
                    </span>
                </div>
            </header>

            <!-- Scrollable Content Area -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                
                <!-- 1. Thẻ Thống Kê Nhanh Tại Cơ Sở -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <!-- Doanh thu chi nhánh -->
                    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500 flex justify-between items-center">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Doanh Thu Cơ Sở</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1">12.800.000 đ</h3>
                            <span class="text-xs text-green-600 font-semibold mt-1 inline-block"><i class="fa-solid fa-arrow-up"></i> Đang khách đông</span>
                        </div>
                        <div class="w-12 h-12 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-cash-register"></i>
                        </div>
                    </div>
                    <!-- Trạng thái bàn -->
                    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-amber-500 flex justify-between items-center">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Tình Trạng Bàn</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1">8 / 15 Bàn</h3>
                            <span class="text-xs text-amber-600 font-semibold mt-1 inline-block">Đang có khách ngồi</span>
                        </div>
                        <div class="w-12 h-12 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-chair"></i>
                        </div>
                    </div>
                    <!-- Nhân viên ca trực -->
                    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-emerald-500 flex justify-between items-center">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Nhân Viên Trực</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1">6 Người</h3>
                            <span class="text-xs text-emerald-600 font-semibold mt-1 inline-block">Đầy đủ các bộ phận</span>
                        </div>
                        <div class="w-12 h-12 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-users"></i>
                        </div>
                    </div>
                    <!-- Đơn hàng chờ xử lý -->
                    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-purple-500 flex justify-between items-center">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Đơn Đang Chế Biến</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1">4 Đơn</h3>
                            <span class="text-xs text-purple-600 font-semibold mt-1 inline-block">Bếp đang thực hiện</span>
                        </div>
                        <div class="w-12 h-12 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-fire-burner"></i>
                        </div>
                    </div>
                </div>

                <!-- 2. Bảng Giám Sát Bàn Ăn & Trạng Thái Tại Cơ Sở -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <!-- Header của bảng kèm tìm kiếm -->
                    <div class="p-5 border-b border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">
                        <h3 class="font-bold text-gray-800 text-lg"><i class="fa-solid fa-clipboard-user mr-2 text-blue-500"></i> Sơ Đồ Trạng Thái Bàn Ăn Tại Cơ Sở</h3>
                        <div class="w-full md:w-72 relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <i class="fa-solid fa-search"></i>
                            </span>
                            <input type="text" placeholder="Tìm số bàn, tên khách..." class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <!-- Bảng HTML -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-xs uppercase font-semibold border-b border-gray-200">
                                    <th class="py-3 px-6">Số Bàn</th>
                                    <th class="py-3 px-6">Khu Vực</th>
                                    <th class="py-3 px-6">Trạng Thái Bàn</th>
                                    <th class="py-3 px-6">Nhân Viên Phục Vụ</th>
                                    <th class="py-3 px-6">Tổng Tiền Tạm Tính</th>
                                    <th class="py-3 px-6 text-center">Hành Động</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-gray-200 text-gray-700">
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="py-4 px-6 font-bold text-gray-900">Bàn 01</td>
                                    <td class="py-4 px-6">Tầng 1 (Sảnh chính)</td>
                                    <td class="py-4 px-6"><span class="bg-red-100 text-red-700 text-xs px-2.5 py-1 rounded-full font-bold">Đang dùng bữa</span></td>
                                    <td class="py-4 px-6">Lê Thị Phục Vụ</td>
                                    <td class="py-4 px-6 font-semibold text-gray-900">350.000 đ</td>
                                    <td class="py-4 px-6 text-center">
                                        <button class="text-blue-600 hover:text-blue-800 px-2 py-1 text-xs font-semibold bg-blue-50 rounded border border-blue-200">Xem Chi Tiết</button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="py-4 px-6 font-bold text-gray-900">Bàn 02</td>
                                    <td class="py-4 px-6">Tầng 1 (Sảnh chính)</td>
                                    <td class="py-4 px-6"><span class="bg-green-100 text-green-700 text-xs px-2.5 py-1 rounded-full font-bold">Bàn Trống</span></td>
                                    <td class="py-4 px-6">-</td>
                                    <td class="py-4 px-6 text-gray-400">0 đ</td>
                                    <td class="py-4 px-6 text-center">
                                        <button class="text-gray-500 hover:text-gray-800 px-2 py-1 text-xs font-semibold bg-gray-100 rounded border border-gray-200" disabled>Trống</button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="py-4 px-6 font-bold text-gray-900">Bàn 03</td>
                                    <td class="py-4 px-6">Tầng 2 (Phòng máy lạnh)</td>
                                    <td class="py-4 px-6"><span class="bg-red-100 text-red-700 text-xs px-2.5 py-1 rounded-full font-bold">Đang dùng bữa</span></td>
                                    <td class="py-4 px-6">Lê Thị Phục Vụ</td>
                                    <td class="py-4 px-6 font-semibold text-gray-900">820.000 đ</td>
                                    <td class="py-4 px-6 text-center">
                                        <button class="text-blue-600 hover:text-blue-800 px-2 py-1 text-xs font-semibold bg-blue-50 rounded border border-blue-200">Xem Chi Tiết</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Footer phân trang -->
                    <div class="p-4 bg-gray-50 border-t border-gray-200 flex justify-between items-center text-sm text-gray-500">
                        <span>Hiển thị 3 trên tổng số 15 bàn của cơ sở</span>
                        <div class="flex space-x-1">
                            <button class="px-3 py-1 border border-gray-300 rounded bg-white hover:bg-gray-100">Trước</button>
                            <button class="px-3 py-1 border border-blue-500 bg-blue-500 text-white rounded">1</button>
                            <button class="px-3 py-1 border border-gray-300 rounded bg-white hover:bg-gray-100">2</button>
                            <button class="px-3 py-1 border border-gray-300 rounded bg-white hover:bg-gray-100">Sau</button>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

</body>
</html>