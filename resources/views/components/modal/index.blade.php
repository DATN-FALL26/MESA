@props([
    'id',
    'title',
    'size' => 'md',
])

@php
    $maxWidth = match($size) {
        'sm' => 'sm:max-w-md',
        'md' => 'sm:max-w-lg',
        'lg' => 'sm:max-w-2xl',
        'xl' => 'sm:max-w-4xl',
        default => 'sm:max-w-lg',
    };
@endphp

<div
    id="{{ $id }}"
    class="fixed inset-0 z-50 hidden overflow-y-auto"
    aria-labelledby="{{ $id }}-title"
    role="dialog"
    aria-modal="true"
>
    <!-- Background Backdrop -->
    <div
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs transition-opacity"
        onclick="document.getElementById('{{ $id }}').classList.add('hidden')"
    ></div>

    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full {{ $maxWidth }} border border-slate-200">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-semibold text-slate-900" id="{{ $id }}-title">
                    {{ $title }}
                </h3>
                <button
                    type="button"
                    onclick="document.getElementById('{{ $id }}').classList.add('hidden')"
                    class="rounded-lg p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="px-6 py-5">
                {{ $slot }}
            </div>

            <!-- Footer / Actions -->
            @if(isset($actions))
                <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                    {{ $actions }}
                </div>
            @endif
        </div>
    </div>
</div>
