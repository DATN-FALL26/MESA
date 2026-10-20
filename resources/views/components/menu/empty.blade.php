@props(['title', 'text' => null, 'icon' => 'squares'])

<div class="flex flex-col items-center px-6 py-14 text-center">
    <div class="flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
        <x-menu.icon :name="$icon" class="size-6" />
    </div>
    <p class="mt-4 text-sm font-semibold text-slate-900">{{ $title }}</p>
    @if ($text)
        <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $text }}</p>
    @endif
    @if (trim((string) $slot) !== '')
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
