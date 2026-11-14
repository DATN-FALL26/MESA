@extends('layouts.auth')

@section('title', 'Thanh toán '.$place.' | MESA')
@section('heading', 'Thanh toán · '.$place.($sessionCode ? ' · Lượt dùng bữa '.$sessionCode : ''))

@php
    $money = fn (int $amount): string => number_format($amount, 0, ',', '.').'đ';
    $isFullyPaid = $summary['remaining'] <= 0 && $summary['total'] > 0;
@endphp

@section('content')
    <div class="space-y-4">
        @include('payments.partials.steps', ['current' => 1])

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_360px]">
            {{-- Hóa đơn tạm tính --}}
            <x-card :padding="false">
                <x-card.header title="Hóa đơn tạm tính" :subtitle="$order->order_no">
                    <x-slot:actions>
                        @if ($summary['unservedItems']->isEmpty())
                            <x-badge color="success" size="sm">Tất cả món đã phục vụ</x-badge>
                        @else
                            <x-badge color="warning" size="sm">Còn {{ $summary['unservedItems']->sum('quantity') }} món chưa phục vụ</x-badge>
                        @endif
                    </x-slot:actions>
                </x-card.header>

                <table class="w-full text-sm">
                    <thead class="text-left text-xs text-slate-500">
                        <tr class="border-b border-slate-100">
                            <th class="px-6 py-2.5 font-medium">Món</th>
                            <th class="px-3 py-2.5 text-right font-medium">SL</th>
                            <th class="px-3 py-2.5 text-right font-medium">Đơn giá</th>
                            <th class="px-6 py-2.5 text-right font-medium">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($summary['items'] as $orderItem)
                            <tr>
                                <td class="px-6 py-2.5 text-slate-900">
                                    {{ $orderItem->item_name_snapshot }}
                                    @if ($orderItem->variant_name_snapshot)
                                        <span class="text-slate-500">({{ $orderItem->variant_name_snapshot }})</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-right tabular-nums">{{ $orderItem->quantity }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums text-slate-600">{{ $money((int) $orderItem->unit_price_snapshot) }}</td>
                                <td class="px-6 py-2.5 text-right tabular-nums text-slate-900">{{ $money((int) $orderItem->line_total) }}</td>
                            </tr>
                            @foreach ($orderItem->modifiers as $modifier)
                                <tr class="text-xs text-slate-500">
                                    <td class="py-1.5 pl-10 pr-6">+ {{ $modifier->name_snapshot }}</td>
                                    <td class="px-3 py-1.5 text-right tabular-nums">{{ $modifier->quantity }}</td>
                                    <td class="px-3 py-1.5 text-right tabular-nums">{{ $money((int) $modifier->price_snapshot) }}</td>
                                    <td></td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-slate-500">Chưa có món nào được gửi bếp.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="flex items-center justify-between border-t border-slate-200 px-6 py-4">
                    <span class="text-sm font-semibold text-slate-700">Tổng cộng</span>
                    <span class="text-lg font-bold tabular-nums text-slate-900">{{ $money($summary['total']) }}</span>
                </div>
            </x-card>

            {{-- Tổng cần thanh toán + chọn phương thức --}}
            <div class="space-y-4">
                <x-card>
                    @if ($summary['paid'] > 0)
                        <div class="mb-3 space-y-1 border-b border-slate-100 pb-3 text-sm">
                            <div class="flex justify-between text-slate-600"><span>Tổng cộng</span><span class="tabular-nums">{{ $money($summary['total']) }}</span></div>
                            <div class="flex justify-between text-emerald-700"><span>Đã thanh toán</span><span class="tabular-nums">−{{ $money($summary['paid']) }}</span></div>
                        </div>
                    @endif
                    <p class="text-xs text-slate-500">{{ $summary['paid'] > 0 ? 'Còn phải trả (món gọi thêm)' : 'Tổng cần thanh toán' }}</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums {{ $isFullyPaid ? 'text-emerald-700' : 'text-red-700' }}">{{ $money($summary['remaining']) }}</p>
                    @if ($isFullyPaid)
                        <x-badge color="success" class="mt-2">Đã thanh toán đủ</x-badge>
                    @endif
                </x-card>

                @if (! $isFullyPaid && $summary['remaining'] > 0)
                    @if ($canCollect)
                        <x-card>
                            <form method="POST" action="{{ route('payments.start', $order) }}" class="space-y-3">
                                @csrf
                                <p class="text-sm font-semibold text-slate-800">Phương thức thanh toán</p>
                                @foreach (['cash' => ['Tiền mặt', 'M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z'], 'qr' => ['QR / chuyển khoản', 'M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5ZM6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z']] as $method => [$label, $icon])
                                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-800 has-[:checked]:border-red-700 has-[:checked]:bg-red-50">
                                        <input type="radio" name="method" value="{{ $method }}" class="sr-only" @checked($loop->first)>
                                        <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
                                        {{ $label }}
                                    </label>
                                @endforeach
                                <button type="submit" class="mt-1 w-full rounded-xl bg-red-800 px-4 py-3 text-sm font-semibold text-white transition hover:bg-red-700">Tiếp tục thanh toán</button>
                            </form>
                            @if ($pendingQr)
                                <a href="{{ route('payments.qr', $pendingQr) }}" class="mt-2 block text-center text-xs font-medium text-red-800 hover:underline">Mở lại mã QR đang chờ thanh toán</a>
                            @endif
                        </x-card>
                    @else
                        <x-alert type="info">Tài khoản của bạn không có quyền thu tiền cho {{ $place }}.</x-alert>
                    @endif
                @endif

                @if ($order->session_id)
                    <x-button :href="route('orders.session', $order->session_id)" variant="outline" class="w-full">Quay lại order</x-button>
                @endif

                @if ($payments->isNotEmpty())
                    <x-card :padding="false">
                        <x-card.header title="Giao dịch của order" />
                        <ul class="divide-y divide-slate-100 text-sm">
                            @foreach ($payments as $payment)
                                @php
                                    $statusLabel = \App\Services\Payment\OrderPaymentService::statusLabel($payment);
                                @endphp
                                <li class="flex items-center justify-between gap-3 px-6 py-2.5">
                                    <span class="text-slate-600">
                                        {{ $payment->method === \App\Enums\PaymentMethod::CASH ? 'Tiền mặt' : 'QR' }}
                                        · {{ ($payment->paid_at ?? $payment->created_at)->format('H:i') }}
                                    </span>
                                    <span class="flex items-center gap-2">
                                        <span class="tabular-nums text-slate-900">{{ $money((int) $payment->amount) }}</span>
                                        <x-badge size="sm" :color="match ($statusLabel) { 'Thành công' => 'success', 'Đang chờ' => 'warning', default => 'danger' }">{{ $statusLabel }}</x-badge>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </x-card>
                @endif
            </div>
        </div>
    </div>
@endsection
