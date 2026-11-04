{{-- Một hộp xác nhận dùng chung cho cả trang (xóa, mở bán lại...). Nút kích hoạt khai báo data-confirm="{json}". --}}
<div id="menu-confirm-modal" data-menu-modal role="dialog" aria-modal="true"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 hidden">
    <div class="bg-white rounded-xl shadow-lg border border-slate-200 w-full max-w-sm p-5 space-y-4 text-xs">
        <div class="flex items-center gap-3">
            <div data-confirm-icon class="w-9 h-9 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>
            <div>
                <h3 data-confirm-title class="font-bold text-slate-900 text-sm">Xác nhận</h3>
                <p data-confirm-subtitle class="text-slate-500 mt-0.5"></p>
            </div>
        </div>

        <p data-confirm-message class="text-slate-700"></p>

        <form method="POST" action="" data-confirm-form class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
            @csrf
            <input type="hidden" name="_method" value="DELETE" data-confirm-method>
            <button type="button" data-modal-close class="px-3.5 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition-colors">Hủy bỏ</button>
            <button type="submit" data-confirm-submit class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-medium rounded-lg transition-colors">Xác nhận</button>
        </form>
    </div>
</div>
