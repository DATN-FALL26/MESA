@props(['title', 'description' => null, 'id' => null])

<section @if ($id) id="{{ $id }}" @endif {{ $attributes->class(['scroll-mt-6 rounded-xl border border-slate-200 bg-white']) }}>
    <header class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
        <div class="min-w-0">
            <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
            @if ($description)
                <p class="mt-0.5 text-xs text-slate-500">{{ $description }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="shrink-0">{{ $actions }}</div>
        @endisset
    </header>
    <div class="p-5">{{ $slot }}</div>
</section>
