@extends('kitchen.layout')

@section('title', 'Bếp (KDS) | MESA')

@php
    $stationLabels = [
        'PHO' => 'Quầy Phở',
        'DRINK' => 'Quầy Nước',
        'SIDE' => 'Quầy Món kèm',
    ];
    $printerReady = $printerSummary['total'] > 0 && $printerSummary['ready'] === $printerSummary['total'] && $unprintedCount === 0;
    $printerLabel = match (true) {
        $printerSummary['total'] === 0 => 'chưa cấu hình quầy',
        $printerReady => 'sẵn sàng',
        $printerSummary['ready'] === 0 => 'chưa gắn máy in',
        default => $printerSummary['ready'].'/'.$printerSummary['total'].' quầy có máy in',
    };
@endphp

@section('content')
    <div class="flex min-h-screen flex-col">
        {{-- Thanh trên cùng --}}
        <header class="sticky top-0 z-20 border-b border-stone-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-800 text-sm font-black text-white">IP</span>
                    <div>
                        <p class="text-base font-bold leading-tight text-stone-900">I-Pho · Bếp (KDS)</p>
                        <p class="text-xs text-stone-500">{{ $stationCode ? ($stationLabels[$stationCode->value] ?? $stationCode->value) : 'Tất cả quầy' }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs font-medium">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 {{ $printerReady ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200' : ($unprintedCount > 0 ? 'bg-red-50 text-red-800 ring-1 ring-red-200' : 'bg-amber-50 text-amber-800 ring-1 ring-amber-200') }}"
                          title="Theo cấu hình máy in của quầy và kết quả lệnh in phiếu bếp (mô phỏng máy in nhiệt).">
                        <span class="h-2 w-2 rounded-full {{ $printerReady ? 'bg-emerald-500' : ($unprintedCount > 0 ? 'bg-red-500 animate-pulse' : 'bg-amber-500') }}"></span>
                        Máy in nhiệt: {{ $printerLabel }}@if ($unprintedCount > 0) · {{ $unprintedCount }} phiếu chưa in @endif
                    </span>
                    <span id="kds-realtime" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-emerald-800 ring-1 ring-emerald-200">
                        <span id="kds-realtime-dot" class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span id="kds-realtime-label">Realtime: đang kết nối</span>
                    </span>
                    <span id="kds-clock" class="ml-1 font-mono text-lg font-bold tabular-nums text-stone-900">{{ now()->format('H:i') }}</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-stone-100 px-4 py-2 sm:px-6">
                <nav class="flex flex-wrap gap-2" aria-label="Lọc theo quầy">
                    <a href="{{ route('kitchen.index') }}"
                       class="rounded-full px-4 py-1.5 text-sm font-semibold {{ $stationCode === null ? 'bg-stone-900 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                        Tất cả quầy
                    </a>
                    @foreach ($stationCodes as $code)
                        <a href="{{ route('kitchen.index', ['station' => $code->value]) }}"
                           class="rounded-full px-4 py-1.5 text-sm font-semibold {{ $stationCode === $code ? 'bg-stone-900 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                            {{ $stationLabels[$code->value] ?? $code->value }}
                        </a>
                    @endforeach
                </nav>

                <div class="flex items-center gap-3 text-sm">
                    <span class="hidden text-stone-500 sm:inline">{{ auth()->user()->full_name }}</span>
                    @if (auth()->user()->hasPermission('dashboard.view'))
                        <a href="{{ route('dashboard') }}" class="font-medium text-stone-600 hover:text-stone-900 hover:underline">Quản trị</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-stone-300 px-3 py-1 font-medium text-stone-700 hover:bg-stone-100">Đăng xuất</button>
                    </form>
                </div>
            </div>
        </header>

        {{-- Bảng phiếu bếp: tự làm mới mỗi 5 giây --}}
        <main class="flex-1 p-4 sm:p-6">
            <div id="kds-board"
                 data-board-url="{{ route('kitchen.board', array_filter(['station' => $stationCode?->value])) }}"
                 data-overdue-minutes="{{ $overdueMinutes }}">
                @include('kitchen.partials.board', ['columns' => $columns])
            </div>
        </main>
    </div>

    <script>
        (() => {
            const REFRESH_INTERVAL_MS = 5000;
            const board = document.getElementById('kds-board');
            const overdueMs = Number(board.dataset.overdueMinutes) * 60 * 1000;
            const clock = document.getElementById('kds-clock');
            const realtime = document.getElementById('kds-realtime');
            const realtimeDot = document.getElementById('kds-realtime-dot');
            const realtimeLabel = document.getElementById('kds-realtime-label');
            let knownTicketIds = collectTicketIds();
            let isRefreshing = false;

            function collectTicketIds() {
                return new Set([...board.querySelectorAll('[data-ticket-id]')].map((card) => card.dataset.ticketId));
            }

            function formatDuration(milliseconds) {
                const totalSeconds = Math.max(0, Math.floor(milliseconds / 1000));
                const hours = Math.floor(totalSeconds / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;
                const pad = (value) => String(value).padStart(2, '0');

                return hours > 0 ? `${hours}:${pad(minutes)}:${pad(seconds)}` : `${pad(minutes)}:${pad(seconds)}`;
            }

            /** Cập nhật đồng hồ trên từng thẻ và đánh dấu thẻ quá giờ. */
            function tickTimers() {
                const now = Date.now();

                board.querySelectorAll('[data-timer]').forEach((timer) => {
                    const card = timer.closest('[data-ticket-id]');
                    const firedAt = Date.parse(timer.dataset.firedAt);
                    const since = Date.parse(timer.dataset.since ?? timer.dataset.firedAt);
                    const isOverdue = now - firedAt >= overdueMs;
                    const label = timer.dataset.timer === 'cooking' ? 'Đang nấu' : 'Chờ';

                    timer.textContent = isOverdue
                        ? `Quá ${board.dataset.overdueMinutes} phút · ${formatDuration(now - firedAt)}`
                        : `${label} ${formatDuration(now - since)}`;
                    timer.classList.toggle('kds-chip-overdue', isOverdue);
                    card.classList.toggle('kds-card-overdue', isOverdue);
                });

                clock.textContent = new Date(now).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
            }

            function setRealtimeStatus(isConnected) {
                realtime.className = isConnected
                    ? 'inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-emerald-800 ring-1 ring-emerald-200'
                    : 'inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-rose-800 ring-1 ring-rose-200';
                realtimeDot.className = isConnected ? 'h-2 w-2 rounded-full bg-emerald-500' : 'h-2 w-2 rounded-full bg-rose-500 animate-pulse';
                realtimeLabel.textContent = isConnected ? 'Realtime: đang kết nối' : 'Realtime: mất kết nối';
            }

            /** Tải lại bảng phiếu và làm nổi bật thẻ mới xuất hiện. */
            async function refreshBoard() {
                if (isRefreshing) {
                    return;
                }

                isRefreshing = true;

                try {
                    const response = await fetch(board.dataset.boardUrl, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });

                    if (response.redirected || ! response.ok) {
                        throw new Error('Board refresh failed');
                    }

                    board.innerHTML = await response.text();

                    board.querySelectorAll('[data-ticket-id]').forEach((card) => {
                        if (! knownTicketIds.has(card.dataset.ticketId)) {
                            card.classList.add('kds-card-new');
                        }
                    });

                    knownTicketIds = collectTicketIds();
                    setRealtimeStatus(true);
                    tickTimers();
                } catch (error) {
                    setRealtimeStatus(false);
                } finally {
                    isRefreshing = false;
                }
            }

            board.addEventListener('submit', async (event) => {
                const form = event.target.closest('form[data-kds-action]');

                if (! form) {
                    return;
                }

                event.preventDefault();
                form.querySelectorAll('button').forEach((button) => {
                    button.disabled = true;
                    button.classList.add('opacity-60');
                });

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: new FormData(form),
                    });

                    if (! response.ok) {
                        const data = await response.json().catch(() => ({}));
                        alert(data.message ?? 'Không cập nhật được trạng thái. Vui lòng thử lại.');
                    }
                } catch (error) {
                    alert('Mất kết nối tới máy chủ. Vui lòng thử lại.');
                }

                await refreshBoard();
            });

            tickTimers();
            setInterval(tickTimers, 1000);
            setInterval(refreshBoard, REFRESH_INTERVAL_MS);
        })();
    </script>

    <style>
        .kds-card-overdue { box-shadow: 0 0 0 2px rgb(185 28 28); }
        .kds-chip-overdue { background-color: rgb(254 226 226) !important; color: rgb(153 27 27) !important; }
        .kds-card-new { animation: kds-flash 1.2s ease-in-out 3; }
        @keyframes kds-flash {
            0%, 100% { box-shadow: 0 0 0 0 rgb(250 204 21 / 0); }
            50% { box-shadow: 0 0 0 4px rgb(250 204 21); }
        }
    </style>
@endsection
