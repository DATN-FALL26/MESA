@extends('layouts.auth')

@section('title', 'Phục vụ | MESA')
@section('heading', 'Phục vụ · Món chờ mang ra')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600">Bếp bấm Hoàn thành thì món tự hiện ở đây. Mang món ra bàn rồi bấm <strong>Đã phục vụ</strong>.</p>

        <div class="flex items-center gap-2">
            <x-badge id="serve-realtime" color="success" dot>Realtime: đang kết nối</x-badge>
            @if (auth()->user()->hasPermission('kitchen.view'))
                <x-button :href="route('kitchen.index')" variant="outline" size="sm">Màn bếp (KDS)</x-button>
            @endif
        </div>
    </div>

    <div id="serve-board"
         data-board-url="{{ route('kitchen.serve.board') }}"
         data-overdue-minutes="{{ $overdueMinutes }}">
        @include('kitchen.serve.partials.board')
    </div>

    <script>
        (() => {
            const REFRESH_INTERVAL_MS = 5000;
            const BADGE_BASE = 'inline-flex items-center font-semibold rounded-full border px-2.5 py-1 text-xs gap-1.5';
            const BADGE_COLORS = {
                success: 'bg-emerald-50 text-emerald-700 border-emerald-200/80',
                warning: 'bg-amber-50 text-amber-700 border-amber-200/80',
                danger: 'bg-rose-50 text-rose-700 border-rose-200/80',
            };
            const board = document.getElementById('serve-board');
            const overdueMs = Number(board.dataset.overdueMinutes) * 60 * 1000;
            const realtime = document.getElementById('serve-realtime');
            let knownOrderIds = collectReadyOrderIds();
            let isRefreshing = false;

            function collectReadyOrderIds() {
                return new Set([...board.querySelectorAll('[data-ready-order-id]')].map((card) => card.dataset.readyOrderId));
            }

            function pad(value) {
                return String(value).padStart(2, '0');
            }

            function formatDuration(milliseconds) {
                const totalSeconds = Math.max(0, Math.floor(milliseconds / 1000));
                const hours = Math.floor(totalSeconds / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);

                return hours > 0
                    ? `${hours}:${pad(minutes)}:${pad(totalSeconds % 60)}`
                    : `${pad(minutes)}:${pad(totalSeconds % 60)}`;
            }

            /** Cập nhật "Xong x phút trước" và đồng hồ các bàn đang ở bếp. */
            function tickTimers() {
                const now = Date.now();

                board.querySelectorAll('[data-ready-at]').forEach((chip) => {
                    const minutes = Math.floor((now - Date.parse(chip.dataset.readyAt)) / 60000);
                    chip.textContent = minutes < 1 ? 'Vừa xong' : `Xong ${minutes} phút trước`;
                });

                board.querySelectorAll('[data-kitchen-fired-at][data-cooking="1"]').forEach((chip) => {
                    const elapsed = now - Date.parse(chip.dataset.kitchenFiredAt);
                    chip.textContent = `Đang nấu ${formatDuration(elapsed)}`;
                    chip.className = `${BADGE_BASE} font-mono tabular-nums ${elapsed >= overdueMs ? BADGE_COLORS.danger : BADGE_COLORS.warning}`;
                });
            }

            function setRealtimeStatus(isConnected) {
                realtime.className = `${BADGE_BASE} ${isConnected ? BADGE_COLORS.success : BADGE_COLORS.danger}`;
                realtime.innerHTML = `<span class="w-1.5 h-1.5 rounded-full ${isConnected ? 'bg-emerald-500' : 'bg-rose-500 animate-pulse'}"></span>`
                    + (isConnected ? 'Realtime: đang kết nối' : 'Realtime: mất kết nối');
            }

            /** Âm báo ngắn khi có bàn mới xong món (trình duyệt có thể chặn tới khi người dùng chạm vào trang). */
            function beep() {
                try {
                    const context = new (window.AudioContext || window.webkitAudioContext)();
                    const oscillator = context.createOscillator();
                    oscillator.frequency.value = 880;
                    oscillator.connect(context.destination);
                    oscillator.start();
                    oscillator.stop(context.currentTime + 0.2);
                } catch (error) {
                    // Không phát được âm thanh thì chỉ nhấp nháy thẻ.
                }
            }

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

                    let hasNewReadyOrder = false;
                    board.querySelectorAll('[data-ready-order-id]').forEach((card) => {
                        if (! knownOrderIds.has(card.dataset.readyOrderId)) {
                            card.classList.add('serve-card-new');
                            hasNewReadyOrder = true;
                        }
                    });

                    if (hasNewReadyOrder) {
                        beep();
                    }

                    knownOrderIds = collectReadyOrderIds();
                    setRealtimeStatus(true);
                    tickTimers();
                } catch (error) {
                    setRealtimeStatus(false);
                } finally {
                    isRefreshing = false;
                }
            }

            board.addEventListener('submit', async (event) => {
                const form = event.target.closest('form[data-serve-action]');

                if (! form) {
                    return;
                }

                event.preventDefault();
                form.querySelectorAll('button').forEach((button) => button.disabled = true);

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: new FormData(form),
                    });

                    if (! response.ok) {
                        const data = await response.json().catch(() => ({}));
                        alert(data.message ?? 'Không xác nhận được. Vui lòng thử lại.');
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
        .serve-card-new { animation: serve-flash 1.2s ease-in-out 3; }
        @keyframes serve-flash {
            0%, 100% { box-shadow: 0 0 0 0 rgb(16 185 129 / 0); }
            50% { box-shadow: 0 0 0 4px rgb(16 185 129); }
        }
    </style>
@endsection
