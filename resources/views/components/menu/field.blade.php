@props(['label', 'name', 'hint' => null, 'required' => false])

<div {{ $attributes->class(['min-w-0']) }}>
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-slate-800">
        {{ $label }}@if ($required)<span class="text-rose-600"> *</span>@endif
    </label>
    {{ $slot }}
    @error($name)
        <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
    @else
        @if ($hint)
            <p class="mt-1.5 text-xs text-slate-500">{{ $hint }}</p>
        @endif
    @enderror
</div>
