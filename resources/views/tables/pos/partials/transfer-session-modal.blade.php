<div data-modal="transfer-session" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-slate-950/40 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="transfer-session-title">
    <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl shadow-slate-900/20">
        <div class="flex items-start justify-between gap-4 border-b border-slate-700 bg-slate-900 px-6 py-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-300">Chuyển bàn</p>
                <h2 id="transfer-session-title" data-transfer-title class="mt-1 text-lg font-semibold text-white">Chuyển phiên</h2>
            </div>
            <button type="button" data-modal-close class="rounded-lg p-2 text-slate-300 transition hover:bg-slate-800 hover:text-white" aria-label="Đóng hộp thoại">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 6 12 12M18 6 6 18" /></svg>
            </button>
        </div>

        <form data-transfer-form class="space-y-5 px-6 py-5">
            <p class="text-sm leading-6 text-slate-600">Phiên và toàn bộ món đã gọi sẽ tiếp tục ở bàn đích. Bàn hiện tại sẽ được trả về trạng thái trống.</p>
            <label class="block text-sm font-medium text-slate-700">
                Chọn bàn trống muốn chuyển đến
                <select name="target_table_id" data-transfer-target required class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10">
                    <option value="">Chọn bàn đích</option>
                </select>
            </label>
            <p data-transfer-no-target class="hidden rounded-xl border border-amber-300 bg-amber-50 px-3.5 py-3 text-sm text-amber-900">Hiện không có bàn trống để chuyển phiên.</p>
            <p data-transfer-error class="hidden rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-3 text-sm text-rose-700" role="alert"></p>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" data-modal-close class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Hủy</button>
                <button type="submit" data-transfer-submit class="rounded-xl bg-emerald-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-900 disabled:cursor-wait disabled:opacity-60">Xác nhận chuyển bàn</button>
            </div>
        </form>
    </div>
</div>
