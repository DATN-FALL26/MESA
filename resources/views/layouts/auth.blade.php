<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'MESA')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
    <div class="min-h-screen lg:flex">
        <aside class="bg-slate-900 px-5 py-6 text-white lg:min-h-screen lg:w-64">
            <a href="{{ route('dashboard') }}" class="block">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">MESA</p>
                <p class="mt-1 text-xl font-semibold">Quản trị hệ thống</p>
            </a>

            <nav class="mt-8 space-y-1" aria-label="Điều hướng chính">
                @if (auth()->user()->hasPermission('dashboard.view'))
                    <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-800">Dashboard</a>
                @endif
                @if (auth()->user()->hasPermission('users.view'))
                    <a href="{{ route('users.index') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-800">Tài khoản</a>
                @endif
                @if (auth()->user()->hasPermission('roles.view'))
                    <a href="{{ route('roles.index') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-800">Vai trò</a>
                @endif
                @if (auth()->user()->hasPermission('permissions.view'))
                    <a href="{{ route('permissions.index') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-800">Quyền</a>
                @endif
                @if (auth()->user()->hasPermission('profile.view'))
                    <a href="{{ route('profile') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-800">Hồ sơ cá nhân</a>
                @endif
            </nav>

            <div class="mt-8 border-t border-slate-700 pt-5">
                <p class="truncate text-sm font-medium">{{ auth()->user()->full_name }}</p>
                <p class="truncate text-xs text-slate-400">{{ auth()->user()->username }}</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="rounded-lg border border-slate-600 px-3 py-2 text-sm hover:bg-slate-800">Đăng xuất</button>
                </form>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="border-b border-slate-200 bg-white px-6 py-5">
                <h1 class="text-xl font-semibold text-slate-900">@yield('heading', 'MESA')</h1>
            </header>
            <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                @if (session('success'))
                    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
