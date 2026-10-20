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
                    @if (auth()->user()->hasPermission('dashboard.view'))
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
                    @if (auth()->user()->hasPermission('users.view'))
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
                    @if (auth()->user()->hasPermission('roles.view'))
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
                    @if (auth()->user()->hasPermission('permissions.view'))
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
                    @if (auth()->user()->hasPermission('profile.view'))
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
                </nav>
            </div>

            {{-- User Profile & Logout Footer ở cuối Sidebar --}}
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
                    <div class="flex items-center gap-2 border-l border-slate-200 pl-2 dark:border-slate-700">
                        <span
                            class="hidden text-xs font-semibold text-slate-700 dark:text-slate-200 md:inline">{{ auth()->user()->full_name }}</span>
                    </div>
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
