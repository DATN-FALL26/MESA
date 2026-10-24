@php
    $boardColumns = [
        'new' => ['title' => 'Mới', 'badge' => 'bg-red-800 text-white', 'empty' => 'Chưa có order mới.'],
        'preparing' => ['title' => 'Đang nấu', 'badge' => 'bg-amber-700 text-white', 'empty' => 'Chưa có món đang nấu.'],
        'quick' => ['title' => 'Nước & Quẩy', 'badge' => 'bg-sky-700 text-white', 'empty' => 'Không có nước / quẩy cần chuẩn bị.'],
        'done' => ['title' => 'Hoàn thành', 'badge' => 'bg-emerald-800 text-white', 'empty' => 'Chưa có phiếu hoàn thành.'],
    ];
    $overdueSeconds = $overdueMinutes * 60;
    $formatDuration = static function (int $seconds): string {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return $hours > 0
            ? sprintf('%d:%02d:%02d', $hours, $minutes, $seconds % 60)
            : sprintf('%02d:%02d', $minutes, $seconds % 60);
    };
@endphp

{{-- Số bát phở chưa nấu / đã nấu hôm nay, để thanh trên cùng cập nhật mỗi lần tự làm mới --}}
<span id="kds-pho-stats" hidden data-pending="{{ $phoBowlStats['pending'] }}" data-done="{{ $phoBowlStats['done'] }}"></span>

