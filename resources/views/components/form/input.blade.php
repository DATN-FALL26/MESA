@props([
    'name',
    'id' => null,
    'type' => 'text',
    'value' => null,
    'prefix' => null,
    'suffix' => null,
    'disabled' => false,
    'required' => false,
])

@php
    $inputId = $id ?? $name;
    $hasError = $errors->has($name);
    $inputValue = old($name, $value);

    $borderClasses = $hasError
        ? 'border-rose-300 text-rose-900 placeholder-rose-300 focus:border-rose-500 focus:ring-rose-500/20'
        : 'border-slate-300 text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:ring-emerald-500/20';

    $paddingLeft = $prefix ? 'pl-8' : 'pl-3.5';
    $paddingRight = $suffix ? 'pr-10' : 'pr-3.5';
@endphp

<div class="relative rounded-lg shadow-2xs">
    @if($prefix)
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <span class="text-xs font-semibold text-slate-400 select-none">{{ $prefix }}</span>
        </div>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $inputId }}"
        value="{{ $inputValue }}"
        {{ $disabled ? 'disabled' : '' }}
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge([
            'class' => "block w-full rounded-lg border text-sm py-2 transition-all focus:outline-hidden focus:ring-3 disabled:bg-slate-50 disabled:text-slate-500 disabled:border-slate-200 {$borderClasses} {$paddingLeft} {$paddingRight}"
        ]) }}
    />

    @if($suffix)
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
            <span class="text-xs font-medium text-slate-400 select-none">{{ $suffix }}</span>
        </div>
    @endif
</div>

@error($name)
    <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
@enderror
