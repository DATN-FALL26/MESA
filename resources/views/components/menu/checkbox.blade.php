@props(['name', 'label', 'checked' => false, 'modal' => null, 'hint' => null])
@php
    $own = old('_modal') === $modal;
    $isChecked = $own ? (bool) old($name, $checked) : (bool) $checked;
@endphp
<div {{ $attributes->class(['flex items-start gap-2 pt-1']) }}>
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" id="{{ ($modal ? $modal.'-' : '').$name }}" value="1" @checked($isChecked)
        class="mt-0.5 rounded border-slate-300 text-emerald-600 accent-emerald-600 focus:ring-emerald-500">
    <div>
        <label for="{{ ($modal ? $modal.'-' : '').$name }}" class="text-slate-700 font-medium">{{ $label }}</label>
        @if ($hint)
            <p class="text-[10px] text-slate-400 mt-0.5">{{ $hint }}</p>
        @endif
    </div>
</div>
