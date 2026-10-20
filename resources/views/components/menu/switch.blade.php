@props(['name', 'checked' => false, 'label' => null, 'autosubmit' => false, 'disabled' => false])

<label {{ $attributes->class(['inline-flex items-center gap-2.5', 'cursor-pointer' => ! $disabled, 'cursor-not-allowed opacity-60' => $disabled]) }}>
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" value="1" class="peer sr-only"
        @checked($checked) @disabled($disabled)
        @if ($autosubmit) onchange="this.form.requestSubmit()" @endif>
    <span class="relative h-6 w-11 shrink-0 rounded-full bg-slate-300 transition-colors after:absolute after:left-0.5 after:top-0.5 after:size-5 after:rounded-full after:bg-white after:shadow-sm after:transition-transform peer-checked:bg-emerald-500 peer-checked:after:translate-x-5 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-500 peer-focus-visible:ring-offset-2"></span>
    @if ($label)
        <span class="text-sm text-slate-700">{{ $label }}</span>
    @endif
    {{ $slot }}
</label>
