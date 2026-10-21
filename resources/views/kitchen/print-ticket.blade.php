@php
    $order = $ticket->order;
    $tableCodes = $order->session?->sessionTables
        ->map(fn ($sessionTable) => $sessionTable->diningTable?->code)
        ->filter()
        ->join(', ');
    $waiter = $order->creator ?? $order->session?->openedByUser;
    $printStatus = match (true) {
        $printJob === null => 'missing',
        $printJob->status === \App\Enums\PrintJobStatus::PRINTED => 'printed',
        default => 'failed',
    };
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Phiếu bếp #{{ $ticket->id }} | MESA</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f5f5f4; font-family: ui-monospace, 'Courier New', monospace; color: #1c1917; }
        .page { display: flex; flex-direction: column; align-items: center; gap: 12px; padding: 24px 12px; }
        .ticket { width: 80mm; max-width: 100%; background: #fff; padding: 14px 12px; box-shadow: 0 1px 3px rgb(0 0 0 / .12); }
        .center { text-align: center; }
        .title { font-size: 14px; font-weight: 700; letter-spacing: .12em; }
        .table { margin-top: 4px; font-size: 26px; font-weight: 800; letter-spacing: .04em; }
        .meta { display: flex; justify-content: space-between; margin-top: 8px; font-size: 11px; color: #57534e; }
        .rule { border: 0; border-top: 1px dashed #a8a29e; margin: 10px 0; }
        .item { font-size: 15px; font-weight: 700; margin-top: 6px; }
        .sub { font-size: 12px; color: #44403c; padding-left: 26px; margin-top: 2px; }
        .note { font-size: 12px; color: #44403c; padding-left: 26px; margin-top: 2px; font-style: italic; }
        .order-note { font-size: 12px; margin-top: 8px; }
        .footer { font-size: 11px; color: #57534e; text-align: center; }
        .status { width: 80mm; max-width: 100%; font-family: system-ui, sans-serif; font-size: 13px; padding: 8px 12px; border-radius: 8px; }
        .status-ok { background: #ecfdf5; color: #065f46; }
        .status-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .actions { width: 80mm; max-width: 100%; display: flex; gap: 8px; font-family: system-ui, sans-serif; }
        .actions button { flex: 1; padding: 9px; border-radius: 8px; border: 1px solid #d6d3d1; background: #fff; font-weight: 600; cursor: pointer; }
        .actions button.primary { background: #991b1b; border-color: #991b1b; color: #fff; }
        @media print {
            @page { size: 80mm auto; margin: 0; }
            body { background: #fff; }
            .page { padding: 0; }
            .ticket { box-shadow: none; }
            .status, .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="page">
        <article class="ticket">
            <p class="center title">PHIẾU BẾP</p>
            <p class="center table">{{ filled($tableCodes) ? 'BÀN '.$tableCodes : 'MANG VỀ' }}</p>
            <div class="meta">
                <span>Order #{{ str_pad((string) $order->id, 4, '0', STR_PAD_LEFT) }}</span>
                <span>{{ $ticket->station->name }}</span>
                <span>{{ $ticket->fired_at->format('H:i') }}</span>
            </div>

            <hr class="rule">

            @foreach ($ticket->items as $ticketItem)
                @php
                    $orderItem = $ticketItem->orderItem;
                @endphp
                <p class="item">
                    {{ $orderItem->quantity }}x {{ $orderItem->item_name_snapshot }}@if ($orderItem->variant_name_snapshot) ({{ $orderItem->variant_name_snapshot }})@endif
                </p>
                @foreach ($orderItem->modifiers as $modifier)
                    <p class="sub">+ {{ $modifier->quantity > 1 ? $modifier->quantity.'x ' : '' }}{{ $modifier->name_snapshot }}</p>
                @endforeach
                @if ($orderItem->note)
                    <p class="note">Ghi chú: {{ $orderItem->note }}</p>
                @endif
            @endforeach

            @if ($order->note)
                <p class="order-note">Ghi chú đơn: {{ $order->note }}</p>
            @endif

            <hr class="rule">

            <p class="footer">
                In lúc {{ now()->format('H:i') }} · Phục vụ: {{ $waiter?->full_name ?? 'Khách đặt QR' }}
                @if ($printJob && $printJob->retry_count > 0)
                    <br>In lại lần {{ $printJob->retry_count }}
                @endif
            </p>
        </article>

        @if ($printStatus === 'printed')
            <p class="status status-ok">● Đã gửi lệnh in tới {{ $ticket->station->printer->name }}.</p>
        @elseif ($printStatus === 'failed')
            <p class="status status-error">⚠ Máy in {{ $ticket->station->printer->name }} đang lỗi / mất kết nối. Bấm "In qua trình duyệt" để in tay.</p>
        @else
            <p class="status status-error">⚠ {{ $ticket->station->name }} chưa gắn máy in. Bấm "In qua trình duyệt" để in tay.</p>
        @endif

        <div class="actions">
            <button type="button" class="primary" onclick="window.print()">In qua trình duyệt</button>
            <button type="button" onclick="window.close()">Đóng</button>
        </div>
    </div>

    <script>
        window.addEventListener('load', () => window.print());
    </script>
</body>
</html>
