@extends('layouts.auth')

@section('title', 'Thanh toán QR '.$place.' | MESA')
@section('heading', 'Thanh toán · '.$place.($sessionCode ? ' · Lượt dùng bữa '.$sessionCode : ''))

@php
    $money = fn (int $amount): string => number_format($amount, 0, ',', '.').'đ';
    $isPending = $payment->status === \App\Enums\PaymentStatus::PENDING;
    $transferNote = $payment->gateway_payload['transfer_note'] ?? '';
    $expiresAt = $payment->gateway_payload['expires_at'] ?? null;

    // Mã QR mô phỏng: lưới 25×25 sinh từ nội dung chuyển khoản, có 3 ô định vị ở góc như QR thật.
    $size = 25;
    $seed = crc32($transferNote.'|'.$payment->id);
    $isFinder = fn (int $row, int $col): bool => ($row < 7 && $col < 7) || ($row < 7 && $col >= $size - 7) || ($row >= $size - 7 && $col < 7);
    $finderCell = function (int $row, int $col) use ($size): bool {
        $r = $row >= $size - 7 ? $row - ($size - 7) : $row;
        $c = $col >= $size - 7 ? $col - ($size - 7) : $col;

        return $r === 0 || $r === 6 || $c === 0 || $c === 6 || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4);
    };
@endphp

