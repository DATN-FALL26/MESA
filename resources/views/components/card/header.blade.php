@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3']) }}>
    <div>
        <h3 class="text-base font-semibold text-slate-900 leading-snug">{{ $title }}</h3>
        @if($subtitle)
            <p class="text-xs text-slate-500 mt-0.5">{{ $subtitle }}</p>
        @endif
    </div>

    @if(isset($actions))
        <div class="flex items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</div>
