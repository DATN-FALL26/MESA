<!DOCTYPE html>
<html lang="vi" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) && $title ? $title . ' - ' : '' }}MESA Operations & POS</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Scripts & Styles -->
    @if (file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @endif
</head>
<body class="h-full font-sans antialiased text-slate-800 bg-slate-50 flex">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-slate-900 text-slate-200 flex flex-col shrink-0 min-h-screen border-r border-slate-800">
        <!-- Logo & Brand -->
        <div class="h-16 flex items-center gap-3 px-6 border-b border-slate-800/80 bg-slate-950/40">
            <div class="w-9 h-9 rounded-lg bg-emerald-500 text-slate-950 flex items-center justify-center font-black text-lg shadow-sm">
                M
            </div>
            <div class="flex flex-col">
                <span class="font-bold text-white tracking-wide text-base leading-tight">MESA POS</span>
                <span class="text-xs text-emerald-400 font-medium">Hệ thống Chuỗi Quán Phở</span>
            </div>
        </div>

        <!-- Branch Context Indicator -->
        <div class="px-4 py-3 border-b border-slate-800 bg-slate-900/50">
            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-400">Chi nhánh hiện tại:</span>
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-300 font-semibold border border-emerald-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    CN Ba Đình
                </span>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">

            <!-- SECTION: BÁO CÁO & QUẢN TRỊ (MODULE 9 - CỦA THUẬN) -->
            <div class="pb-1 px-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Báo cáo & Thống kê</span>
            </div>

            <!-- 1. Bảng điều khiển (Dashboard) -->
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }} transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                <span>Bảng điều khiển</span>
            </a>

            <!-- 2. Báo cáo chi tiết -->
            <a href="{{ route('admin.reports') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.reports') ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }} transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                </svg>
                <span>Báo cáo chi tiết</span>
            </a>

            <!-- SECTION: KHO & ĐỊNH LƯỢNG (MODULE 07) -->
            <div class="pt-4 pb-1 px-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Kho & Định Lượng</span>
            </div>

            <a href="{{ route('inventory.ingredients') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('inventory.ingredients*') ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }} transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                </svg>
                <span>Nguyên vật liệu</span>
            </a>

            <a href="{{ route('inventory.suppliers') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('inventory.suppliers*') ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }} transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.979-3.199a4.5 4.5 0 1 1 7.5 0M15 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                </svg>
                <span>Nhà cung cấp</span>
            </a>

            <a href="{{ route('inventory.recipes') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('inventory.recipes*') ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }} transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                </svg>
                <span>Định lượng (BOM)</span>
            </a>

            <a href="{{ route('inventory.stock-movements') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('inventory.stock-movements*') ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }} transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z" />
                </svg>
                <span>Thẻ kho & Biến động kho</span>
            </a>

            <!-- SECTION: CÁC PHÂN HỆ KHÁC -->
            <div class="pt-4 pb-1 px-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Bán hàng & Vận hành</span>
            </div>

            <a href="#" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/80 transition-colors">
                <svg class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                </svg>
                <span>POS & Sơ đồ bàn</span>
            </a>

            <a href="#" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/80 transition-colors">
                <svg class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" />
                </svg>
                <span>Màn hình Bếp (KDS)</span>
            </a>
        </nav>

        <!-- Current User Footer -->
        <div class="p-4 border-t border-slate-800 bg-slate-950/40 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-slate-700 text-slate-200 flex items-center justify-center font-semibold text-xs border border-slate-600">
                    AD
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-white">Quản trị viên</span>
                    <span class="text-[10px] text-slate-400">admin@mesa.vn</span>
                </div>
            </div>
        </div>
    </aside>

    <!-- MAIN WRAPPER -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <!-- TOPBAR -->
        <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between sticky top-0 z-30 shadow-2xs">
            <!-- Breadcrumbs / Quick Info -->
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <span class="font-medium text-slate-900">{{ $headerTitle ?? 'Hệ thống Quản lý' }}</span>
                @if(isset($headerSubtitle))
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-600">{{ $headerSubtitle }}</span>
                @endif
            </div>

            <!-- Right Actions: Low Stock Badge, Notifications, Settings -->
            <div class="flex items-center gap-4">
                <a href="{{ url('/inventory/ingredients?low_stock=1') }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 transition-colors">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span>Cảnh báo tồn kho: <strong>2 món</strong></span>
                </a>

                <div class="h-5 w-px bg-slate-200"></div>

                <div class="text-xs text-slate-500">
                    Múi giờ: <span class="font-mono text-slate-700 font-medium">Asia/Ho_Chi_Minh</span>
                </div>
            </div>
        </header>

        <!-- TOAST NOTIFICATIONS (TOP-RIGHT) -->
        <x-toast />

        <!-- MAIN PAGE CONTENT -->
        <main class="flex-1 p-6">
            @yield('content')
        </main>
    </div>

</body>
</html>
