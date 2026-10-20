@props(['name', 'old' => true])
@php
    $hasError = $old && $errors->has($name);
    $tone = $hasError
        ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'
        : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-200';
@endphp
<select name="{{ $name }}" id="{{ $attributes->get('id', $name) }}"
    {{ $attributes->except('id')->class(['block w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 shadow-xs focus:outline-hidden focus:ring-2', $tone]) }}>
    {{ $slot }}
</select>
