@php
    $boardColumns = [
        'new' => ['title' => 'Mới', 'badge' => 'bg-red-800 text-white', 'empty' => 'Chưa có order mới.'],
        'preparing' => ['title' => 'Đang nấu', 'badge' => 'bg-amber-700 text-white', 'empty' => 'Chưa có món đang nấu.'],
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

<div class="grid gap-4 lg:grid-cols-3">
    @foreach ($boardColumns as $columnKey => $column)
        @php
            $tickets = $columns[$columnKey];
        @endphp

        <section class="flex min-h-[60vh] flex-col rounded-2xl bg-stone-200/60 p-3" aria-label="{{ $column['title'] }}">
            <header class="mb-3 flex items-center justify-between px-1">
                <h2 class="text-sm font-bold uppercase tracking-wide text-stone-700">{{ $column['title'] }}</h2>
                <span class="flex h-6 min-w-6 items-center justify-center rounded-full px-2 text-xs font-bold {{ $column['badge'] }}">{{ $tickets->count() }}</span>
            </header>

            <div class="flex flex-1 flex-col gap-3">
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
                    @endphp

                    <article data-ticket-id="{{ $ticket->id }}"
                             class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-stone-200 {{ $isOverdue ? 'kds-card-overdue' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-lg font-bold leading-tight text-stone-900">
                                    {{ filled($tableCodes) ? 'Bàn '.$tableCodes : 'Mang về' }}
                                </p>
                                <p class="mt-0.5 text-xs text-stone-500">
                                    {{ $ticket->station->name }} · #{{ $ticket->id }} · Gửi {{ $ticket->fired_at->format('H:i') }}
                                </p>
                            </div>

                            @if ($columnKey === 'done')
                                <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800">
                                    Xong sau {{ $formatDuration((int) $ticket->fired_at->diffInSeconds($ticket->ready_at ?? now())) }}
                                </span>
                            @else
                                <span data-timer="{{ $columnKey === 'preparing' ? 'cooking' : 'waiting' }}"
                                      data-fired-at="{{ $ticket->fired_at->toIso8601String() }}"
                                      data-since="{{ ($columnKey === 'preparing' ? $startedAt : $ticket->fired_at)->toIso8601String() }}"
                                      class="shrink-0 rounded-full px-2.5 py-1 font-mono text-xs font-semibold tabular-nums {{ $columnKey === 'preparing' ? 'bg-amber-50 text-amber-800' : 'bg-stone-100 text-stone-700' }} {{ $isOverdue ? 'kds-chip-overdue' : '' }}">
                                    @if ($isOverdue)
                                        Quá {{ $overdueMinutes }} phút · {{ $formatDuration($elapsedSeconds) }}
                                    @else
                                        {{ $columnKey === 'preparing' ? 'Đang nấu' : 'Chờ' }}
                                        {{ $formatDuration((int) ($columnKey === 'preparing' ? $startedAt : $ticket->fired_at)->diffInSeconds(now())) }}
                                    @endif
                                </span>
                            @endif
                        </div>

                        @if ($order->note)
                            <p class="mt-2 rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs text-amber-900">Ghi chú đơn: {{ $order->note }}</p>
                        @endif

                        <ul class="mt-3 space-y-1.5 text-sm">
                            @foreach ($ticket->items as $ticketItem)
                                @php
                                    $orderItem = $ticketItem->orderItem;
                                @endphp
                                <li>
                                    <p class="{{ $columnKey !== 'done' && $ticketItem->status === \App\Enums\TicketItemStatus::READY ? 'text-stone-400 line-through' : 'text-stone-900' }}">
                                        <span class="font-bold">{{ $orderItem->quantity }}x</span>
                                        <span class="font-medium">{{ $orderItem->item_name_snapshot }}</span>
                                        @if ($orderItem->variant_name_snapshot)
                                            <span class="text-stone-500">({{ $orderItem->variant_name_snapshot }})</span>
                                        @endif
                                        @if ($orderItem->note)
                                            <span class="font-medium text-red-700">· {{ $orderItem->note }}</span>
                                        @endif
                                    </p>
                                    @foreach ($orderItem->modifiers as $modifier)
                                        <p class="pl-5 text-xs text-stone-500">
                                            + {{ $modifier->quantity > 1 ? $modifier->quantity.'x ' : '' }}{{ $modifier->name_snapshot }}
                                        </p>
                                    @endforeach
                                </li>
                            @endforeach
                        </ul>

                        {{-- INV-17: trạng thái in phiếu bếp --}}
                        @if ($columnKey !== 'done')
                            @php
                                $printJob = $printJobs->get($ticket->id);
                                $printStatus = match (true) {
                                    $printJob === null && $ticket->station->printer === null => 'missing',
                                    $printJob === null => 'pending',
                                    $printJob->status === \App\Enums\PrintJobStatus::PRINTED => 'printed',
                                    default => 'failed',
                                };
                            @endphp
                            <div data-print-status="{{ $printStatus }}"
                                 class="mt-3 flex items-center justify-between gap-2 rounded-lg px-3 py-2 text-xs {{ $printStatus === 'printed' ? 'bg-stone-50 text-stone-600' : 'bg-red-50 text-red-800 ring-1 ring-red-200' }}">
                                <span class="font-medium">
                                    @if ($printStatus === 'printed')
                                        <span class="text-emerald-600" aria-hidden="true">●</span>
                                        {{ $printJob->retry_count > 0 ? 'Đã in lại' : 'Đã in tự động' }} lúc {{ $printJob->printed_at->format('H:i') }}
                                    @elseif ($printStatus === 'failed')
                                        ⚠ Máy in lỗi / mất kết nối – chưa in được phiếu
                                    @elseif ($printStatus === 'pending')
                                        ⚠ Phiếu chưa được in
                                    @else
                                        ⚠ Quầy chưa gắn máy in – cần in tay
                                    @endif
                                </span>
                                @if (auth()->user()->hasPermission('kitchen.print'))
                                    <form method="POST" action="{{ route('kitchen.tickets.print', $ticket) }}" target="_blank">
                                        @csrf
                                        <button type="submit"
                                                class="shrink-0 rounded-md border px-2.5 py-1 font-semibold {{ $printStatus === 'printed' ? 'border-stone-300 bg-white text-stone-700 hover:bg-stone-100' : 'border-red-300 bg-white text-red-800 hover:bg-red-100' }}">
                                            In lại phiếu
                                        </button>
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
                                <p class="mt-3 flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-800">
                                    <span aria-hidden="true">✓</span>
                                    Đã phục vụ lúc {{ $servedItems->max(fn ($ticketItem) => $ticketItem->orderItem->updated_at)?->format('H:i') }}
                                </p>
                            @else
                                <p class="mt-3 flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800">
                                    <span aria-hidden="true">🔔</span>
                                    Đã báo phục vụ – đang chờ mang món ra bàn
                                </p>
                            @endif
                        @else
                            <form method="POST" action="{{ route('kitchen.tickets.advance', $ticket) }}" data-kds-action class="mt-3">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="w-full rounded-lg py-2.5 text-sm font-bold text-white shadow-sm transition {{ $columnKey === 'new' ? 'bg-red-800 hover:bg-red-700' : 'bg-emerald-800 hover:bg-emerald-700' }}">
                                    {{ $columnKey === 'new' ? 'Bắt đầu nấu' : 'Hoàn thành' }}
                                </button>
                            </form>
                        @endif
                    </article>
                @empty
                    <p class="rounded-xl border-2 border-dashed border-stone-300 px-4 py-10 text-center text-sm text-stone-500">{{ $column['empty'] }}</p>
                @endforelse
            </div>
        </section>
    @endforeach
</div>
