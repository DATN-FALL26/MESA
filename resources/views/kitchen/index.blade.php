@extends('kitchen.layout')

@section('title', 'Bếp (KDS) | MESA')

@php
    $stationLabels = [
        'PHO' => 'Quầy Phở',
        'DRINK' => 'Quầy Nước',
        'SIDE' => 'Quầy Món kèm',
    ];
@endphp

@section('content')
    <div class="flex min-h-screen flex-col">
        {{-- Thanh trên cùng --}}
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-800 text-sm font-black text-white">IP</span>
                    <div>
                        <p class="text-base font-bold leading-tight text-slate-900">I-Pho · Bếp (KDS)</p>
                        <p class="text-xs text-slate-500">{{ $stationCode ? ($stationLabels[$stationCode->value] ?? $stationCode->value) : 'Tất cả quầy' }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs font-medium">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 ring-1 ring-slate-200"
                          title="Chỉ tính phở (quầy Phở), theo số bát. Chưa nấu: đang ở cột Mới và Đang nấu. Đã nấu: hoàn thành trong hôm nay.">
                        <span class="font-semibold text-slate-700">Phở</span>
                        <span class="text-red-800">Chưa nấu: <strong id="kds-pending-count" class="tabular-nums">{{ $phoBowlStats['pending'] }}</strong> bát</span>
                        <span class="h-3 w-px bg-slate-200"></span>
                        <span class="text-emerald-800">Đã nấu: <strong id="kds-done-count" class="tabular-nums">{{ $phoBowlStats['done'] }}</strong> bát</span>
                    </span>
                    <span id="kds-realtime" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-emerald-800 ring-1 ring-emerald-200">
                        <span id="kds-realtime-dot" class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span id="kds-realtime-label">Realtime: đang kết nối</span>
                    </span>
                    <span id="kds-clock" class="ml-1 font-mono text-lg font-bold tabular-nums text-slate-900">{{ now()->format('H:i') }}</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-2 sm:px-6">
                <nav class="flex flex-wrap gap-2" aria-label="Lọc theo quầy">
                    <a href="{{ route('kitchen.index') }}"
                       class="rounded-full px-4 py-1.5 text-sm font-semibold {{ $stationCode === null ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        Tất cả quầy
                    </a>
                    @foreach ($stationCodes as $code)
                        <a href="{{ route('kitchen.index', ['station' => $code->value]) }}"
                           class="rounded-full px-4 py-1.5 text-sm font-semibold {{ $stationCode === $code ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                            {{ $stationLabels[$code->value] ?? $code->value }}
                        </a>
                    @endforeach
                </nav>

                <div class="flex items-center gap-3 text-sm">
                    <span class="hidden text-slate-500 sm:inline">{{ auth()->user()->full_name }}</span>
                    @if (auth()->user()->hasPermission('order.serve'))
                        <x-button :href="route('kitchen.serve.index')" variant="ghost" size="sm">Trang ra món</x-button>
                    @endif
                    @if (auth()->user()->hasPermission('dashboard.view'))
                        <x-button :href="route('dashboard')" variant="ghost" size="sm">Quản trị</x-button>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-button type="submit" variant="outline" size="sm">Đăng xuất</x-button>
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
                    const label = timer.dataset.label ?? (timer.dataset.timer === 'cooking' ? 'Đang nấu' : 'Chờ');

                    timer.textContent = isOverdue
                        ? `Quá ${board.dataset.overdueMinutes} phút · ${formatDuration(now - firedAt)}`
                        : `${label} ${formatDuration(now - since)}`;
                    timer.classList.toggle('kds-chip-overdue', isOverdue);
                    card.classList.toggle('kds-card-overdue', isOverdue);
                });

                clock.textContent = new Date(now).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
            }

            /** Cập nhật số bát phở chưa nấu / đã nấu trên thanh trên cùng. */
            function updatePhoStats() {
                const stats = document.getElementById('kds-pho-stats');

                if (stats) {
                    document.getElementById('kds-pending-count').textContent = stats.dataset.pending;
                    document.getElementById('kds-done-count').textContent = stats.dataset.done;
                }
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
                    updatePhoStats();
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
