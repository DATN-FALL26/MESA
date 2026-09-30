<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MESA - Quản Trị Tổng Hệ Thống (CEO)</title>
    <!-- Tailwind CSS CDN (Hoặc dùng chung asset của Breeze) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome cho Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50 font-sans antialiased">

    <div class="flex h-screen overflow-hidden">

        <!-- SIDEBAR THANH MENU BÊN TRÁI -->
        <aside class="w-64 bg-slate-900 text-slate-300 flex flex-col hidden md:flex shadow-xl">
            <!-- Logo / Brand -->
            <div class="h-16 flex items-center px-6 bg-slate-950 text-white font-bold text-xl tracking-wider">
                <i class="fa-solid fa-utensils text-orange-500 mr-2"></i> MESA <span class="text-xs text-orange-400 ml-2 border border-orange-400 px-1.5 py-0.5 rounded">CEO</span>
            </div>

            <!-- Menu Links -->
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                <a href="#" class="flex items-center px-4 py-3 text-white bg-orange-600 rounded-lg shadow-md transition">
                    <i class="fa-solid fa-chart-pie w-6"></i> Tổng Quan
                </a>
                <a href="#" class="flex items-center px-4 py-3 hover:bg-slate-800 hover:text-white rounded-lg transition">
                    <i class="fa-solid fa-code-branch w-6"></i> Quản Lý Chi Nhánh
                </a>
                <a href="#" class="flex items-center px-4 py-3 hover:bg-slate-800 hover:text-white rounded-lg transition">
                    <i class="fa-solid fa-users-gear w-6"></i> Nhân Sự & Phân Quyền
                </a>
                <a href="#" class="flex items-center px-4 py-3 hover:bg-slate-800 hover:text-white rounded-lg transition">
                    <i class="fa-solid fa-book-open w-6"></i> Thực Đơn & Món Ăn
                </a>
                <a href="#" class="flex items-center px-4 py-3 hover:bg-slate-800 hover:text-white rounded-lg transition justify-between">
                    <span><i class="fa-solid fa-boxes-stacked w-6"></i> Kho & Phê Duyệt</span>
                    <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full font-bold">3</span>
                </a>
                <a href="#" class="flex items-center px-4 py-3 hover:bg-slate-800 hover:text-white rounded-lg transition">
                    <i class="fa-solid fa-chart-line w-6"></i> Báo Cáo Tài Chính
                </a>
            </nav>

            <!-- User Info & Logout Footer -->
            <div class="p-4 bg-slate-950 border-t border-slate-800 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-orange-500 flex items-center justify-center text-white font-bold">
                        H
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white">Nguyễn Văn Hoàng</p>
                        <p class="text-xs text-orange-400">CEO Hệ Thống</p>
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
                    <span class="text-gray-800 font-semibold text-lg">Xin chào, Sếp Hoàng trở lại! 👋</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-xs bg-green-100 text-green-700 font-medium px-3 py-1 rounded-full border border-green-200">
                        <i class="fa-solid fa-circle text-[8px] mr-1 text-green-500"></i> Hệ thống hoạt động ổn định
                    </span>
                </div>
            </header>

            <!-- Scrollable Content Area -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">

                <!-- 1. Thẻ Thống Kê Tổng Quan (Stat Cards) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <!-- Doanh thu -->
                    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-orange-500 flex justify-between items-center">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Doanh Thu Hôm Nay</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1">24.500.000 đ</h3>
                            <span class="text-xs text-green-600 font-semibold mt-1 inline-block"><i class="fa-solid fa-arrow-up"></i> +12% so với hôm qua</span>
                        </div>
                        <div class="w-12 h-12 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                    </div>
                    <!-- Đơn hàng -->
                    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500 flex justify-between items-center">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Tổng Đơn Hàng</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1">142 Đơn</h3>
                            <span class="text-xs text-blue-600 font-semibold mt-1 inline-block">Đang phục vụ: 12 bàn</span>
                        </div>
                        <div class="w-12 h-12 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                    </div>
                    <!-- Nhân sự -->
                    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-purple-500 flex justify-between items-center">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Nhân Sự Online</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1">18 / 22</h3>
                            <span class="text-xs text-purple-600 font-semibold mt-1 inline-block">Đúng ca trực</span>
                        </div>
                        <div class="w-12 h-12 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-user-group"></i>
                        </div>
                    </div>
                    <!-- Cần duyệt -->
                    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-red-500 flex justify-between items-center">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Phiếu Chờ Duyệt</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1">3 Phiếu Kho</h3>
                            <span class="text-xs text-red-600 font-semibold mt-1 inline-block">Cần xử lý gấp</span>
                        </div>
                        <div class="w-12 h-12 rounded-lg bg-red-50 text-red-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                    </div>
                </div>

                <!-- 2. Bảng Dữ Liệu & Tìm Kiếm (Ví dụ: Quản lý nhân sự/phân quyền) -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <!-- Header của bảng kèm ô Tìm kiếm -->
                    <div class="p-5 border-b border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">
                        <h3 class="font-bold text-gray-800 text-lg"><i class="fa-solid fa-table-list mr-2 text-orange-500"></i> Danh Sách Nhân Sự & Phân Quyền Nhanh</h3>
                        <div class="w-full md:w-72 relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <i class="fa-solid fa-search"></i>
                            </span>
                            <input type="text" placeholder="Tìm kiếm nhân viên, email..." class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                        </div>
                    </div>

                    <!-- Bảng HTML -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-xs uppercase font-semibold border-b border-gray-200">
                                    <th class="py-3 px-6">Họ và Tên</th>
                                    <th class="py-3 px-6">Email Đăng Nhập</th>
                                    <th class="py-3 px-6">Cơ Sở Trực Thuộc</th>
                                    <th class="py-3 px-6">Vai Trò (Role)</th>
                                    <th class="py-3 px-6">Trạng Thái</th>
                                    <th class="py-3 px-6 text-center">Hành Động</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-gray-200 text-gray-700">
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="py-4 px-6 font-medium text-gray-900">Nguyễn Văn Hoàng</td>
                                    <td class="py-4 px-6 text-gray-500">ceo@mesa.com</td>
                                    <td class="py-4 px-6">Toàn hệ thống (CEO)</td>
                                    <td class="py-4 px-6"><span class="bg-purple-100 text-purple-700 text-xs px-2.5 py-1 rounded-full font-bold">CEO</span></td>
                                    <td class="py-4 px-6"><span class="text-green-600 font-medium text-xs"><i class="fa-solid fa-circle text-[8px] mr-1"></i> Hoạt động</span></td>
                                    <td class="py-4 px-6 text-center">
                                        <button class="text-blue-600 hover:text-blue-800 mx-1" title="Sửa"><i class="fa-solid fa-pen-to-square"></i></button>
                                        <button class="text-red-600 hover:text-red-800 mx-1" title="Khóa"><i class="fa-solid fa-lock"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="py-4 px-6 font-medium text-gray-900">Trần Văn Quản Lý</td>
                                    <td class="py-4 px-6 text-gray-500">manager1@mesa.com</td>
                                    <td class="py-4 px-6">I-Pho Cơ sở 1 - Cầu Giấy</td>
                                    <td class="py-4 px-6"><span class="bg-blue-100 text-blue-700 text-xs px-2.5 py-1 rounded-full font-bold">Branch Manager</span></td>
                                    <td class="py-4 px-6"><span class="text-green-600 font-medium text-xs"><i class="fa-solid fa-circle text-[8px] mr-1"></i> Hoạt động</span></td>
                                    <td class="py-4 px-6 text-center">
                                        <button class="text-blue-600 hover:text-blue-800 mx-1" title="Sửa"><i class="fa-solid fa-pen-to-square"></i></button>
                                        <button class="text-red-600 hover:text-red-800 mx-1" title="Khóa"><i class="fa-solid fa-lock"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="py-4 px-6 font-medium text-gray-900">Lê Thị Phục Vụ</td>
                                    <td class="py-4 px-6 text-gray-500">waiter1@mesa.com</td>
                                    <td class="py-4 px-6">I-Pho Cơ sở 1 - Cầu Giấy</td>
                                    <td class="py-4 px-6"><span class="bg-amber-100 text-amber-700 text-xs px-2.5 py-1 rounded-full font-bold">Waiter</span></td>
                                    <td class="py-4 px-6"><span class="text-green-600 font-medium text-xs"><i class="fa-solid fa-circle text-[8px] mr-1"></i> Hoạt động</span></td>
                                    <td class="py-4 px-6 text-center">
                                        <button class="text-blue-600 hover:text-blue-800 mx-1" title="Sửa"><i class="fa-solid fa-pen-to-square"></i></button>
                                        <button class="text-red-600 hover:text-red-800 mx-1" title="Khóa"><i class="fa-solid fa-lock"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Footer phân trang -->
                    <div class="p-4 bg-gray-50 border-t border-gray-200 flex justify-between items-center text-sm text-gray-500">
                        <span>Hiển thị 3 trên tổng số 15 nhân sự</span>
                        <div class="flex space-x-1">
                            <button class="px-3 py-1 border border-gray-300 rounded bg-white hover:bg-gray-100 disabled">Trước</button>
                            <button class="px-3 py-1 border border-orange-500 bg-orange-500 text-white rounded">1</button>
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