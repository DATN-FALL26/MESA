@props([
    'type' => 'info',
    'message' => null,
    'dismissible' => false,
])

@php
    $typeClasses = match($type) {
        'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
        'error', 'danger' => 'bg-rose-50 text-rose-800 border-rose-200',
        'warning' => 'bg-amber-50 text-amber-800 border-amber-200',
        'info' => 'bg-sky-50 text-sky-800 border-sky-200',
        default => 'bg-slate-50 text-slate-800 border-slate-200',
    };

    $iconClasses = match($type) {
        'success' => 'text-emerald-500',
        'error', 'danger' => 'text-rose-500',
        'warning' => 'text-amber-500',
        'info' => 'text-sky-500',
        default => 'text-slate-500',
    };
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    {{ $attributes->merge(['class' => "rounded-lg border p-4 flex items-start gap-3 {$typeClasses} transition-all duration-200"]) }}
>
    <!-- Icon -->
    <div class="shrink-0 mt-0.5 {{ $iconClasses }}">
        @if($type === 'success')
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        @elseif($type === 'error' || $type === 'danger')
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        @elseif($type === 'warning')
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
        @else
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
            </svg>
        @endif
    </div>

    <!-- Message -->
    <div class="flex-1 text-sm font-medium">
        {{ $message ?? $slot }}
    </div>

    <!-- Close button -->
    @if($dismissible)
        <button
            type="button"
            onclick="this.closest('.flex').remove()"
            class="shrink-0 text-slate-400 hover:text-slate-600 rounded-md p-1 -m-1 transition-colors cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</div>
