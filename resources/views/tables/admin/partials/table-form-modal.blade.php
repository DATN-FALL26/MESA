<div data-modal="table-form" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-slate-950/40 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="table-form-title">
    <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl shadow-slate-900/20">
        <div class="flex items-start justify-between gap-4 border-b border-slate-700 bg-slate-900 px-6 py-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-300">Quản lý sơ đồ</p>
                <h2 id="table-form-title" data-form-title class="mt-1 text-lg font-semibold text-white">Thông tin bàn</h2>
            </div>
            <button type="button" data-modal-close class="rounded-lg p-2 text-slate-300 transition hover:bg-slate-800 hover:text-white" aria-label="Đóng hộp thoại">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 6 12 12M18 6 6 18" /></svg>
            </button>
        </div>

        <form data-table-form class="space-y-5 px-6 py-5">
            <input type="hidden" name="id">
            <input type="hidden" name="branch_id" value="{{ $currentBranchId }}">

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block text-sm font-medium text-slate-700">
                    Mã bàn
                    <input name="code" type="text" maxlength="20" required placeholder="Ví dụ: B01" class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-500 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10">
                </label>
                <label class="block text-sm font-medium text-slate-700">
                    Khu vực
                    <select name="area_id" required class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10">
                        @foreach ($areas as $area)
                            <option value="{{ $area->id }}">{{ $area->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block text-sm font-medium text-slate-700">
                    Số chỗ ngồi
                    <input name="seats" type="number" min="1" max="32767" value="4" required class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10">
                </label>
                <label class="block text-sm font-medium text-slate-700">
                    Trạng thái
                    <select name="status" required class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10">
                        <option value="available">Trống</option>
                        <option value="occupied">Đang dùng</option>
                        <option value="reserved">Đã đặt</option>
                        <option value="cleaning">Cần dọn</option>
                    </select>
                </label>
            </div>

            <p data-active-session-warning class="hidden rounded-xl border border-amber-300 bg-amber-50 px-3.5 py-3 text-sm font-medium text-amber-900" role="status">
                Bàn đang có phiên dùng bữa. Hãy đóng phiên ở phần chi tiết bàn trước khi chuyển trạng thái sang Trống.
            </p>
            <p data-form-error class="hidden rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-3 text-sm text-rose-700" role="alert"></p>

            <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                <button type="button" data-delete-table class="hidden rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 disabled:cursor-not-allowed disabled:opacity-50">Xóa bàn</button>
                <div class="flex justify-end gap-2 sm:ml-auto">
                    <button type="button" data-modal-close class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Hủy</button>
                    <button type="submit" data-save-table class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-900 disabled:cursor-wait disabled:opacity-70">
                        <span data-save-label>Lưu bàn</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
