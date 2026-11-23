<div data-modal="close-session" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-slate-950/40 p-4 backdrop-blur-sm" role="dialog" aria-modal="true">
    <div class="w-full max-w-sm overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="border-b border-slate-200 px-5 py-4">
            <p class="text-sm font-semibold text-slate-950">Đóng phiên dùng bữa</p>
            <h2 data-close-title class="mt-1 text-xs text-slate-500">Bàn đang chọn</h2>
        </div>
        <form data-close-session-form class="space-y-4 px-5 py-5">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                <p class="text-sm font-medium leading-6 text-slate-800">Khách đã thanh toán và rời bàn?</p>
                <p class="mt-1 text-xs leading-5 text-slate-500">Xác nhận sẽ đóng phiên và chuyển bàn sang <strong>Cần dọn</strong>.</p>
            </div>
            <p data-close-error class="hidden rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-3 text-sm text-rose-700"></p>
            <div class="grid grid-cols-2 gap-2 border-t border-slate-100 pt-4">
                <button type="button" data-modal-close class="rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-700">Chưa rời bàn</button>
                <button type="submit" data-close-submit class="rounded-xl bg-emerald-800 px-3 py-2.5 text-sm font-semibold text-white">Đã thanh toán, rời bàn</button>
            </div>
        </form>
    </div>
</div>
