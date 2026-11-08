@props(['name', 'type' => 'text', 'value' => null, 'modal' => null, 'suffix' => null])
@php
    $own = old('_modal') === $modal;
    $current = $type === 'file' ? null : ($own ? old($name, $value) : $value);
    $hasError = $own && $errors->has($name);
    $classes = 'w-full px-3 py-2 rounded-lg border text-xs focus:outline-none disabled:bg-slate-50 disabled:text-slate-400 '
        .($hasError ? 'border-rose-400 focus:border-rose-500' : 'border-slate-300 focus:border-emerald-600')
        .($suffix !== null ? ' pr-10' : '');
@endphp
<div class="relative">
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $attributes->get('id', ($modal ? $modal.'-' : '').$name) }}"
        @if ($current !== null && $type !== 'file') value="{{ $current }}" @endif
        {{ $attributes->except('id')->merge(['class' => $classes]) }}>
    @if ($suffix !== null)
        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-[10px] font-medium text-slate-400">{{ $suffix }}</span>
    @endif
</div>
