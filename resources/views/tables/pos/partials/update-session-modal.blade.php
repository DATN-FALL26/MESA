<div data-modal="update-session" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-slate-950/40 p-4 backdrop-blur-sm" role="dialog" aria-modal="true">
    <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="border-b border-slate-700 bg-slate-900 px-6 py-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-300">Phiên dùng bữa</p>
            <h2 data-update-title class="mt-1 text-lg font-semibold text-white">Cập nhật phiên</h2>
        </div>
        <form data-update-session-form class="space-y-4 px-6 py-5">
            <label class="block text-sm font-medium text-slate-700">Số khách
                <input name="guest_count" type="number" min="1" max="32767" required class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5">
            </label>
            <label class="block text-sm font-medium text-slate-700">Nhân viên phụ trách
                <select name="opened_by" data-update-employee required class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5"></select>
            </label>
            <label class="block text-sm font-medium text-slate-700">Ghi chú phiên
                <textarea name="note" rows="3" maxlength="255" class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5"></textarea>
            </label>
            <p data-update-error class="hidden rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-3 text-sm text-rose-700"></p>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" data-modal-close class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Hủy</button>
                <button type="submit" data-update-submit class="rounded-xl bg-emerald-800 px-4 py-2.5 text-sm font-semibold text-white">Lưu phiên</button>
            </div>
        </form>
    </div>
</div>
