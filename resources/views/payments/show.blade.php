@extends('layouts.auth')

@section('title', 'Thanh toán '.$place.' | MESA')
@section('heading', 'Thanh toán · '.$place)

@section('content')
    <div class="mx-auto max-w-2xl space-y-4">
        @if ($order->session_id)
            <x-button :href="route('orders.session', $order->session_id)" variant="ghost" size="sm">← Quay lại gọi món</x-button>
        @endif

        @if ($unservedItems->isNotEmpty())
            <x-alert type="warning">
                Còn {{ $unservedItems->sum('quantity') }} món chưa phục vụ:
                {{ $unservedItems->map(fn ($orderItem) => $orderItem->quantity.'x '.$orderItem->item_name_snapshot)->join(', ') }}.
                Kiểm tra lại trước khi tính tiền.
            </x-alert>
        @endif

        <x-card :padding="false">
            <x-card.header title="Hóa đơn tạm tính" :subtitle="$place.' · '.$order->order_no">
                <x-slot:actions>
                    <x-badge color="warning">Chưa thanh toán</x-badge>
                </x-slot:actions>
            </x-card.header>

            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Món</th>
                        <th class="px-3 py-3 text-right">SL</th>
                        <th class="px-3 py-3 text-right">Đơn giá</th>
                        <th class="px-6 py-3 text-right">Thành tiền</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($billableItems as $orderItem)
                        <tr>
                            <td class="px-6 py-3">
                                <p class="font-medium text-slate-900">
                                    {{ $orderItem->item_name_snapshot }}
                                    @if ($orderItem->variant_name_snapshot)
                                        <span class="text-slate-500">({{ $orderItem->variant_name_snapshot }})</span>
                                    @endif
                                </p>
                                @foreach ($orderItem->modifiers as $modifier)
                                    <p class="text-xs text-slate-500">+ {{ $modifier->name_snapshot }}</p>
                                @endforeach
                                @if ($orderItem->status !== \App\Enums\OrderItemStatus::SERVED)
                                    <x-badge color="warning" size="sm" class="mt-1">Chưa phục vụ</x-badge>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums">{{ $orderItem->quantity }}</td>
                            <td class="px-3 py-3 text-right tabular-nums text-slate-600">{{ number_format((int) $orderItem->unit_price_snapshot, 0, ',', '.') }}đ</td>
                            <td class="px-6 py-3 text-right font-semibold tabular-nums text-slate-900">{{ number_format((int) $orderItem->line_total, 0, ',', '.') }}đ</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-slate-500">Chưa có món nào được gửi bếp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="space-y-1.5 border-t border-slate-200 px-6 py-4 text-sm">
                <div class="flex justify-between text-slate-600">
                    <span>Tạm tính</span>
                    <span class="tabular-nums">{{ number_format($subtotal, 0, ',', '.') }}đ</span>
                </div>
                @if ($discount > 0)
                    <div class="flex justify-between text-slate-600">
                        <span>Giảm giá</span>
                        <span class="tabular-nums">−{{ number_format($discount, 0, ',', '.') }}đ</span>
                    </div>
                @endif
                <div class="flex justify-between pt-1.5 text-base font-bold text-slate-900">
                    <span>Tổng cộng</span>
                    <span class="tabular-nums text-red-700">{{ number_format($total, 0, ',', '.') }}đ</span>
                </div>
            </div>
        </x-card>

        <p class="text-center text-xs text-slate-500">Chức năng thu tiền (tiền mặt / QR) và in hóa đơn đang được phát triển.</p>
    </div>
@endsection
