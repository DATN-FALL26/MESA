@props(['id', 'title', 'size' => 'md'])
@php
    $max = match ($size) {
        'sm' => 'max-w-sm',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-2xl',
        default => 'max-w-md',
    };
@endphp
<div id="{{ $id }}" data-menu-modal role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 hidden">
    <div class="bg-white rounded-xl shadow-lg border border-slate-200 w-full {{ $max }} max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h3 id="{{ $id }}-title" data-modal-title class="text-sm font-bold text-slate-900">{{ $title }}</h3>
            <button type="button" data-modal-close class="text-slate-400 hover:text-slate-600" aria-label="Đóng">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="p-6">{{ $slot }}</div>
    </div>
</div>
