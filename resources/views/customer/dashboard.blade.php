<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MESA | Chi nhánh nhà hàng</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4">
            <a href="{{ route('dashboard') }}" class="font-bold tracking-widest text-slate-900">MESA</a>
            <a href="{{ route('login') }}" class="text-sm font-medium text-indigo-700">Đăng nhập</a>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8 sm:py-12">
        <section class="rounded-3xl bg-slate-900 p-7 text-white sm:p-10">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">MESA · Nhà hàng</p>
            <h1 class="mt-3 text-3xl font-bold sm:text-4xl">Chào mừng bạn</h1>
            <p class="mt-3 max-w-2xl text-slate-300">
                Xem thông tin các chi nhánh MESA. Bạn không cần tài khoản để truy cập.
            </p>
        </section>

        <section class="mt-8" aria-labelledby="branches-heading">
            <h2 id="branches-heading" class="text-xl font-bold text-slate-900">Chi nhánh MESA</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @forelse ($branches as $branch)
                    <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                        <h3 class="font-semibold text-slate-900">{{ $branch->name }}</h3>
                        @if ($branch->address)
                            <p class="mt-1 text-sm text-slate-500">{{ $branch->address }}</p>
                        @endif
                        @if ($branch->phone)
                            <p class="mt-1 text-sm text-slate-500">{{ $branch->phone }}</p>
                        @endif
                    </article>
                @empty
                    <p class="text-sm text-slate-500">Hiện chưa có chi nhánh hoạt động.</p>
                @endforelse
            </div>
        </section>
    </main>
</body>
</html>
