@props(['variant' => 'primary', 'href' => null, 'type' => 'button'])
@php
    $classes = match ($variant) {
        'outline' => 'border border-slate-300 bg-white text-slate-600 hover:bg-slate-50',
        'danger' => 'bg-rose-600 hover:bg-rose-700 text-white shadow-2xs',
        default => 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs',
    };
    $classes = 'inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg transition-colors '.$classes;
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
