@props([
    'name',
    'id' => null,
    'rows' => 3,
    'value' => null,
    'disabled' => false,
    'required' => false,
])

@php
    $textareaId = $id ?? $name;
    $hasError = $errors->has($name);
    $textareaValue = old($name, $value);

    $borderClasses = $hasError
        ? 'border-rose-300 text-rose-900 placeholder-rose-300 focus:border-rose-500 focus:ring-rose-500/20'
        : 'border-slate-300 text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:ring-emerald-500/20';
@endphp

<div class="relative rounded-lg shadow-2xs">
    <textarea
        name="{{ $name }}"
        id="{{ $textareaId }}"
        rows="{{ $rows }}"
        {{ $disabled ? 'disabled' : '' }}
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge([
            'class' => "block w-full rounded-lg border text-sm p-3 transition-all focus:outline-hidden focus:ring-3 disabled:bg-slate-50 disabled:text-slate-500 disabled:border-slate-200 {$borderClasses}"
        ]) }}
    >{{ $textareaValue }}</textarea>
</div>

@error($name)
    <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
@enderror
