@props([
    'align' => 'left',
])

@php
    $alignClasses = match($align) {
        'center' => 'text-center',
        'right' => 'text-right',
        default => 'text-left',
    };
@endphp

<th scope="col" {{ $attributes->merge(['class' => "px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-600 {$alignClasses}"]) }}>
    {{ $slot }}
</th>