<div class="grid gap-3 md:grid-cols-4">
    @foreach ($boardColumns as $columnKey => $column)
        @php
            $tickets = $columns[$columnKey];
        @endphp

        <section class="flex min-h-[60vh] flex-col rounded-2xl bg-slate-200/60 p-2.5" aria-label="{{ $column['title'] }}">
            <header class="mb-2.5 flex items-center justify-between px-1">
                <h2 class="text-xs font-bold uppercase tracking-wide text-slate-700">{{ $column['title'] }}</h2>
                <span class="flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[11px] font-bold {{ $column['badge'] }}">{{ $tickets->count() }}</span>
            </header>

            <div class="flex flex-1 flex-col gap-2.5">
                @forelse ($tickets as $ticket)
                    @php
                        $order = $ticket->order;
                        $tableCodes = $order->session?->sessionTables
                            ->map(fn ($sessionTable) => $sessionTable->diningTable?->code)
                            ->filter()
                            ->join(', ');
                        $elapsedSeconds = (int) $ticket->fired_at->diffInSeconds(now());
                        $isOverdue = $columnKey !== 'done' && $elapsedSeconds >= $overdueSeconds;
                        $startedAt = $ticket->items->pluck('started_at')->filter()->min() ?? $ticket->fired_at;
                        $isServedTicket = $columnKey === 'done'
                            && $ticket->items->every(fn ($ticketItem) => $ticketItem->orderItem->status === \App\Enums\OrderItemStatus::SERVED);
                    @endphp

                    {{-- Cột Nước & Quẩy: thẻ gọn 1 dòng, bấm "Xong" là hoàn thành --}}
                    @if ($columnKey === 'quick')
                        <article data-ticket-id="{{ $ticket->id }}"
                                 class="flex items-center justify-between gap-2 rounded-lg bg-white px-3 py-2 shadow-sm ring-1 ring-slate-200 {{ $isOverdue ? 'kds-card-overdue' : '' }}">
                            <div class="min-w-0">
                                <p class="flex flex-wrap items-center gap-x-2 text-sm font-bold text-slate-900">
                                    {{ filled($tableCodes) ? 'Bàn '.$tableCodes : 'Mang về' }}
                                    <span data-timer="waiting" data-label="Chờ"
                                          data-fired-at="{{ $ticket->fired_at->toIso8601String() }}"
                                          data-since="{{ $ticket->fired_at->toIso8601String() }}"
                                          class="rounded-full bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] font-semibold tabular-nums text-slate-600 {{ $isOverdue ? 'kds-chip-overdue' : '' }}">
                                        Chờ {{ $formatDuration($elapsedSeconds) }}
                                    </span>
                                </p>
                                <p class="truncate text-xs text-slate-700">
                                    {{ $ticket->items->map(fn ($ticketItem) => $ticketItem->orderItem->quantity.'x '.$ticketItem->orderItem->item_name_snapshot)->join(', ') }}
                                </p>
                            </div>
                            <form method="POST" action="{{ route('kitchen.tickets.advance', $ticket) }}" data-kds-action class="shrink-0">
                                @csrf
                                @method('PATCH')
                                <x-button type="submit" variant="primary" size="sm" class="font-bold">Xong</x-button>
                            </form>
                        </article>
                        @continue
                    @endif

                    <article data-ticket-id="{{ $ticket->id }}"
                             class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-200 transition-opacity {{ $isOverdue ? 'kds-card-overdue' : '' }} {{ $isServedTicket ? 'opacity-50' : '' }}">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-base font-bold leading-tight text-slate-900">
                                    {{ filled($tableCodes) ? 'Bàn '.$tableCodes : 'Mang về' }}
                                </p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ $ticket->station->name }} · #{{ $ticket->id }} · Gửi {{ $ticket->fired_at->format('H:i') }}
                                </p>
                            </div>

                            @if ($columnKey === 'done')
                                <span class="shrink-0 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-800">
                                    Xong sau {{ $formatDuration((int) $ticket->fired_at->diffInSeconds($ticket->ready_at ?? now())) }}
                                </span>
                            @else
                                @php
                                    $isReadyMadeTicket = $ticket->items->every(
                                        fn ($ticketItem) => \App\Services\Kitchen\KitchenTicketDispatcher::isReadyMade($ticketItem->orderItem)
                                    );
                                    $timerLabel = match (true) {
                                        $columnKey !== 'preparing' => 'Chờ',
                                        $isReadyMadeTicket => 'Đang chuẩn bị',
                                        default => 'Đang nấu',
                                    };
                                @endphp
                                <span data-timer="{{ $columnKey === 'preparing' ? 'cooking' : 'waiting' }}"
                                      data-label="{{ $timerLabel }}"
                                      data-fired-at="{{ $ticket->fired_at->toIso8601String() }}"
                                      data-since="{{ ($columnKey === 'preparing' ? $startedAt : $ticket->fired_at)->toIso8601String() }}"
                                      class="shrink-0 rounded-full px-2 py-0.5 font-mono text-[11px] font-semibold tabular-nums {{ $columnKey === 'preparing' ? 'bg-amber-50 text-amber-800' : 'bg-slate-100 text-slate-700' }} {{ $isOverdue ? 'kds-chip-overdue' : '' }}">
                                    @if ($isOverdue)
                                        Quá {{ $overdueMinutes }} phút · {{ $formatDuration($elapsedSeconds) }}
                                    @else
                                        {{ $timerLabel }}
                                        {{ $formatDuration((int) ($columnKey === 'preparing' ? $startedAt : $ticket->fired_at)->diffInSeconds(now())) }}
                                    @endif
                                </span>
                            @endif
                        </div>

                        @if ($order->note)
                            <p class="mt-2 rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs text-amber-900">Ghi chú đơn: {{ $order->note }}</p>
                        @endif

                        <ul class="mt-2 space-y-1 text-sm">
                            @foreach ($ticket->items as $ticketItem)
                                @php
                                    $orderItem = $ticketItem->orderItem;
                                @endphp
                                <li>
                                    <p class="{{ $columnKey !== 'done' && $ticketItem->status === \App\Enums\TicketItemStatus::READY ? 'text-slate-400 line-through' : 'text-slate-900' }}">
                                        <span class="font-bold">{{ $orderItem->quantity }}x</span>
                                        <span class="font-medium">{{ $orderItem->item_name_snapshot }}</span>
                                        @if ($orderItem->variant_name_snapshot)
                                            <span class="text-slate-500">({{ $orderItem->variant_name_snapshot }})</span>
                                        @endif
                                        @if ($orderItem->note)
                                            <span class="font-medium text-red-700">· {{ $orderItem->note }}</span>
                                        @endif
                                    </p>
                                    @foreach ($orderItem->modifiers as $modifier)
                                        <p class="pl-5 text-xs text-slate-500">
                                            + {{ $modifier->quantity > 1 ? $modifier->quantity.'x ' : '' }}{{ $modifier->name_snapshot }}
                                        </p>
                                    @endforeach
                                </li>
                            @endforeach
                        </ul>

                        {{-- INV-17: trạng thái in phiếu trả món (in sau khi bếp bấm Hoàn thành) --}}
                        @if ($columnKey === 'done')
                            @php
                                $printJob = $printJobs->get($ticket->id);
                                $printStatus = match (true) {
                                    $printJob === null => 'missing',
                                    $printJob->status === \App\Enums\PrintJobStatus::PRINTED => 'printed',
                                    default => 'failed',
                                };
                            @endphp
                            <div data-print-status="{{ $printStatus }}"
                                 class="mt-2 flex items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-[11px] {{ $printStatus === 'printed' ? 'bg-slate-50 text-slate-600' : 'bg-red-50 text-red-800 ring-1 ring-red-200' }}">
                                <span class="font-medium">
                                    @if ($printStatus === 'printed')
                                        <span class="text-emerald-600" aria-hidden="true">●</span>
                                        {{ $printJob->retry_count > 0 ? 'Đã in lại' : 'Đã in tự động' }} lúc {{ $printJob->printed_at->format('H:i') }}
                                    @elseif ($printStatus === 'failed')
                                        ⚠ Máy in lỗi / mất kết nối – chưa in được phiếu
                                    @else
                                        ⚠ Chưa in được phiếu – chi nhánh chưa có máy in bếp
                                    @endif
                                </span>
                                @if (auth()->user()->hasPermission('kitchen.print'))
                                    <form method="POST" action="{{ route('kitchen.tickets.print', $ticket) }}" target="_blank">
                                        @csrf
                                        <x-button type="submit" :variant="$printStatus === 'printed' ? 'outline' : 'danger'" size="xs" class="shrink-0">
                                            In lại phiếu
                                        </x-button>
                                    </form>
                                @endif
                            </div>
                        @endif

                        @if ($columnKey === 'done')
                            {{-- INV-18: trạng thái phục vụ mang món ra bàn --}}
                            @php
                                $servedItems = $ticket->items->filter(fn ($ticketItem) => $ticketItem->orderItem->status === \App\Enums\OrderItemStatus::SERVED);
                                $isServed = $servedItems->count() === $ticket->items->count();
                            @endphp
                            @if ($isServed)
                                <p class="mt-2 flex items-center gap-2 rounded-lg bg-emerald-50 px-2.5 py-1.5 text-[11px] font-medium text-emerald-800">
                                    <span aria-hidden="true">✓</span>
                                    Đã phục vụ lúc {{ $servedItems->max(fn ($ticketItem) => $ticketItem->orderItem->updated_at)?->format('H:i') }}
                                </p>
                            @else
                                <p class="mt-2 flex items-center gap-2 rounded-lg bg-amber-50 px-2.5 py-1.5 text-[11px] font-medium text-amber-800">
                                    <span aria-hidden="true">🔔</span>
                                    Đã báo phục vụ – đang chờ mang món ra bàn
                                </p>
                            @endif
                        @else
                            <form method="POST" action="{{ route('kitchen.tickets.advance', $ticket) }}" data-kds-action class="mt-2.5">
                                @csrf
                                @method('PATCH')
                                <x-button type="submit" :variant="$columnKey === 'new' ? 'danger' : 'primary'" size="md" class="w-full font-bold">
                                    {{ $columnKey === 'new' ? 'Bắt đầu nấu' : 'Hoàn thành' }}
                                </x-button>
                            </form>
                        @endif
                    </article>
                @empty
                    <p class="rounded-xl border-2 border-dashed border-slate-300 px-3 py-8 text-center text-xs text-slate-500">{{ $column['empty'] }}</p>
                @endforelse
            </div>
        </section>
    @endforeach
</div>
