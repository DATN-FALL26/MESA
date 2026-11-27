@props([
    'padding' => true,
])

@php
    $paddingClasses = $padding ? 'p-6' : '';
@endphp

<div {{ $attributes->merge(['class' => "bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden {$paddingClasses}"]) }}>
    {{ $slot }}
</div>
