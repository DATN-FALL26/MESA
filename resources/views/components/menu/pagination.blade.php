@props(['paginator', 'label' => 'bản ghi'])

{{-- Chân bảng phân trang, cùng bố cục với trang Nguyên vật liệu (Hiển thị x / y + Trước / Sau). --}}
<div class="flex items-center justify-between px-4 py-3 bg-slate-50 border-t border-slate-200 text-xs text-slate-600">
    <div>
        @if ($paginator->total() > 0)
            Hiển thị {{ $paginator->firstItem() }}-{{ $paginator->lastItem() }} / {{ $paginator->total() }} {{ $label }}
        @else
            Không có {{ $label }} nào
        @endif
    </div>
    @if ($paginator->hasPages())
        <div class="flex items-center gap-2">
            @if ($paginator->onFirstPage())
                <span class="px-3 py-1.5 border border-slate-300 rounded-md bg-white opacity-40 cursor-not-allowed">Trước</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-1.5 border border-slate-300 rounded-md bg-white hover:bg-slate-100">Trước</a>
            @endif
            <span class="px-2 font-semibold text-slate-800">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-1.5 border border-slate-300 rounded-md bg-white hover:bg-slate-100">Sau</a>
            @else
                <span class="px-3 py-1.5 border border-slate-300 rounded-md bg-white opacity-40 cursor-not-allowed">Sau</span>
            @endif
        </div>
    @endif
</div>
