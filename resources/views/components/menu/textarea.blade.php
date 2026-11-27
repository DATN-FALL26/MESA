@props(['name', 'value' => null, 'modal' => null, 'rows' => 2])
@php
    $own = old('_modal') === $modal;
    $hasError = $own && $errors->has($name);
    $classes = 'w-full px-3 py-2 rounded-lg border text-xs focus:outline-none '
        .($hasError ? 'border-rose-400 focus:border-rose-500' : 'border-slate-300 focus:border-emerald-600');
@endphp
<textarea name="{{ $name }}" id="{{ $attributes->get('id', ($modal ? $modal.'-' : '').$name) }}" rows="{{ $rows }}" {{ $attributes->except('id')->merge(['class' => $classes]) }}>{{ $own ? old($name, $value) : $value }}</textarea>
