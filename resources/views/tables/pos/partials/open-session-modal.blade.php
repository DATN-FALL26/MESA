<div data-modal="open-session" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-slate-950/40 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="open-session-title">
    <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl shadow-slate-900/20">
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Bắt đầu phục vụ</p>
                <h2 id="open-session-title" data-open-title class="mt-1 text-lg font-semibold text-slate-900">Mở bàn</h2>
            </div>
            <button type="button" data-modal-close class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Đóng hộp thoại">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 6 12 12M18 6 6 18" /></svg>
            </button>
        </div>

        <form data-open-session-form class="space-y-5 px-6 py-5">
            <label class="block text-sm font-medium text-slate-700">
                Số lượng khách
                <input name="guest_count" type="number" min="1" max="32767" value="2" required class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10">
            </label>

            <label class="block text-sm font-medium text-slate-700">
                Nhân viên phục vụ
                <select name="opened_by" data-employee-select class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10">
                    <option value="">Tài khoản đang đăng nhập</option>
                </select>
            </label>

            <p data-open-error class="hidden rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-3 text-sm text-rose-700" role="alert"></p>

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" data-modal-close class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Để sau</button>
                <button type="submit" data-open-submit class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:cursor-wait disabled:opacity-60">Xác nhận mở bàn</button>
            </div>
        </form>
    </div>
</div>
