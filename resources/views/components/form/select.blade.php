@props([
    'name',
    'id' => null,
    'disabled' => false,
    'required' => false,
])

@php
    $selectId = $id ?? $name;
    $hasError = $errors->has($name);

    $borderClasses = $hasError
        ? 'border-rose-300 text-rose-900 focus:border-rose-500 focus:ring-rose-500/20'
        : 'border-slate-300 text-slate-800 focus:border-emerald-500 focus:ring-emerald-500/20';
@endphp

<div class="relative rounded-lg shadow-2xs">
    <select
        name="{{ $name }}"
        id="{{ $selectId }}"
        {{ $disabled ? 'disabled' : '' }}
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge([
            'class' => "block w-full rounded-lg border text-sm py-2 px-3 bg-white transition-all focus:outline-hidden focus:ring-3 disabled:bg-slate-50 disabled:text-slate-500 disabled:border-slate-200 {$borderClasses}"
        ]) }}
    >
        {{ $slot }}
    </select>
</div>

@error($name)
    <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
@enderror
