@extends('kitchen.layout')

@section('title', 'Phục vụ | MESA')

@section('content')
    <div class="flex min-h-screen flex-col">
        {{-- Thanh trên cùng --}}
        <header class="sticky top-0 z-20 border-b border-stone-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-800 text-sm font-black text-white">IP</span>
                    <p class="text-base font-bold text-stone-900">I-Pho · Phục vụ</p>
                </div>

                <div class="flex items-center gap-3 text-sm">
                    <span id="serve-realtime" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-800 ring-1 ring-emerald-200">
                        <span id="serve-realtime-dot" class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span id="serve-realtime-label">Realtime: đang kết nối</span>
                    </span>
                    <span class="font-medium text-red-800">{{ auth()->user()->full_name }}</span>
                    @if (auth()->user()->hasPermission('kitchen.view'))
                        <a href="{{ route('kitchen.index') }}" class="hidden font-medium text-stone-600 hover:text-stone-900 hover:underline sm:inline">Màn bếp</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-stone-300 px-3 py-1 font-medium text-stone-700 hover:bg-stone-100">Đăng xuất</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6">
            <div id="serve-board"
                 data-board-url="{{ route('kitchen.serve.board') }}"
                 data-overdue-minutes="{{ $overdueMinutes }}">
                @include('kitchen.serve.partials.board')
            </div>
        </main>
    </div>

    <script>
        (() => {
            const REFRESH_INTERVAL_MS = 5000;
            const board = document.getElementById('serve-board');
            const overdueMs = Number(board.dataset.overdueMinutes) * 60 * 1000;
            const realtime = document.getElementById('serve-realtime');
            const realtimeDot = document.getElementById('serve-realtime-dot');
            const realtimeLabel = document.getElementById('serve-realtime-label');
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

                board.querySelectorAll('[data-kitchen-fired-at]').forEach((chip) => {
                    const firedAt = Date.parse(chip.dataset.kitchenFiredAt);

                    if (chip.dataset.cooking !== '1') {
                        return;
                    }

                    const isOverdue = now - firedAt >= overdueMs;
                    chip.textContent = `Đang nấu ${formatDuration(now - firedAt)}`;
                    chip.className = isOverdue
                        ? 'rounded-full bg-red-100 px-2.5 py-0.5 font-mono text-xs font-semibold tabular-nums text-red-800'
                        : 'rounded-full bg-amber-100 px-2.5 py-0.5 font-mono text-xs font-semibold tabular-nums text-amber-800';
                });
            }

            function setRealtimeStatus(isConnected) {
                realtime.className = isConnected
                    ? 'inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-800 ring-1 ring-emerald-200'
                    : 'inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-medium text-rose-800 ring-1 ring-rose-200';
                realtimeDot.className = isConnected ? 'h-2 w-2 rounded-full bg-emerald-500' : 'h-2 w-2 rounded-full bg-rose-500 animate-pulse';
                realtimeLabel.textContent = isConnected ? 'Realtime: đang kết nối' : 'Realtime: mất kết nối';
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
