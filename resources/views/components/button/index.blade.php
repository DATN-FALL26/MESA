@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'disabled' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-all focus:outline-hidden focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed select-none cursor-pointer';

    $sizeClasses = match($size) {
        'xs' => 'px-2 py-1 text-xs gap-1',
        'sm' => 'px-2.5 py-1.5 text-xs gap-1.5',
        'md' => 'px-3.5 py-2 text-sm gap-2',
        'lg' => 'px-5 py-2.5 text-base gap-2.5',
        default => 'px-3.5 py-2 text-sm gap-2',
    };

    $variantClasses = match($variant) {
        'primary' => 'bg-emerald-600 text-white hover:bg-emerald-700 active:bg-emerald-800 focus:ring-emerald-500 shadow-2xs',
        'secondary' => 'bg-slate-800 text-white hover:bg-slate-900 active:bg-slate-950 focus:ring-slate-700 shadow-2xs',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-700 active:bg-rose-800 focus:ring-rose-500 shadow-2xs',
        'warning' => 'bg-amber-500 text-white hover:bg-amber-600 active:bg-amber-700 focus:ring-amber-500 shadow-2xs',
        'outline' => 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 hover:text-slate-900 focus:ring-emerald-500 shadow-2xs',
        'ghost' => 'bg-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:ring-slate-400',
        default => 'bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-500',
    };

    $classes = "{$baseClasses} {$sizeClasses} {$variantClasses}";
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
