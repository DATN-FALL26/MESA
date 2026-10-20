@props(['title', 'description' => null])

<div class="mb-5 flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
        <h2 class="text-xl font-semibold tracking-tight text-slate-900">{{ $title }}</h2>
        @if ($description)
            <p class="mt-1 max-w-2xl text-sm text-slate-500">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
