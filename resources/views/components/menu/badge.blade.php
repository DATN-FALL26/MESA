@props(['tone' => 'neutral', 'dot' => false])
@php
    $map = [
        'success' => ['bg-emerald-50 text-emerald-700 ring-emerald-600/20', 'bg-emerald-500'],
        'danger' => ['bg-rose-50 text-rose-700 ring-rose-600/20', 'bg-rose-500'],
        'warning' => ['bg-amber-50 text-amber-800 ring-amber-600/20', 'bg-amber-500'],
        'info' => ['bg-indigo-50 text-indigo-700 ring-indigo-600/20', 'bg-indigo-500'],
        'neutral' => ['bg-slate-100 text-slate-600 ring-slate-500/20', 'bg-slate-400'],
    ];
    [$classes, $dotClass] = $map[$tone] ?? $map['neutral'];
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset', $classes]) }}>
    @if ($dot)
        <span class="size-1.5 rounded-full {{ $dotClass }}"></span>
    @endif
    {{ $slot }}
</span>
