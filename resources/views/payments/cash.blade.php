@extends('layouts.auth')

@section('title', 'Thanh toán tiền mặt '.$place.' | MESA')
@section('heading', 'Thanh toán · '.$place.($sessionCode ? ' · Lượt dùng bữa '.$sessionCode : ''))

@php
    $remaining = $summary['remaining'];
    $money = fn (int $amount): string => number_format($amount, 0, ',', '.').'đ';
    // Gợi ý tiền khách đưa: làm tròn lên các mệnh giá hay dùng
    $quickAmounts = collect([50_000, 100_000, 500_000])
        ->map(fn (int $step): int => (int) (ceil($remaining / $step) * $step))
        ->push(500_000)
        ->filter(fn (int $amount): bool => $amount > $remaining)
        ->unique()
        ->sort()
        ->take(3)
        ->values();
@endphp

@section('content')
    <div class="space-y-4">
        @include('payments.partials.steps', ['current' => 2, 'secondLabel' => 'Thanh toán tiền mặt'])

        <form method="POST" action="{{ route('payments.cash.pay', $order) }}" id="cash-form"
              data-remaining="{{ $remaining }}"
              class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
            @csrf

            <div class="flex flex-col gap-4">
                <x-card>
                    <p class="text-xs text-slate-500">{{ $summary['paid'] > 0 ? 'Còn phải trả (món gọi thêm)' : 'Tổng cần thanh toán' }}</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums text-red-700">{{ $money($remaining) }}</p>
                </x-card>

                <x-card class="flex flex-1 flex-col justify-center">
                    <label for="tendered-display" class="text-xs text-slate-500">Khách đưa</label>
                    <input type="hidden" name="tendered_amount" id="tendered-amount" value="{{ old('tendered_amount', 0) }}">
                    <div id="tendered-display"
                         class="mt-1 rounded-lg border-2 border-red-700 px-4 py-2.5 text-right text-2xl font-bold tabular-nums text-slate-900">
                        {{ $money((int) old('tendered_amount', 0)) }}
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" data-quick="{{ $remaining }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Đủ tiền</button>
                        @foreach ($quickAmounts as $amount)
                            <button type="button" data-quick="{{ $amount }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium tabular-nums text-slate-700 hover:bg-slate-50">{{ number_format($amount, 0, ',', '.') }}</button>
                        @endforeach
                    </div>
                </x-card>

                <div id="change-box" class="flex items-center justify-between rounded-xl bg-emerald-50 px-6 py-4 ring-1 ring-emerald-200">
                    <span id="change-label" class="text-sm font-medium text-emerald-800">Tiền thừa trả khách</span>
                    <span id="change-amount" class="text-2xl font-bold tabular-nums text-emerald-800">0đ</span>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <div class="grid flex-1 auto-rows-fr grid-cols-3 gap-2">
                    @foreach (['1', '2', '3', '4', '5', '6', '7', '8', '9', '000', '0'] as $key)
                        <button type="button" data-key="{{ $key }}" class="rounded-lg border border-slate-200 bg-white py-3 text-lg font-semibold tabular-nums text-slate-800 shadow-sm hover:bg-slate-50">{{ $key }}</button>
                    @endforeach
                    <button type="button" data-key="back" aria-label="Xóa" class="rounded-lg border border-slate-200 bg-white py-3 text-lg font-semibold text-slate-800 shadow-sm hover:bg-slate-50">⌫</button>
                </div>

                <button type="submit" id="confirm-cash" class="w-full rounded-xl bg-red-800 px-4 py-3 text-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50">
                    Xác nhận đã nhận tiền
                </button>
                <x-button :href="route('payments.show', $order)" variant="outline" class="w-full">Đổi phương thức</x-button>
            </div>
        </form>
    </div>

    <script>
        (() => {
            const form = document.getElementById('cash-form');
            const remaining = Number(form.dataset.remaining);
            const hidden = document.getElementById('tendered-amount');
            const display = document.getElementById('tendered-display');
            const changeBox = document.getElementById('change-box');
            const changeLabel = document.getElementById('change-label');
            const changeAmount = document.getElementById('change-amount');
            const confirmButton = document.getElementById('confirm-cash');
            const format = (value) => value.toLocaleString('vi-VN') + 'đ';
            let tendered = Number(hidden.value) || 0;
            // Mặc định 0đ: nhân viên tự bấm số tiền khách đưa.
            let isTyping = tendered > 0;

            function render() {
                hidden.value = tendered;
                display.textContent = format(tendered);
                const change = tendered - remaining;
                const isEnough = change >= 0;

                changeLabel.textContent = isEnough ? 'Tiền thừa trả khách' : 'Khách đưa còn thiếu';
                changeAmount.textContent = format(Math.abs(change));
                changeBox.className = isEnough
                    ? 'flex items-center justify-between rounded-xl bg-emerald-50 px-6 py-4 ring-1 ring-emerald-200'
                    : 'flex items-center justify-between rounded-xl bg-red-50 px-6 py-4 ring-1 ring-red-200';
                changeLabel.className = 'text-sm font-medium ' + (isEnough ? 'text-emerald-800' : 'text-red-800');
                changeAmount.className = 'text-2xl font-bold tabular-nums ' + (isEnough ? 'text-emerald-800' : 'text-red-800');
                confirmButton.disabled = ! isEnough;
            }

            form.querySelectorAll('[data-quick]').forEach((button) => button.addEventListener('click', () => {
                tendered = Number(button.dataset.quick);
                isTyping = false;
                render();
            }));

            form.querySelectorAll('[data-key]').forEach((button) => button.addEventListener('click', () => {
                const key = button.dataset.key;
                let digits = isTyping ? String(tendered) : '';

                digits = key === 'back' ? digits.slice(0, -1) : (digits + key).replace(/^0+/, '');
                tendered = Math.min(Number(digits || 0), 999999999);
                isTyping = true;
                render();
            }));

            render();
        })();
    </script>
@endsection
