@props(['title', 'description' => null])

{{-- Thẻ tiêu đề trang: cùng khuôn với trang Nguyên vật liệu (module Kho). --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200">
    <div class="min-w-0">
        <h1 class="text-xl font-bold text-slate-900 tracking-tight">{{ $title }}</h1>
        @if ($description)
            <p class="text-xs text-slate-500 mt-1">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-3">{{ $actions }}</div>
    @endisset
</div>