@section('content')
    <div class="space-y-4">
        @include('payments.partials.steps', ['current' => 2, 'secondLabel' => 'Thanh toán QR'])

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_360px]">
            <x-card class="flex flex-col items-center justify-center py-10 text-center">
                <p class="text-sm font-semibold text-slate-800">Khách quét mã để chuyển khoản</p>
                @if ($bankQr)
                    {{-- Mã VietQR thật: app ngân hàng tự điền tài khoản, số tiền và nội dung --}}
                    <div class="mt-4 w-72 rounded-2xl bg-gradient-to-b from-sky-100 via-sky-50 to-white p-4 shadow-md ring-1 ring-sky-100 {{ $isPending ? '' : 'opacity-25' }}">
                        <div class="flex items-center gap-3 text-left">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-700 text-[11px] font-extrabold text-white" title="{{ $bank['name'] }}">
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::limit(\Illuminate\Support\Str::before($bank['name'].' ', ' '), 3, '')) }}
                            </span>
                            <div class="min-w-0 leading-tight">
                                <p class="truncate text-sm font-semibold uppercase text-blue-900">{{ $bank['account_name'] }}</p>
                                <p class="text-sm tabular-nums text-blue-900">{{ $bank['account_no'] }}</p>
                            </div>
                        </div>
                        <div id="vietqr" data-payload="{{ $bankQr }}" class="mx-auto mt-3 rounded-lg bg-white p-1" style="width: 230px; height: 230px;" role="img" aria-label="Mã VietQR"></div>
                        <div class="mt-3 flex items-center justify-between px-1">
                            <span class="text-base font-extrabold tracking-tight"><span class="text-red-600">Viet</span><span class="text-blue-800">QR</span></span>
                            <span class="text-base font-bold italic text-blue-800">napas <span class="text-red-600">247</span></span>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">{{ $bank['name'] }}</p>
                @else
                <svg viewBox="0 0 {{ $size }} {{ $size }}" class="mt-4 h-56 w-56 {{ $isPending ? '' : 'opacity-25' }}" shape-rendering="crispEdges" role="img" aria-label="Mã QR mô phỏng">
                    <rect width="{{ $size }}" height="{{ $size }}" fill="#fff" />
                    @for ($row = 0; $row < $size; $row++)
                        @for ($col = 0; $col < $size; $col++)
                            @php
                                $filled = $isFinder($row, $col)
                                    ? $finderCell($row, $col)
                                    : ((($seed >> (($row * 7 + $col * 13) % 31)) ^ ($row * $col + $row + $col)) & 1) === 1;
                            @endphp
                            @if ($filled)
                                <rect x="{{ $col }}" y="{{ $row }}" width="1" height="1" fill="#0f172a" />
                            @endif
                        @endfor
                    @endfor
                </svg>
                <p class="mt-3 text-xs text-slate-500">Mã mô phỏng, chưa phải mã ngân hàng thật</p>
                @endif
            </x-card>

            <div class="flex flex-col gap-3">
                <x-card class="flex flex-1 flex-col justify-center">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-xs text-slate-500">Số tiền</p>
                        <x-badge size="sm" :color="match ($statusLabel) { 'Thành công' => 'success', 'Đang chờ' => 'warning', default => 'danger' }">
                            {{ $statusLabel === 'Đang chờ' ? 'Đang chờ thanh toán' : $statusLabel }}
                        </x-badge>
                    </div>
                    <p class="mt-1 text-3xl font-bold tabular-nums text-red-700">{{ $money((int) $payment->amount) }}</p>
                    <dl class="mt-3 space-y-1.5 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Nội dung chuyển khoản</dt>
                            <dd class="font-semibold text-slate-900">{{ $transferNote }}</dd>
                        </div>
                        @if ($isPending && $expiresAt)
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">Hết hạn sau</dt>
                                <dd id="qr-countdown" data-expires-at="{{ $expiresAt }}" class="font-mono font-semibold tabular-nums text-slate-900">--:--</dd>
                            </div>
                        @endif
                    </dl>
                </x-card>

                @if ($isPending)
                    @unless ($bankQr)
                        <p class="rounded-xl bg-red-50 px-4 py-3 text-xs text-red-800 ring-1 ring-red-200">
                            Chế độ mô phỏng: khi khách đã chuyển khoản, nhân viên kiểm tra điện thoại rồi bấm "Đã nhận tiền".
                        </p>
                    @endunless

                    <form method="POST" action="{{ route('payments.qr.confirm', $payment) }}">
                        @csrf
                        <button type="submit" id="qr-confirm" class="w-full rounded-xl bg-red-800 px-4 py-3 text-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50">Đã nhận tiền</button>
                    </form>
                    <form method="POST" action="{{ route('payments.qr.fail', $payment) }}">
                        @csrf
                        <button type="submit" class="w-full rounded-xl border border-red-300 bg-white px-4 py-2.5 text-sm font-semibold text-red-800 transition hover:bg-red-50">Giao dịch thất bại / hết hạn</button>
                    </form>
                @else
                    <x-alert :type="$statusLabel === 'Thành công' ? 'success' : 'error'">
                        @if ($statusLabel === 'Hết hạn')
                            Mã QR đã hết hạn. Tạo mã mới hoặc đổi sang tiền mặt.
                        @elseif ($statusLabel === 'Thành công')
                            Giao dịch đã thanh toán thành công.
                        @else
                            Giao dịch thất bại. Thử lại bằng mã mới hoặc đổi phương thức.
                        @endif
                    </x-alert>

                    @if ($statusLabel !== 'Thành công' && $summary['remaining'] > 0)
                        <form method="POST" action="{{ route('payments.start', $order) }}">
                            @csrf
                            <input type="hidden" name="method" value="qr">
                            <button type="submit" class="w-full rounded-xl bg-red-800 px-4 py-3 text-sm font-semibold text-white transition hover:bg-red-700">Tạo mã QR mới</button>
                        </form>
                    @endif
                @endif

                <x-button :href="route('payments.show', $order)" variant="outline" class="w-full">Đổi phương thức</x-button>
            </div>
        </div>
    </div>

    @if ($bankQr)
        <script src="{{ asset('js/vendor/qrcode-generator.min.js') }}"></script>
        <script>
            (() => {
                const box = document.getElementById('vietqr');
                const qr = qrcode(0, 'M');
                qr.addData(box.dataset.payload);
                qr.make();
                box.innerHTML = qr.createSvgTag({ cellSize: 6, margin: 2, scalable: true });
            })();
        </script>
    @endif

    @if ($isPending && $expiresAt)
        <script>
            (() => {
                const countdown = document.getElementById('qr-countdown');
                const confirmButton = document.getElementById('qr-confirm');
                const expiresAt = Date.parse(countdown.dataset.expiresAt);
                const pad = (value) => String(value).padStart(2, '0');

                const tick = () => {
                    const left = Math.max(0, Math.floor((expiresAt - Date.now()) / 1000));
                    countdown.textContent = `${pad(Math.floor(left / 60))}:${pad(left % 60)}`;

                    if (left === 0) {
                        confirmButton.disabled = true;
                        clearInterval(timer);
                        // Tải lại để máy chủ ghi nhận "Hết hạn".
                        setTimeout(() => window.location.reload(), 800);
                    }
                };

                const timer = setInterval(tick, 1000);
                tick();
            })();
        </script>
    @endif
@endsection
