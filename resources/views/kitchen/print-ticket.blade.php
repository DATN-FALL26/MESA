@php
    $order = $ticket->order;
    $session = $order->session;
    $tableCodes = $session?->sessionTables
        ->map(fn ($sessionTable) => $sessionTable->diningTable?->code)
        ->filter()
        ->join(', ');
    $stationLabel = match ($ticket->station->station_code) {
        \App\Enums\StationCode::PHO => 'PHỞ',
        \App\Enums\StationCode::DRINK => 'NƯỚC',
        \App\Enums\StationCode::SIDE => 'MÓN KÈM',
        default => mb_strtoupper($ticket->station->name),
    };
    $waiter = $order->creator ?? $session?->openedByUser;
    $orderCode = $order->created_at->format('Ymd').'-'.str_pad((string) $order->id, 4, '0', STR_PAD_LEFT);
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Phiếu trả món {{ $stationLabel }} {{ $tableCodes }} | MESA</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f1f5f9; font-family: 'Segoe UI', Arial, sans-serif; color: #0f172a; }
        .page { display: flex; flex-direction: column; align-items: center; gap: 12px; padding: 24px 12px; }
        .slip { width: 80mm; max-width: 100%; background: #fff; padding: 14px 14px 12px; box-shadow: 0 1px 3px rgb(0 0 0 / .12); }
        .title { margin: 0; text-align: center; font-size: 30px; font-weight: 600; letter-spacing: .02em; }
        .code { margin: 2px 0 10px; text-align: center; font-size: 12px; color: #475569; }
        .info { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
        .info p { margin: 2px 0; font-size: 12px; }
        .info span { color: #475569; }
        .stamp { flex-shrink: 0; align-self: center; border: 2px solid #0f172a; padding: 4px 8px; font-size: 13px; font-weight: 700; letter-spacing: .04em; transform: rotate(-4deg); }
        table { width: 100%; margin-top: 10px; border-collapse: collapse; }
        th { font-size: 11px; font-weight: 500; color: #475569; text-align: left; padding: 4px 0; border-bottom: 1px solid #0f172a; }
        th.qty, td.qty { width: 34px; text-align: right; }
        td { padding: 6px 0; vertical-align: top; border-bottom: 1px dashed #cbd5e1; }
        .item { font-size: 16px; font-weight: 600; text-transform: uppercase; }
        .item small { font-size: 12px; font-weight: 400; text-transform: none; color: #475569; }
        .sub { margin-top: 2px; font-size: 12px; color: #334155; }
        td.qty { font-size: 18px; font-weight: 700; }
        .order-note { margin: 8px 0 0; font-size: 12px; }
        .footer { margin: 10px 0 0; font-size: 11px; color: #475569; text-align: center; }
        .actions { width: 80mm; max-width: 100%; display: flex; gap: 8px; }
        .actions button { flex: 1; padding: 11px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: #0f172a; font-size: 15px; font-weight: 700; cursor: pointer; }
        .actions button:hover { background: #f8fafc; }
        .actions button.primary { border-color: #059669; background: #059669; color: #fff; }
        .actions button.primary:hover { background: #047857; }
        @media print {
            @page { size: 80mm auto; margin: 0; }
            body { background: #fff; }
            .page { padding: 0; }
            .slip { box-shadow: none; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="page">
        <article class="slip">
            <p class="title">{{ $stationLabel }} {{ filled($tableCodes) ? $tableCodes : '· MANG VỀ' }}</p>
            <p class="code">Mã ĐH: {{ $orderCode }}</p>

            <div class="info">
                <div>
                    <p><span>Số người:</span> {{ $session?->guest_count ?? '–' }}</p>
                    <p><span>Nhân viên:</span> {{ $waiter?->full_name ?? '–' }}</p>
                    <p><span>Bắt đầu:</span> {{ $ticket->fired_at->format('H:i d/m/Y') }}</p>
                    <p><span>Trả món:</span> {{ ($ticket->ready_at ?? now())->format('H:i d/m/Y') }}</p>
                </div>
                <div class="stamp">ĐÃ XONG</div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Tên sản phẩm</th>
                        <th class="qty">SL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ticket->items as $ticketItem)
                        @php
                            $orderItem = $ticketItem->orderItem;
                        @endphp
                        <tr>
                            <td>
                                <div class="item">
                                    {{ $orderItem->item_name_snapshot }}
                                    @if ($orderItem->variant_name_snapshot)
                                        <small>({{ $orderItem->variant_name_snapshot }})</small>
                                    @endif
                                </div>
                                @foreach ($orderItem->modifiers as $modifier)
                                    <div class="sub">+ {{ $modifier->quantity > 1 ? $modifier->quantity.'x ' : '' }}{{ $modifier->name_snapshot }}</div>
                                @endforeach
                                @if ($orderItem->note)
                                    <div class="sub">Ghi chú: {{ $orderItem->note }}</div>
                                @endif
                            </td>
                            <td class="qty">{{ $orderItem->quantity }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($order->note)
                <p class="order-note">Ghi chú đơn: {{ $order->note }}</p>
            @endif

            <p class="footer">
                {{ $ticket->station->name }} · In lúc {{ now()->format('H:i d/m/Y') }}
                @if ($printJob && $printJob->retry_count > 0)
                    <br>In lại lần {{ $printJob->retry_count }}
                @endif
            </p>
        </article>

        <div class="actions">
            <button type="button" class="primary" onclick="window.print()">In phiếu</button>
            <button type="button" onclick="window.close()">Đóng</button>
        </div>
    </div>

    <script>
        // In xong thì tự đóng tab phiếu.
        window.addEventListener('afterprint', () => window.close());
    </script>
</body>
</html>
