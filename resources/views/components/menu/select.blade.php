@props(['name', 'modal' => null])
@php
    $hasError = old('_modal') === $modal && $errors->has($name);
    $classes = 'w-full px-3 py-2 rounded-lg border text-xs bg-white focus:outline-none '
        .($hasError ? 'border-rose-400 focus:border-rose-500' : 'border-slate-300 focus:border-emerald-600');
@endphp
<select name="{{ $name }}" id="{{ $attributes->get('id', ($modal ? $modal.'-' : '').$name) }}" {{ $attributes->except('id')->merge(['class' => $classes]) }}>
    {{ $slot }}
</select>
