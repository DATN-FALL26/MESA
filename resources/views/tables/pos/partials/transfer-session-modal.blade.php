<div data-modal="transfer-session" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-slate-950/40 p-4 backdrop-blur-sm" role="dialog" aria-modal="true">
    <div class="w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-700 bg-slate-950 shadow-2xl">
        <div class="flex items-start justify-between border-b border-slate-700 px-5 py-4">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-400">Chuyển bàn</p>
                <h2 data-transfer-title class="mt-1 text-base font-semibold text-white">Chuyển phiên sang bàn khác</h2>
            </div>
            <button type="button" data-modal-close class="rounded-lg p-2 text-slate-300 hover:bg-slate-800" aria-label="Đóng">×</button>
        </div>
        <form data-transfer-form class="bg-slate-50">
            <div class="grid grid-cols-[minmax(0,1fr)_36px_minmax(0,1fr)] items-center gap-2 border-b border-slate-200 px-5 py-4">
                <div class="rounded-xl border border-emerald-300 bg-emerald-50 p-3">
                    <p class="text-[10px] font-bold uppercase text-slate-500">Bàn hiện tại</p>
                    <p data-transfer-source-code class="mt-1 text-lg font-bold text-slate-950"></p>
                    <p data-transfer-source-info class="mt-1 text-xs text-slate-500"></p>
                </div>
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-orange-500 ring-1 ring-slate-200">→</div>
                <div data-transfer-target-card class="rounded-xl border border-dashed border-slate-300 bg-slate-100 p-3">
                    <p class="text-[10px] font-bold uppercase text-slate-500">Bàn đích</p>
                    <p data-transfer-target-placeholder class="mt-1 text-sm font-semibold text-slate-600">Chưa chọn</p>
                </div>
            </div>
            <div class="px-5 py-4">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-800">Chọn bàn trống</p>
                <div data-transfer-target class="mt-2 grid max-h-[46vh] grid-cols-2 gap-2 overflow-y-auto pr-1 sm:grid-cols-3 lg:grid-cols-4"></div>
                <select name="target_table_id" data-transfer-target-select class="hidden" required><option value="">Chọn bàn đích</option></select>
                <p data-transfer-no-target class="hidden rounded-xl border border-amber-300 bg-amber-50 px-3.5 py-3 text-sm text-amber-900">Không có bàn trống để chuyển.</p>
                <p data-transfer-error class="hidden mt-3 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-3 text-sm text-rose-700"></p>
            </div>
            <div class="flex justify-end gap-2 border-t border-slate-200 bg-white px-5 py-4">
                <button type="button" data-modal-close class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Hủy</button>
                <button type="submit" data-transfer-submit disabled class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white disabled:bg-slate-300">Xác nhận chuyển phiên</button>
            </div>
        </form>
    </div>
</div>
