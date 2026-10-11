@props([
    'value' => null,
    'required' => false,
])

<label {{ $attributes->merge(['class' => 'block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5']) }}>
    {{ $value ?? $slot }}
    @if($required)
        <span class="text-rose-500 font-bold ml-0.5">*</span>
    @endif
</label>
