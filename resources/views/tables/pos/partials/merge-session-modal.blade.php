<div data-modal="merge-session" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-slate-950/40 p-4 backdrop-blur-sm" role="dialog" aria-modal="true">
    <div class="w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-700 bg-slate-950 shadow-2xl">
        <div class="flex items-start justify-between border-b border-slate-700 px-5 py-4">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-400">Gộp & ghép bàn</p>
                <h2 data-merge-title class="mt-1 text-base font-semibold text-white">Gộp bàn vào phiên</h2>
            </div>
            <button type="button" data-modal-close class="rounded-lg p-2 text-slate-300 hover:bg-slate-800" aria-label="Đóng">×</button>
        </div>
        <form data-merge-session-form class="bg-slate-50">
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 bg-slate-100 px-5 py-3">
                <span data-merge-source-badge class="rounded-lg border border-emerald-300 bg-emerald-100 px-2.5 py-1.5 text-xs font-bold text-emerald-900"></span>
                <span class="text-slate-400">+</span>
                <span data-merge-selected-count class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700">Chưa chọn bàn</span>
                <span data-merge-capacity class="ml-auto rounded-lg border border-sky-200 bg-sky-50 px-2.5 py-1.5 text-xs font-semibold text-sky-800"></span>
            </div>
            <div class="px-5 py-4">
                <div data-merge-target class="grid max-h-[48vh] grid-cols-2 gap-2 overflow-y-auto pr-1 sm:grid-cols-3 lg:grid-cols-4"></div>
            </div>
            <p data-merge-no-target class="hidden mx-5 mb-4 rounded-xl border border-amber-300 bg-amber-50 px-3.5 py-3 text-sm text-amber-900">Không có bàn trống để gộp.</p>
            <p data-merge-error class="hidden mx-5 mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-3 text-sm text-rose-700"></p>
            <div class="flex justify-end gap-2 border-t border-slate-200 bg-white px-5 py-4">
                <button type="button" data-modal-close class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Hủy</button>
                <button type="submit" data-merge-submit disabled class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white disabled:bg-slate-300">Lưu gộp bàn</button>
            </div>
        </form>
    </div>
</div>
