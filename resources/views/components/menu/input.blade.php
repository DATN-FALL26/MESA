@props(['name', 'type' => 'text', 'value' => null, 'suffix' => null, 'old' => true])
@php
    $current = $type === 'file' ? null : ($old ? old($name, $value) : $value);
    $hasError = $old && $errors->has($name);
    $base = 'block w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 shadow-xs placeholder:text-slate-400 focus:outline-hidden focus:ring-2 disabled:bg-slate-50 disabled:text-slate-400';
    $tone = $hasError
        ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'
        : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-200';
@endphp
<div class="relative">
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $attributes->get('id', $name) }}"
        @if ($type !== 'file' && $current !== null) value="{{ $current }}" @endif
        {{ $attributes->except('id')->class([$base, $tone, 'pr-12' => $suffix !== null]) }}>
    @if ($suffix !== null)
        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-slate-500">{{ $suffix }}</span>
    @endif
</div>
