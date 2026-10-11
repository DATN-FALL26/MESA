<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $branch ? $branch->name.' | MESA' : 'Đặt món | MESA' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4">
            <a href="{{ route('dashboard') }}" class="font-bold tracking-widest text-slate-900">MESA</a>
            <nav class="flex items-center gap-4 text-sm">
                @auth
                    <a href="{{ route('profile') }}" class="font-medium text-indigo-700">Hồ sơ</a>
                    <a href="{{ route('dashboard') }}" class="text-slate-600">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="font-medium text-indigo-700">Đăng nhập</a>
                    <a href="{{ route('register') }}" class="text-slate-600">Tạo tài khoản</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8 sm:py-12">
        @if ($branch && $diningTable)
            <section class="rounded-3xl bg-slate-900 p-6 text-white sm:p-9">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Đặt món tại bàn</p>
                <h1 class="mt-2 text-3xl font-bold">{{ $branch->name }}</h1>
                <p class="mt-2 text-slate-300">Bàn {{ $diningTable->code }}</p>
            </section>

            @if (session('order_success'))
                <section class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-900" role="status">
                    <h2 class="font-semibold">Đã gửi đơn đến bếp</h2>
                    <p class="mt-1 text-sm">Mã đơn: <strong>{{ session('order_success.order_no') }}</strong></p>
                    <p class="text-sm">Tạm tính cần thanh toán tại quầy:
                        <strong>{{ number_format(session('order_success.total_amount')) }} đ</strong>
                    </p>
                </section>
            @endif

            @if (! $sessionIsOpen)
                <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
                    Bàn này hiện chưa có lượt phục vụ đang mở. Vui lòng báo nhân viên để bắt đầu gọi món.
                </div>
            @elseif ($items->isEmpty())
                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-600">
                    Thực đơn của chi nhánh hiện chưa có món nào để đặt.
                </div>
            @else
                <form method="POST" action="{{ $signedOrderUrl }}" class="mt-8">
                    @csrf
                    <section class="mb-8 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
                        <h2 class="text-lg font-semibold">Thông tin nhận đơn</h2>
                        <p class="mt-1 text-sm text-slate-500">Không cần tài khoản. Thanh toán tiền mặt tại quầy sau khi dùng bữa.</p>
                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="customer_name" class="mb-1 block text-sm font-medium">Tên khách hàng</label>
                                <input id="customer_name" name="customer_name" value="{{ old('customer_name', auth()->user()?->full_name) }}" required maxlength="150" class="w-full rounded-lg border-slate-300">
                            </div>
                            <div>
                                <label for="customer_phone" class="mb-1 block text-sm font-medium">Số điện thoại</label>
                                <input id="customer_phone" name="customer_phone" value="{{ old('customer_phone', auth()->user()?->phone) }}" required maxlength="20" class="w-full rounded-lg border-slate-300">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="note" class="mb-1 block text-sm font-medium">Ghi chú đơn hàng (không bắt buộc)</label>
                                <input id="note" name="note" value="{{ old('note') }}" maxlength="255" class="w-full rounded-lg border-slate-300">
                            </div>
                        </div>
                    </section>

                    @foreach ($items->groupBy(fn ($item) => $item->category->name) as $categoryName => $categoryItems)
                        <section class="mb-8">
                            <h2 class="mb-3 text-xl font-bold text-slate-900">{{ $categoryName }}</h2>
                            <div class="grid gap-4 md:grid-cols-2">
                                @foreach ($categoryItems as $item)
                                    @php
                                        $branchPrice = $item->branchMenuItems->first()?->price;
                                        $basePrice = (float) ($branchPrice ?? $item->base_price);
                                    @endphp
                                    <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <h3 class="font-semibold text-slate-900">{{ $item->name }}</h3>
                                                @if ($item->description)
                                                    <p class="mt-1 text-sm text-slate-500">{{ $item->description }}</p>
                                                @endif
                                            </div>
                                            <p class="shrink-0 font-semibold text-indigo-700">{{ number_format((int) $basePrice) }} đ</p>
                                        </div>
                                        <div class="mt-4 grid gap-3 sm:grid-cols-[1fr_6rem]">
                                            @if ($item->variants->isNotEmpty())
                                                <div>
                                                    <label for="variant-{{ $item->id }}" class="mb-1 block text-xs font-medium text-slate-600">Kích cỡ</label>
                                                    <select id="variant-{{ $item->id }}" name="items[{{ $item->id }}][variant_id]" class="w-full rounded-lg border-slate-300 text-sm">
                                                        <option value="">Mặc định</option>
                                                        @foreach ($item->variants as $variant)
                                                            <option value="{{ $variant->id }}" @selected(old("items.{$item->id}.variant_id") == $variant->id)>
                                                                {{ $variant->name }} ({{ $variant->price_delta >= 0 ? '+' : '' }}{{ number_format((int) $variant->price_delta) }} đ)
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @else
                                                <input type="hidden" name="items[{{ $item->id }}][variant_id]" value="">
                                            @endif
                                            <div>
                                                <label for="quantity-{{ $item->id }}" class="mb-1 block text-xs font-medium text-slate-600">Số lượng</label>
                                                <input id="quantity-{{ $item->id }}" type="number" name="items[{{ $item->id }}][quantity]" value="{{ old("items.{$item->id}.quantity", 0) }}" min="0" max="30" inputmode="numeric" class="w-full rounded-lg border-slate-300 text-sm">
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label for="item-note-{{ $item->id }}" class="mb-1 block text-xs font-medium text-slate-600">Ghi chú món</label>
                                                <input id="item-note-{{ $item->id }}" name="items[{{ $item->id }}][note]" value="{{ old("items.{$item->id}.note") }}" maxlength="255" placeholder="Ví dụ: ít cay, không hành" class="w-full rounded-lg border-slate-300 text-sm">
                                            </div>
                                            <input type="hidden" name="items[{{ $item->id }}][menu_item_id]" value="{{ $item->id }}">
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endforeach

                    <div class="sticky bottom-0 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur">
                        <button type="submit" class="w-full rounded-xl bg-indigo-600 px-5 py-3 font-semibold text-white hover:bg-indigo-500">
                            Gửi món đến bếp · Thanh toán tại quầy
                        </button>
                    </div>
                </form>
            @endif
        @else
            <section class="rounded-3xl bg-slate-900 p-7 text-white sm:p-10">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">MESA · Khách hàng</p>
                <h1 class="mt-3 text-3xl font-bold sm:text-4xl">Chào mừng bạn</h1>
                <p class="mt-3 max-w-2xl text-slate-300">
                    Quét mã QR tại bàn để xem thực đơn và gửi món đến bếp. Bạn không cần tạo tài khoản để đặt món.
                </p>
            </section>

            <section class="mt-8">
                <h2 class="text-xl font-bold text-slate-900">Chi nhánh MESA</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @forelse ($branches as $activeBranch)
                        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                            <h3 class="font-semibold text-slate-900">{{ $activeBranch->name }}</h3>
                            @if ($activeBranch->address)
                                <p class="mt-1 text-sm text-slate-500">{{ $activeBranch->address }}</p>
                            @endif
                            <p class="mt-3 text-sm text-indigo-700">Quét QR tại bàn để đặt món</p>
                        </article>
                    @empty
                        <p class="text-sm text-slate-500">Hiện chưa có chi nhánh hoạt động.</p>
                    @endforelse
                </div>
            </section>
        @endif

        @if ($errors->any())
            <div class="fixed inset-x-4 bottom-24 z-10 mx-auto max-w-xl rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 shadow-lg" role="alert">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </main>
</body>
</html>
