@props([
    'color' => 'neutral',
    'size' => 'md',
    'dot' => false,
])

@php
    $baseClasses = 'inline-flex items-center font-semibold rounded-full border';

    $sizeClasses = match($size) {
        'sm' => 'px-2 py-0.5 text-[11px] gap-1',
        'md' => 'px-2.5 py-1 text-xs gap-1.5',
        default => 'px-2.5 py-1 text-xs gap-1.5',
    };

    $colorClasses = match($color) {
        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200/80',
        'danger' => 'bg-rose-50 text-rose-700 border-rose-200/80',
        'warning' => 'bg-amber-50 text-amber-700 border-amber-200/80',
        'info' => 'bg-sky-50 text-sky-700 border-sky-200/80',
        'purple' => 'bg-purple-50 text-purple-700 border-purple-200/80',
        'neutral' => 'bg-slate-100 text-slate-700 border-slate-200/80',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
    };

    $dotColorClasses = match($color) {
        'success' => 'bg-emerald-500',
        'danger' => 'bg-rose-500',
        'warning' => 'bg-amber-500',
        'info' => 'bg-sky-500',
        'purple' => 'bg-purple-500',
        default => 'bg-slate-400',
    };

    $classes = "{$baseClasses} {$sizeClasses} {$colorClasses}";
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $dotColorClasses }}"></span>
    @endif
    {{ $slot }}
</span>
