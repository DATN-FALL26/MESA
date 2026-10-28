<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'MESA Admin Dashboard')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-full bg-slate-100 font-sans antialiased text-slate-800 selection:bg-indigo-500 selection:text-white dark:bg-slate-950">

    <div class="min-h-screen lg:flex">

        {{-- ================= SIDEBAR TRÁI (HIỆN ĐẠI & CHUYÊN NGHIỆP) ================= --}}
        <aside
            class="flex flex-col gap-5 border-b border-slate-200 bg-white px-4 py-4 text-slate-700 shadow-sm dark:bg-slate-900 dark:text-slate-300 lg:min-h-screen lg:w-64 lg:shrink-0 lg:justify-between lg:gap-0 lg:border-b-0 lg:border-r lg:border-slate-800 lg:bg-slate-950 lg:px-5 lg:py-6 lg:shadow-none">

            <div class="space-y-4 lg:space-y-8">
                {{-- Logo / Brand Header --}}
                <a href="{{ route('dashboard') }}" class="group flex items-center gap-3.5 px-2">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-white shadow-lg shadow-indigo-500/30 transition-transform duration-300 group-hover:scale-105">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-[0.25em] text-indigo-400">Hệ thống MESA
                        </div>
                        <div class="text-lg font-bold tracking-tight text-slate-900 lg:text-white">Quản trị hệ thống</div>
                    </div>
                </a>

                {{-- Navigation Menu --}}
                <nav class="flex gap-2 overflow-x-auto pb-1 lg:block lg:space-y-1.5 lg:overflow-visible lg:pb-0" aria-label="Điều hướng chính">
                    <div class="hidden px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500 lg:block">Menu quản
                        lý</div>

                    @php
                        $currentRoute = Route::currentRouteName();
                    @endphp

                    {{-- Dashboard --}}
                    @if (!auth()->check() || auth()->user()->hasPermission('dashboard.view'))
                        <a href="{{ route('dashboard') }}"
                            class="group flex shrink-0 items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200 {{ $currentRoute === 'dashboard' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 lg:text-slate-300 lg:hover:bg-slate-800 lg:hover:text-white' }}">
                            <svg class="h-5 w-5 transition-colors {{ $currentRoute === 'dashboard' ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                            </svg>
                            <span>Dashboard</span>
                        </a>
                    @endif

                    {{-- Tài khoản --}}
                    @if (auth()->check() && auth()->user()->hasPermission('users.view'))
                        <a href="{{ route('users.index') }}"
                            class="group flex shrink-0 items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200 {{ str_starts_with($currentRoute, 'users') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 lg:text-slate-300 lg:hover:bg-slate-800 lg:hover:text-white' }}">
                            <svg class="h-5 w-5 transition-colors {{ str_starts_with($currentRoute, 'users') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                            <span>Quản lý tài khoản</span>
                        </a>
                    @endif

                    {{-- Vai trò --}}
                    @if (auth()->check() && auth()->user()->hasPermission('roles.view'))
                        <a href="{{ route('roles.index') }}"
                            class="group flex shrink-0 items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200 {{ str_starts_with($currentRoute, 'roles') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 lg:text-slate-300 lg:hover:bg-slate-800 lg:hover:text-white' }}">
                            <svg class="h-5 w-5 transition-colors {{ str_starts_with($currentRoute, 'roles') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                            </svg>
                            <span>Quản lý vai trò</span>
                        </a>
                    @endif

                    {{-- Quyền --}}
                    @if (auth()->check() && auth()->user()->hasPermission('permissions.view'))
                        <a href="{{ route('permissions.index') }}"
                            class="group flex shrink-0 items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200 {{ str_starts_with($currentRoute, 'permissions') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 lg:text-slate-300 lg:hover:bg-slate-800 lg:hover:text-white' }}">
                            <svg class="h-5 w-5 transition-colors {{ str_starts_with($currentRoute, 'permissions') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                            <span>Danh mục quyền</span>
                        </a>
                    @endif

                    {{-- Hồ sơ cá nhân --}}
                    @if (auth()->check() && auth()->user()->hasPermission('profile.view'))
                        <a href="{{ route('profile') }}"
                            class="group flex shrink-0 items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200 {{ $currentRoute === 'profile' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 lg:text-slate-300 lg:hover:bg-slate-800 lg:hover:text-white' }}">
                            <svg class="h-5 w-5 transition-colors {{ $currentRoute === 'profile' ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <span>Hồ sơ cá nhân</span>
                        </a>
                    @endif

                    {{-- ================= MENU KHO & ĐỊNH LƯỢNG (BOM) ================= --}}
                    <div class="hidden px-3 pt-4 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500 lg:block">
                        Kho & Định lượng
                    </div>

                    {{-- Nguyên vật liệu --}}
                    @if (!auth()->check() || auth()->user()->hasPermission('inventory.view'))
                        <a href="{{ route('inventory.ingredients') }}"
                            class="group flex shrink-0 items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200 {{ str_starts_with($currentRoute, 'inventory.ingredients') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 lg:text-slate-300 lg:hover:bg-slate-800 lg:hover:text-white' }}">
                            <svg class="h-5 w-5 transition-colors {{ str_starts_with($currentRoute, 'inventory.ingredients') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                            </svg>
                            <span>Nguyên vật liệu</span>
                        </a>
                    @endif

                    {{-- Định lượng BOM --}}
                    @if (!auth()->check() || auth()->user()->hasPermission('recipe.manage'))
                        <a href="{{ route('inventory.recipes') }}"
                            class="group flex shrink-0 items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200 {{ str_starts_with($currentRoute, 'inventory.recipes') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 lg:text-slate-300 lg:hover:bg-slate-800 lg:hover:text-white' }}">
                            <svg class="h-5 w-5 transition-colors {{ str_starts_with($currentRoute, 'inventory.recipes') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                            </svg>
                            <span>Định lượng (BOM)</span>
                        </a>
                    @endif

                    {{-- Nhà cung cấp --}}
                    @if (!auth()->check() || auth()->user()->hasPermission('supplier.manage'))
                        <a href="{{ route('inventory.suppliers') }}"
                            class="group flex shrink-0 items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200 {{ str_starts_with($currentRoute, 'inventory.suppliers') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 lg:text-slate-300 lg:hover:bg-slate-800 lg:hover:text-white' }}">
                            <svg class="h-5 w-5 transition-colors {{ str_starts_with($currentRoute, 'inventory.suppliers') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.979-3.199a4.5 4.5 0 1 1 7.5 0M15 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                            </svg>
                            <span>Nhà cung cấp</span>
                        </a>
                    @endif

                    {{-- Thẻ kho & Biến động kho --}}
                    @if (!auth()->check() || auth()->user()->hasPermission('inventory.view'))
                        <a href="{{ route('inventory.stock-movements') }}"
                            class="group flex shrink-0 items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200 {{ str_starts_with($currentRoute, 'inventory.stock-movements') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 lg:text-slate-300 lg:hover:bg-slate-800 lg:hover:text-white' }}">
                            <svg class="h-5 w-5 transition-colors {{ str_starts_with($currentRoute, 'inventory.stock-movements') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z" />
                            </svg>
                            <span>Thẻ kho & Biến động</span>
                        </a>
                    @endif

                    {{-- Đơn nhập hàng (PO) --}}
                    @if (!auth()->check() || auth()->user()->hasPermission('purchase.create'))
                        <a href="{{ route('inventory.purchase-orders') }}"
                            class="group flex shrink-0 items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200 {{ str_starts_with($currentRoute, 'inventory.purchase-orders') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 lg:text-slate-300 lg:hover:bg-slate-800 lg:hover:text-white' }}">
                            <svg class="h-5 w-5 transition-colors {{ str_starts_with($currentRoute, 'inventory.purchase-orders') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.948c0-.621-.504-1.125-1.125-1.125H2.25c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75" />
                            </svg>
                            <span>Đơn nhập hàng (PO)</span>
                        </a>
                    @endif

                    {{-- Kiểm kê & Cân đối kho --}}
                    @if (!auth()->check() || auth()->user()->hasPermission('inventory.adjust'))
                        <a href="{{ route('inventory.adjustments') }}"
                            class="group flex shrink-0 items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200 {{ str_starts_with($currentRoute, 'inventory.adjustments') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 lg:text-slate-300 lg:hover:bg-slate-800 lg:hover:text-white' }}">
                            <svg class="h-5 w-5 transition-colors {{ str_starts_with($currentRoute, 'inventory.adjustments') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                            </svg>
                            <span>Kiểm kê & Điều chỉnh</span>
                        </a>
                    @endif

                    {{-- ================= MENU BẾP (MOD04) ================= --}}
                    @if (auth()->check() && auth()->user()->hasAnyPermission(['kitchen.view', 'order.serve']))
                        <div class="hidden px-3 pt-4 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500 lg:block">
                            Bếp
                        </div>
                    @endif

                    {{-- Màn hình bếp (KDS) --}}
                    @if (auth()->check() && auth()->user()->hasPermission('kitchen.view'))
                        <a href="{{ route('kitchen.index') }}"
                            class="group flex shrink-0 items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200 {{ in_array($currentRoute, ['kitchen.index', 'kitchen.board'], true) ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 lg:text-slate-300 lg:hover:bg-slate-800 lg:hover:text-white' }}">
                            <svg class="h-5 w-5 transition-colors {{ in_array($currentRoute, ['kitchen.index', 'kitchen.board'], true) ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" />
                            </svg>
                            <span>Màn hình bếp (KDS)</span>
                        </a>
                    @endif

                    {{-- Trang ra món (phục vụ) --}}
                    @if (auth()->check() && auth()->user()->hasPermission('order.serve'))
                        <a href="{{ route('kitchen.serve.index') }}"
                            class="group flex shrink-0 items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200 {{ str_starts_with((string) $currentRoute, 'kitchen.serve') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 lg:text-slate-300 lg:hover:bg-slate-800 lg:hover:text-white' }}">
                            <svg class="h-5 w-5 transition-colors {{ str_starts_with((string) $currentRoute, 'kitchen.serve') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                            </svg>
                            <span>Trang ra món</span>
                        </a>
                    @endif
                </nav>
            </div>

            {{-- User Profile & Logout Footer ở cuối Sidebar --}}
            @if (auth()->check())
            <div class="mt-8 rounded-2xl bg-slate-800/60 p-4 ring-1 ring-slate-700/60 backdrop-blur-md">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-500/20 font-bold text-indigo-400 ring-1 ring-indigo-500/30">
                        {{ strtoupper(substr(auth()->user()->full_name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-bold text-white">{{ auth()->user()->full_name }}</p>
                        <p class="truncate text-[11px] text-slate-400">&#64;{{ auth()->user()->username }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="mt-3.5">
                    @csrf
                    <button type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-200 transition-all hover:bg-red-500/10 hover:border-red-500/30 hover:text-red-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                        </svg>
                        <span>Đăng xuất hệ thống</span>
                    </button>
                </form>
            </div>
            @else
            <div class="mt-8 rounded-2xl bg-slate-800/60 p-4 ring-1 ring-slate-700/60 backdrop-blur-md">
                <a href="{{ route('login') }}" class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-lg shadow-indigo-600/30 transition-all hover:bg-indigo-500">
                    <span>Đăng nhập hệ thống</span>
                </a>
            </div>
            @endif

        </aside>

        {{-- ================= KHUNG NỘI DUNG CHÍNH BÊN PHẢI ================= --}}
        <div class="min-w-0 flex-1 flex flex-col min-h-screen">

            {{-- Header Phải Chuyên Nghiệp --}}
            <header
                class="sticky top-0 z-30 flex items-center justify-between border-b border-slate-200/80 bg-white/80 px-4 py-4 backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/80 sm:px-8">
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                        @yield('heading', 'Dashboard')
                    </h1>
                </div>

                <div class="flex items-center gap-4">
                    {{-- Thời gian hệ thống nhỏ gọn hoặc Badge trực tuyến --}}
                    <div
                        class="hidden items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 sm:flex">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>{{ now()->format('d/m/Y') }}</span>
                    </div>

                    {{-- Avatar nhanh trên Header mobile/desktop --}}
                    @if (auth()->check())
                    <div class="flex items-center gap-2 border-l border-slate-200 pl-2 dark:border-slate-700">
                        <span
                            class="hidden text-xs font-semibold text-slate-700 dark:text-slate-200 md:inline">{{ auth()->user()->full_name }}</span>
                    </div>
                    @endif
                </div>
            </header>

            {{-- Main Content Container --}}
            <main class="flex-1 mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {{-- Session Success Alert --}}
                @if (session('success'))
                    <div
                        class="mb-6 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 shadow-sm">
                        <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24"
                            stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span class="font-medium">{{ session('success') }}</span>
                    </div>
                @endif

                {{-- Validation Errors Alert --}}
                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 shadow-sm">
                        <div class="flex items-center gap-2 font-semibold mb-1">
                            <svg class="h-5 w-5 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                            <span>Vui lòng kiểm tra lại các lỗi sau:</span>
                        </div>
                        <ul class="list-disc space-y-1 pl-7 text-xs text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Nội dung view con đổ vào đây --}}
                @yield('content')
            </main>

            {{-- Footer nhỏ dưới cùng --}}
            <footer class="border-t border-slate-200 bg-white py-4 text-center text-xs text-slate-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
                &copy; {{ date('Y') }} <strong class="text-slate-700 dark:text-slate-200">MESA Management System</strong>. All rights
                reserved.
            </footer>
        </div>

    </div>

</body>

</html>
