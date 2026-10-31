<!DOCTYPE html>
<html lang="vi" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title . ' - ' : '' }}MESA Operations & POS</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased text-slate-800 bg-slate-50 lg:flex">

    <!-- SIDEBAR -->
    <aside id="mesa-sidebar" class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-slate-800 bg-slate-900 text-slate-200 transition-transform duration-200 lg:static lg:min-h-screen lg:w-64 lg:shrink-0 lg:translate-x-0">
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
            <!-- Tổng quan -->
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                <span>Bảng điều khiển</span>
            </a>

            <!-- SECTION: KHO & ĐỊNH LƯỢNG (MODULE 07) -->
            <div class="pt-4 pb-1 px-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Kho & Định Lượng</span>
            </div>

            <a href="{{ url('/inventory/ingredients') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->is('inventory/ingredients*') ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }} transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                </svg>
                <span>Nguyên vật liệu</span>
            </a>

            <a href="{{ url('/inventory/recipes') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->is('inventory/recipes*') ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }} transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                </svg>
                <span>Định lượng (BOM)</span>
            </a>

            <a href="{{ url('/inventory/purchase-orders') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->is('inventory/purchase-orders*') ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }} transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25V3.75m0 3.75a2.25 2.25 0 0 1-2.25 2.25h-4.5a2.25 2.25 0 0 1-2.25-2.25V3.75m9 3.75h3" />
                </svg>
                <span>Nhập hàng (PO)</span>
            </a>

            <a href="{{ url('/inventory/stock-adjustments') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->is('inventory/stock-adjustments*') ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }} transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                </svg>
                <span>Kiểm kê & Điều chỉnh</span>
            </a>

            <a href="{{ url('/inventory/stock-movements') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->is('inventory/stock-movements*') ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }} transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z" />
                </svg>
                <span>Thẻ kho (Sổ cái)</span>
            </a>

            <!-- SECTION: CÁC PHÂN HỆ KHÁC -->
            <div class="pt-4 pb-1 px-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Bán hàng & Vận hành</span>
            </div>

            <a href="{{ route('tables.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('tables.*') ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }} transition-colors">
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
                    {{ strtoupper(substr(auth()->user()->full_name ?: auth()->user()->username, 0, 2)) }}
                </div>
                <div class="flex flex-col">
                    <span class="max-w-36 truncate text-xs font-semibold text-white">{{ auth()->user()->full_name }}</span>
                    <span class="text-[10px] text-slate-400">{{ auth()->user()->username }}</span>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-slate-800 hover:text-white" aria-label="Đăng xuất" title="Đăng xuất">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3-3H9m0 0 3-3m-3 3 3 3" />
                    </svg>
                </button>
            </form>
        </div>
    </aside>
    <button id="mesa-sidebar-backdrop" type="button" class="fixed inset-0 z-40 hidden bg-slate-950/50 lg:hidden" aria-label="Đóng menu"></button>

    <!-- MAIN WRAPPER -->
    <div class="flex min-h-screen min-w-0 flex-1 flex-col">
        <!-- TOPBAR -->
        <header class="sticky top-0 z-30 flex min-h-16 items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 shadow-2xs sm:px-6">
            <!-- Breadcrumbs / Quick Info -->
            <div class="flex min-w-0 items-center gap-3 text-sm text-slate-500">
                <button id="mesa-sidebar-toggle" type="button" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 lg:hidden" aria-label="Mở menu" aria-expanded="false" aria-controls="mesa-sidebar">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                </button>
                <div class="min-w-0">
                    <span class="block truncate font-semibold text-slate-900">{{ $headerTitle ?? 'Hệ thống Quản lý' }}</span>
                @if(isset($headerSubtitle))
                    <span class="hidden text-slate-300 sm:inline">/</span>
                    <span class="hidden text-slate-600 sm:inline">{{ $headerSubtitle }}</span>
                @endif
                </div>
            </div>

            <!-- Right Actions: Low Stock Badge, Notifications, Settings -->
            <div class="flex shrink-0 items-center gap-3">
                <div class="hidden text-right sm:block">
                    <p class="text-xs font-semibold text-slate-800">{{ auth()->user()->full_name }}</p>
                    <p class="text-[11px] text-slate-500">Nhân viên vận hành</p>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-50 text-xs font-bold text-emerald-700 ring-1 ring-emerald-100">
                    {{ strtoupper(substr(auth()->user()->full_name ?: auth()->user()->username, 0, 1)) }}
                </div>
            </div>
        </header>

        <!-- TOAST NOTIFICATIONS (TOP-RIGHT) -->
        <x-toast />


        <!-- MAIN PAGE CONTENT -->
        <main class="flex-1 p-4 sm:p-6 xl:p-8">
            {{ $slot }}
        </main>
    </div>

    <script>
        (() => {
            const sidebar = document.getElementById('mesa-sidebar');
            const backdrop = document.getElementById('mesa-sidebar-backdrop');
            const toggle = document.getElementById('mesa-sidebar-toggle');

            const setSidebarOpen = (open) => {
                sidebar.classList.toggle('-translate-x-full', !open);
                backdrop.classList.toggle('hidden', !open);
                toggle.setAttribute('aria-expanded', String(open));
            };

            toggle.addEventListener('click', () => setSidebarOpen(toggle.getAttribute('aria-expanded') !== 'true'));
            backdrop.addEventListener('click', () => setSidebarOpen(false));
            window.addEventListener('resize', () => {
                if (window.innerWidth >= 1024) setSidebarOpen(false);
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
