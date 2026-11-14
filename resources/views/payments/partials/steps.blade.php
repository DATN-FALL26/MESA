{{-- Thanh tiến trình thanh toán: 1. Yêu cầu → 2. Thanh toán (tiền mặt / QR) --}}
@php
    $steps = [1 => 'Yêu cầu', 2 => $secondLabel ?? 'Thanh toán'];
@endphp
<ol class="flex items-center gap-3 text-xs">
    @foreach ($steps as $number => $label)
        <li class="flex items-center gap-2 {{ $number === $current ? 'font-semibold text-slate-900' : 'text-slate-400' }}">
            <span class="flex h-5 w-5 items-center justify-center rounded-full text-[11px] font-bold {{ $number === $current ? 'bg-red-700 text-white' : 'border border-slate-300 text-slate-500' }}">{{ $number }}</span>
            {{ $label }}
        </li>
        @if (! $loop->last)
            <li class="h-px w-12 bg-slate-300" aria-hidden="true"></li>
        @endif
    @endforeach
</ol>
