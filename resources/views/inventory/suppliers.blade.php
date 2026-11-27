@extends('layouts.auth')

@section('title', 'Nhà cung cấp - MESA Operations')
@section('heading', 'Quản lý Nhà cung cấp')

@section('content')
    <div class="space-y-6" id="suppliers-app">
        <!-- HEADER & TOOLBAR -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200">
            <div>
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Quản lý Nhà cung cấp</h1>
                <p class="text-xs text-slate-500 mt-1">Danh bạ đối tác cung ứng thịt bò, bánh phở, gia vị và trang thiết bị cho chuỗi</p>
            </div>
            <div class="flex items-center gap-3">
                <button 
                    type="button" 
                    onclick="openCreateSupplierModal()" 
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition-colors shadow-2xs"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Thêm nhà cung cấp</span>
                </button>
            </div>
        </div>

        <!-- CẢNH BÁO HỒ SƠ ATVSTP HẾT HẠN / SẮP HẾT HẠN -->
        <div id="atvstp-alerts-container" class="hidden rounded-xl border border-amber-200 bg-amber-50/80 p-4 text-xs text-amber-900 shadow-2xs">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <div class="flex-1 space-y-1">
                    <div class="flex items-center justify-between">
                        <h4 class="font-bold text-amber-950 text-sm">Cảnh báo Hồ sơ An toàn Vệ sinh Thực phẩm & Kiểm định</h4>
                        <span id="atvstp-alerts-count" class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-200 text-amber-900">0 cảnh báo</span>
                    </div>
                    <p class="text-amber-800 text-xs">Các hồ sơ ATVSTP, chứng nhận hoặc phiếu kiểm nghiệm của nhà cung cấp sắp hết hạn (trong vòng 30 ngày) hoặc đã quá hạn:</p>
                    <div id="atvstp-alerts-list" class="mt-2 space-y-1.5 max-h-36 overflow-y-auto pr-1"></div>
                </div>
            </div>
        </div>

        <!-- SEARCH & FILTERS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-white p-4 rounded-xl border border-slate-200">
            <div class="md:col-span-2 relative">
                <input 
                    type="text" 
                    id="filter-supplier-search" 
                    placeholder="Tìm theo tên nhà cung cấp, người liên hệ hoặc số điện thoại..." 
                    class="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                    onkeyup="if(event.key === 'Enter') applySupplierFilter()"
                >
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>
            <div class="flex items-center gap-2">
                <select 
                    id="filter-supplier-status" 
                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-emerald-600"
                    onchange="applySupplierFilter()"
                >
                    <option value="">-- Trạng thái hợp tác --</option>
                    <option value="1">Đang hợp tác (Active)</option>
                    <option value="0">Tạm dừng (Inactive)</option>
                </select>
                <button 
                    type="button" 
                    onclick="resetSupplierFilter()" 
                    class="px-3 py-2 text-xs text-slate-600 hover:text-slate-900 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors shrink-0"
                    title="Đặt lại bộ lọc"
                >
                    Làm mới
                </button>
            </div>
        </div>

        <!-- TABLE CONTAINER -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider font-semibold">
                            <th class="py-3 px-4">Tên nhà cung cấp</th>
                            <th class="py-3 px-4">Người liên hệ</th>
                            <th class="py-3 px-4">Số điện thoại</th>
                            <th class="py-3 px-4">Địa chỉ kho / trụ sở</th>
                            <th class="py-3 px-4 text-center">Hồ sơ ATVSTP</th>
                            <th class="py-3 px-4 text-center">Trạng thái</th>
                            <th class="py-3 px-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="suppliers-table-body" class="divide-y divide-slate-100 text-slate-700">
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="inline-flex items-center gap-2">
                                    <svg class="w-4 h-4 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>Đang tải dữ liệu nhà cung cấp...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div class="flex items-center justify-between px-4 py-3 bg-slate-50 border-t border-slate-200 text-xs text-slate-600">
                <div id="supplier-pagination-info">Hiển thị 0 / 0 nhà cung cấp</div>
                <div class="flex items-center gap-2">
                    <button id="btn-supplier-prev" onclick="changeSupplierPage(-1)" disabled class="px-3 py-1.5 border border-slate-300 rounded-md bg-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-100">Trước</button>
                    <span id="supplier-page-display" class="px-2 font-semibold text-slate-800">1</span>
                    <button id="btn-supplier-next" onclick="changeSupplierPage(1)" disabled class="px-3 py-1.5 border border-slate-300 rounded-md bg-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-100">Sau</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL THÊM / SỬA NHÀ CUNG CẤP -->
    <div id="supplier-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs hidden">
        <div class="bg-white rounded-xl shadow-lg border border-slate-200 w-full max-w-md p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 id="supplier-modal-title" class="text-sm font-bold text-slate-900">Thêm nhà cung cấp mới</h3>
                <button type="button" onclick="closeSupplierModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="supplier-form" onsubmit="saveSupplier(event)" class="space-y-3.5 text-xs">
                <input type="hidden" id="supplier-id" value="">

                <div>
                    <label class="block font-medium text-slate-700 mb-1">Tên nhà cung cấp / Công ty <span class="text-rose-500">*</span></label>
                    <input 
                        type="text" 
                        id="input-supplier-name" 
                        required 
                        placeholder="VD: Công ty TNHH Thực Phẩm Sạch Hà Nội" 
                        class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-emerald-600"
                    >
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Người đại diện / Liên hệ</label>
                        <input 
                            type="text" 
                            id="input-supplier-contact" 
                            placeholder="Nguyễn Văn A" 
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-emerald-600"
                        >
                    </div>
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Số điện thoại</label>
                        <input 
                            type="text" 
                            id="input-supplier-phone" 
                            placeholder="0987xxxxxx" 
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-emerald-600"
                        >
                    </div>
                </div>

                <div>
                    <label class="block font-medium text-slate-700 mb-1">Địa chỉ văn phòng / kho hàng</label>
                    <input 
                        type="text" 
                        id="input-supplier-address" 
                        placeholder="Số 123 đường Giải Phóng, Hà Nội..." 
                        class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-emerald-600"
                    >
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" id="input-supplier-active" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <label for="input-supplier-active" class="text-slate-700 font-medium">Đang trong trạng thái hợp tác cung ứng</label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                    <button 
                        type="button" 
                        onclick="closeSupplierModal()" 
                        class="px-3.5 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition-colors"
                    >
                        Hủy
                    </button>
                    <button 
                        type="submit" 
                        id="btn-save-supplier" 
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors"
                    >
                        Lưu nhà cung cấp
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL XÓA NHÀ CUNG CẤP -->
    <div id="delete-supplier-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs hidden">
        <div class="bg-white rounded-xl shadow-lg border border-slate-200 w-full max-w-sm p-5 space-y-4 text-xs">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-sm">Xóa nhà cung cấp</h3>
                    <p class="text-slate-500 mt-0.5">Kiểm tra đơn mua hàng (PO) liên quan</p>
                </div>
            </div>

            <p class="text-slate-700">
                Bạn có chắc chắn muốn xóa đối tác <strong id="delete-supplier-name" class="text-slate-900"></strong>?
            </p>

            <div id="delete-supplier-error-box" class="p-2.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 hidden"></div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button 
                    type="button" 
                    onclick="closeDeleteSupplierModal()" 
                    class="px-3.5 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition-colors"
                >
                    Hủy bỏ
                </button>
                <button 
                    type="button" 
                    id="btn-confirm-delete-supplier" 
                    onclick="confirmDeleteSupplier()" 
                    class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-medium rounded-lg transition-colors"
                >
                    Xác nhận xóa
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL QUẢN LÝ HỒ SƠ & GIẤY TỜ ATVSTP CỦA NHÀ CUNG CẤP -->
    <div id="supplier-documents-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs hidden p-4">
        <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-4xl max-h-[90vh] flex flex-col text-xs overflow-hidden">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 bg-slate-50 shrink-0">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <h3 class="text-sm font-bold text-slate-900">
                            Hồ sơ An toàn vệ sinh thực phẩm (ATVSTP) & Pháp lý
                        </h3>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Nhà cung cấp: <strong id="doc-modal-supplier-name" class="text-slate-800"></strong>
                    </p>
                </div>
                <button type="button" onclick="closeSupplierDocumentsModal()" class="text-slate-400 hover:text-slate-600 p-1">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Body (Scrollable) -->
            <div class="p-6 space-y-6 overflow-y-auto flex-1">
                <!-- Danh sách tài liệu hiện có -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="font-bold text-slate-800 flex items-center gap-1.5 text-xs">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            <span>Danh sách chứng từ / hồ sơ đã lưu</span>
                        </h4>
                        <span id="doc-modal-count-badge" class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600">0 tài liệu</span>
                    </div>

                    <div class="border border-slate-200 rounded-lg overflow-hidden">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider font-semibold">
                                    <th class="py-2.5 px-3">Loại chứng từ</th>
                                    <th class="py-2.5 px-3">Tên tài liệu & Số hiệu</th>
                                    <th class="py-2.5 px-3">Cơ quan cấp</th>
                                    <th class="py-2.5 px-3">Hạn sử dụng</th>
                                    <th class="py-2.5 px-3 text-center">Trạng thái</th>
                                    <th class="py-2.5 px-3 text-right">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="supplier-documents-table-body" class="divide-y divide-slate-100 text-slate-700">
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-slate-400">Đang tải hồ sơ...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Form tải lên hồ sơ mới -->
                <div class="border border-slate-200 bg-slate-50/50 rounded-xl p-4 space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <h4 class="font-bold text-slate-800 text-xs">Tải lên hồ sơ / chứng nhận mới</h4>
                    </div>

                    <form id="upload-doc-form" onsubmit="saveSupplierDocument(event)" class="space-y-3">
                        <div id="doc-form-error" class="hidden p-2.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs"></div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-slate-700 mb-1">Loại giấy tờ / Chứng nhận <span class="text-rose-500">*</span></label>
                                <select id="doc-input-type" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-emerald-600 bg-white">
                                    <option value="">-- Chọn loại tài liệu --</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-medium text-slate-700 mb-1">Tên tài liệu / Hồ sơ <span class="text-rose-500">*</span></label>
                                <input 
                                    type="text" 
                                    id="doc-input-name" 
                                    required 
                                    placeholder="VD: Chứng nhận cơ sở đủ điều kiện ATTP" 
                                    class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-emerald-600 bg-white"
                                >
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block font-medium text-slate-700 mb-1">Số hiệu văn bản / giấy phép</label>
                                <input 
                                    type="text" 
                                    id="doc-input-number" 
                                    placeholder="VD: 123/2026/ATTP-ĐN" 
                                    class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-emerald-600 bg-white"
                                >
                            </div>
                            <div>
                                <label class="block font-medium text-slate-700 mb-1">Cơ quan cấp</label>
                                <input 
                                    type="text" 
                                    id="doc-input-authority" 
                                    placeholder="VD: Chi cục ATVSTP Hà Nội" 
                                    class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-emerald-600 bg-white"
                                >
                            </div>
                            <div>
                                <label class="block font-medium text-slate-700 mb-1">Tệp tài liệu đính kèm (PDF, Ảnh) <span class="text-rose-500">*</span></label>
                                <input 
                                    type="file" 
                                    id="doc-input-file" 
                                    required 
                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" 
                                    class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer"
                                >
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block font-medium text-slate-700 mb-1">Ngày cấp</label>
                                <input 
                                    type="date" 
                                    id="doc-input-issue-date" 
                                    class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-emerald-600 bg-white"
                                >
                            </div>
                            <div>
                                <label class="block font-medium text-slate-700 mb-1">Ngày hết hạn (để trống nếu vô thời hạn)</label>
                                <input 
                                    type="date" 
                                    id="doc-input-expiry-date" 
                                    class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-emerald-600 bg-white"
                                >
                            </div>
                            <div>
                                <label class="block font-medium text-slate-700 mb-1">Ghi chú bổ sung</label>
                                <input 
                                    type="text" 
                                    id="doc-input-notes" 
                                    placeholder="Ghi chú chi tiết chỉ tiêu kiểm tra..." 
                                    class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-emerald-600 bg-white"
                                >
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button 
                                type="submit" 
                                id="btn-save-doc" 
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors text-xs shadow-2xs"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                </svg>
                                <span>Tải lên & Lưu hồ sơ</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end px-6 py-3 bg-slate-50 border-t border-slate-200 shrink-0">
                <button 
                    type="button" 
                    onclick="closeSupplierDocumentsModal()" 
                    class="px-4 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-100 transition-colors"
                >
                    Đóng
                </button>
            </div>
        </div>
    </div>

    <!-- CLIENT SCRIPT GỌI API /api/v1/inventory/suppliers -->
    <script>
        let currentSupplierPage = 1;
        let lastSupplierPage = 1;
        let deleteSupplierTargetId = null;
        let currentDocSupplierId = null;
        let documentTypesCache = [];

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        document.addEventListener('DOMContentLoaded', () => {
            loadSuppliers();
            loadExpiringAlerts();
        });

        async function loadExpiringAlerts() {
            try {
                const res = await fetch('/api/v1/inventory/supplier-documents/expiring?days=30', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) return;
                const json = await res.json();
                const items = json.data || [];
                const container = document.getElementById('atvstp-alerts-container');
                const list = document.getElementById('atvstp-alerts-list');
                const countBadge = document.getElementById('atvstp-alerts-count');

                if (items.length === 0) {
                    container.classList.add('hidden');
                    return;
                }

                countBadge.innerText = `${items.length} cảnh báo`;
                list.innerHTML = items.map(doc => {
                    const statusText = doc.is_expired
                        ? `<span class="text-rose-700 font-bold">ĐÃ HẾT HẠN</span>`
                        : `<span class="text-amber-700 font-semibold">Còn ${doc.days_until_expiration} ngày</span>`;
                    return `
                        <div class="flex items-center justify-between py-1 px-2 rounded bg-amber-100/60 border border-amber-200/80">
                            <span class="truncate">
                                <strong>${escapeHtml(doc.supplier_name || 'Đối tác')}</strong>: 
                                ${escapeHtml(doc.document_name)} (${escapeHtml(doc.document_type_label)})
                            </span>
                            <span class="shrink-0 ml-2">${statusText}</span>
                        </div>
                    `;
                }).join('');

                container.classList.remove('hidden');
            } catch (e) {
                console.error('Lỗi khi tải cảnh báo ATVSTP:', e);
            }
        }

        async function loadSuppliers(page = 1) {
            currentSupplierPage = page;
            const search = document.getElementById('filter-supplier-search').value.trim();
            const status = document.getElementById('filter-supplier-status').value;

            const params = new URLSearchParams({
                page: currentSupplierPage,
                per_page: 15
            });

            if (search) params.append('search', search);
            if (status !== '') params.append('is_active', status);

            const tbody = document.getElementById('suppliers-table-body');
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="py-8 text-center text-slate-400">
                        <span class="inline-flex items-center gap-2">
                            <span class="w-3.5 h-3.5 border-2 border-emerald-600 border-t-transparent rounded-full animate-spin"></span>
                            Đang tải danh sách nhà cung cấp...
                        </span>
                    </td>
                </tr>
            `;

            try {
                const res = await fetch(`/api/v1/inventory/suppliers?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!res.ok) throw new Error('Không thể tải danh sách nhà cung cấp');

                const json = await res.json();
                renderSuppliersTable(json.data || []);
                renderSupplierPagination(json.meta || {});
            } catch (err) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="py-8 text-center text-rose-500 font-medium">
                            Lỗi khi tải dữ liệu: ${err.message}
                        </td>
                    </tr>
                `;
            }
        }

        function renderSuppliersTable(items) {
            const tbody = document.getElementById('suppliers-table-body');
            if (items.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">
                            Không tìm thấy nhà cung cấp nào.
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = items.map(item => {
                const statusBadge = item.is_active
                    ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Đang hợp tác</span>`
                    : `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-500 border border-slate-200">Tạm dừng</span>`;

                let atvstpBadge = '';
                if (item.has_valid_food_safety) {
                    atvstpBadge = `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-300" title="Đã có chứng nhận ATVSTP/HACCP còn hiệu lực">
                        <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        Đạt chuẩn (${item.documents_count})
                    </span>`;
                } else if (item.documents_count > 0) {
                    atvstpBadge = `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800 border border-amber-300" title="Chưa có giấy chứng nhận ATVSTP hoặc đã quá hạn">
                        <svg class="w-3 h-3 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                        Chưa đủ ATTP (${item.documents_count})
                    </span>`;
                } else {
                    atvstpBadge = `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-500 border border-slate-200">
                        Chưa có hồ sơ
                    </span>`;
                }

                const itemDataEscaped = encodeURIComponent(JSON.stringify(item));

                return `
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="py-3 px-4 font-semibold text-slate-900">${escapeHtml(item.name)}</td>
                        <td class="py-3 px-4 font-medium text-slate-700">${escapeHtml(item.contact || '—')}</td>
                        <td class="py-3 px-4 font-mono text-slate-600">${escapeHtml(item.phone || '—')}</td>
                        <td class="py-3 px-4 text-slate-600 truncate max-w-xs" title="${escapeHtml(item.address || '')}">${escapeHtml(item.address || '—')}</td>
                        <td class="py-3 px-4 text-center">${atvstpBadge}</td>
                        <td class="py-3 px-4 text-center">${statusBadge}</td>
                        <td class="py-3 px-4 text-right whitespace-nowrap">
                            <button 
                                type="button" 
                                onclick="openSupplierDocumentsModal(${item.id}, '${escapeHtml(item.name)}')" 
                                class="inline-flex items-center gap-1 px-2 py-1 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded font-medium mr-1 transition-colors"
                                title="Quản lý hồ sơ ATVSTP"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <span>Hồ sơ (${item.documents_count || 0})</span>
                            </button>
                            <button 
                                type="button" 
                                onclick="openEditSupplierModal('${itemDataEscaped}')" 
                                class="px-2 py-1 text-slate-600 hover:text-emerald-700 font-medium hover:underline"
                            >
                                Sửa
                            </button>
                            <span class="text-slate-300">|</span>
                            <button 
                                type="button" 
                                onclick="openDeleteSupplierModal(${item.id}, '${escapeHtml(item.name)}')" 
                                class="px-2 py-1 text-slate-500 hover:text-rose-600 font-medium hover:underline"
                            >
                                Xóa
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function renderSupplierPagination(meta) {
            currentSupplierPage = meta.current_page || 1;
            lastSupplierPage = meta.last_page || 1;
            const total = meta.total || 0;

            document.getElementById('supplier-pagination-info').innerText = `Hiển thị trang ${currentSupplierPage} / ${lastSupplierPage} (Tổng số ${total} đối tác)`;
            document.getElementById('supplier-page-display').innerText = currentSupplierPage;

            document.getElementById('btn-supplier-prev').disabled = currentSupplierPage <= 1;
            document.getElementById('btn-supplier-next').disabled = currentSupplierPage >= lastSupplierPage;
        }

        function changeSupplierPage(delta) {
            const next = currentSupplierPage + delta;
            if (next >= 1 && next <= lastSupplierPage) {
                loadSuppliers(next);
            }
        }

        function applySupplierFilter() {
            loadSuppliers(1);
        }

        function resetSupplierFilter() {
            document.getElementById('filter-supplier-search').value = '';
            document.getElementById('filter-supplier-status').value = '';
            loadSuppliers(1);
        }

        function openCreateSupplierModal() {
            document.getElementById('supplier-modal-title').innerText = 'Thêm nhà cung cấp mới';
            document.getElementById('supplier-id').value = '';
            document.getElementById('input-supplier-name').value = '';
            document.getElementById('input-supplier-contact').value = '';
            document.getElementById('input-supplier-phone').value = '';
            document.getElementById('input-supplier-address').value = '';
            document.getElementById('input-supplier-active').checked = true;

            document.getElementById('supplier-modal').classList.remove('hidden');
        }

        function openEditSupplierModal(encodedItem) {
            const item = JSON.parse(decodeURIComponent(encodedItem));
            document.getElementById('supplier-modal-title').innerText = `Cập nhật đối tác: ${item.name}`;
            document.getElementById('supplier-id').value = item.id;
            document.getElementById('input-supplier-name').value = item.name;
            document.getElementById('input-supplier-contact').value = item.contact || '';
            document.getElementById('input-supplier-phone').value = item.phone || '';
            document.getElementById('input-supplier-address').value = item.address || '';
            document.getElementById('input-supplier-active').checked = !!item.is_active;

            document.getElementById('supplier-modal').classList.remove('hidden');
        }

        function closeSupplierModal() {
            document.getElementById('supplier-modal').classList.add('hidden');
        }

        async function saveSupplier(e) {
            e.preventDefault();
            const id = document.getElementById('supplier-id').value;
            const isEditing = !!id;

            const payload = {
                name: document.getElementById('input-supplier-name').value.trim(),
                contact: document.getElementById('input-supplier-contact').value.trim() || null,
                phone: document.getElementById('input-supplier-phone').value.trim() || null,
                address: document.getElementById('input-supplier-address').value.trim() || null,
                is_active: document.getElementById('input-supplier-active').checked
            };

            const url = isEditing ? `/api/v1/inventory/suppliers/${id}` : '/api/v1/inventory/suppliers';
            const method = isEditing ? 'PUT' : 'POST';

            const btn = document.getElementById('btn-save-supplier');
            btn.disabled = true;
            btn.innerText = 'Đang lưu...';

            try {
                const res = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (!res.ok) {
                    const message = data.message || Object.values(data.errors || {})[0]?.[0] || 'Lỗi khi lưu nhà cung cấp';
                    window.showToast(message, 'error');
                    return;
                }

                window.showToast(isEditing ? 'Cập nhật nhà cung cấp thành công.' : 'Thêm mới nhà cung cấp thành công.', 'success');
                closeSupplierModal();
                loadSuppliers(currentSupplierPage);
            } catch (err) {
                window.showToast(err.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Lưu nhà cung cấp';
            }
        }

        function openDeleteSupplierModal(id, name) {
            deleteSupplierTargetId = id;
            document.getElementById('delete-supplier-name').innerText = name;
            document.getElementById('delete-supplier-error-box').classList.add('hidden');
            document.getElementById('delete-supplier-error-box').innerText = '';
            document.getElementById('delete-supplier-modal').classList.remove('hidden');
        }

        function closeDeleteSupplierModal() {
            document.getElementById('delete-supplier-modal').classList.add('hidden');
            deleteSupplierTargetId = null;
        }

        async function confirmDeleteSupplier() {
            if (!deleteSupplierTargetId) return;

            const btn = document.getElementById('btn-confirm-delete-supplier');
            const errBox = document.getElementById('delete-supplier-error-box');
            btn.disabled = true;
            btn.innerText = 'Đang xóa...';
            errBox.classList.add('hidden');

            try {
                const res = await fetch(`/api/v1/inventory/suppliers/${deleteSupplierTargetId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await res.json();

                if (!res.ok) {
                    const msg = data.message || 'Không thể xóa nhà cung cấp đã có đơn mua hàng liên quan.';
                    errBox.innerText = msg;
                    errBox.classList.remove('hidden');
                    return;
                }

                window.showToast(data.message || 'Đã xóa nhà cung cấp thành công.', 'success');
                closeDeleteSupplierModal();
                loadSuppliers(currentSupplierPage);
            } catch (err) {
                errBox.innerText = err.message;
                errBox.classList.remove('hidden');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Xác nhận xóa';
            }
        }

        /* =========================================================
         * QUẢN LÝ HỒ SƠ & GIẤY TỜ ATVSTP CỦA NHÀ CUNG CẤP
         * ========================================================= */
        async function loadDocumentTypes() {
            if (documentTypesCache.length > 0) return documentTypesCache;
            try {
                const res = await fetch('/api/v1/inventory/supplier-documents/types', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) return [];
                const json = await res.json();
                documentTypesCache = json.data || [];
                const select = document.getElementById('doc-input-type');
                select.innerHTML = '<option value="">-- Chọn loại tài liệu --</option>' + 
                    documentTypesCache.map(t => `<option value="${t.value}">${escapeHtml(t.label)}</option>`).join('');
                return documentTypesCache;
            } catch (err) {
                console.error('Lỗi khi tải danh mục loại tài liệu:', err);
                return [];
            }
        }

        async function openSupplierDocumentsModal(supplierId, supplierName) {
            currentDocSupplierId = supplierId;
            document.getElementById('doc-modal-supplier-name').innerText = supplierName;
            document.getElementById('doc-form-error').classList.add('hidden');
            document.getElementById('doc-form-error').innerText = '';
            document.getElementById('upload-doc-form').reset();
            document.getElementById('supplier-documents-modal').classList.remove('hidden');

            await loadDocumentTypes();
            await loadSupplierDocuments(supplierId);
        }

        function closeSupplierDocumentsModal() {
            document.getElementById('supplier-documents-modal').classList.add('hidden');
            currentDocSupplierId = null;
        }

        async function loadSupplierDocuments(supplierId) {
            const tbody = document.getElementById('supplier-documents-table-body');
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="py-6 text-center text-slate-400">
                        <span class="inline-flex items-center gap-2">
                            <span class="w-3.5 h-3.5 border-2 border-emerald-600 border-t-transparent rounded-full animate-spin"></span>
                            Đang tải danh sách hồ sơ...
                        </span>
                    </td>
                </tr>
            `;

            try {
                const res = await fetch(`/api/v1/inventory/suppliers/${supplierId}/documents`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('Không thể tải danh sách tài liệu');
                const json = await res.json();
                const docs = json.data || [];
                renderSupplierDocumentsTable(docs);
            } catch (err) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="py-4 text-center text-rose-500">
                            Lỗi khi tải hồ sơ: ${escapeHtml(err.message)}
                        </td>
                    </tr>
                `;
            }
        }

        function renderSupplierDocumentsTable(docs) {
            const tbody = document.getElementById('supplier-documents-table-body');
            const countBadge = document.getElementById('doc-modal-count-badge');
            countBadge.innerText = `${docs.length} tài liệu`;

            if (docs.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="py-6 text-center text-slate-400">
                            Chưa có tài liệu hay chứng nhận ATVSTP nào được lưu.
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = docs.map(doc => {
                let statusBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Hợp lệ</span>';
                if (doc.is_expired) {
                    statusBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">Hết hạn</span>';
                } else if (doc.is_expiring_soon) {
                    statusBadge = `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">Sắp hết hạn (${doc.days_until_expiration} ngày)</span>`;
                }

                const expiryDisplay = doc.expiry_date ? escapeHtml(doc.expiry_date) : '<span class="text-slate-400 italic">Vô thời hạn</span>';

                return `
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="py-2.5 px-3 font-semibold text-slate-800">${escapeHtml(doc.document_type_label)}</td>
                        <td class="py-2.5 px-3">
                            <div class="font-medium text-slate-900">${escapeHtml(doc.document_name)}</div>
                            <div class="text-[10px] text-slate-500 font-mono">${escapeHtml(doc.document_number || '—')}</div>
                        </td>
                        <td class="py-2.5 px-3 text-slate-600">${escapeHtml(doc.issuing_authority || '—')}</td>
                        <td class="py-2.5 px-3 font-mono text-slate-600">${expiryDisplay}</td>
                        <td class="py-2.5 px-3 text-center">${statusBadge}</td>
                        <td class="py-2.5 px-3 text-right whitespace-nowrap">
                            <a 
                                href="${doc.file_url}" 
                                target="_blank" 
                                class="inline-flex items-center gap-1 px-2 py-1 text-emerald-700 hover:text-emerald-900 font-medium hover:underline mr-1"
                                title="Xem / Tải file chứng nhận"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <span>Xem tệp</span>
                            </a>
                            <button 
                                type="button" 
                                onclick="deleteSupplierDocument(${doc.id})" 
                                class="inline-flex items-center gap-1 px-2 py-1 text-rose-600 hover:text-rose-800 font-medium hover:underline"
                                title="Xóa tài liệu này"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                                <span>Xóa</span>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        async function saveSupplierDocument(e) {
            e.preventDefault();
            if (!currentDocSupplierId) return;

            const errBox = document.getElementById('doc-form-error');
            errBox.classList.add('hidden');
            errBox.innerText = '';

            const btn = document.getElementById('btn-save-doc');
            const fileInput = document.getElementById('doc-input-file');
            if (!fileInput.files || fileInput.files.length === 0) {
                errBox.innerText = 'Vui lòng chọn tệp chứng nhận tải lên.';
                errBox.classList.remove('hidden');
                return;
            }

            const formData = new FormData();
            formData.append('document_type', document.getElementById('doc-input-type').value);
            formData.append('document_name', document.getElementById('doc-input-name').value.trim());
            formData.append('document_number', document.getElementById('doc-input-number').value.trim() || '');
            formData.append('issuing_authority', document.getElementById('doc-input-authority').value.trim() || '');
            formData.append('issue_date', document.getElementById('doc-input-issue-date').value || '');
            formData.append('expiry_date', document.getElementById('doc-input-expiry-date').value || '');
            formData.append('notes', document.getElementById('doc-input-notes').value.trim() || '');
            formData.append('file', fileInput.files[0]);

            btn.disabled = true;
            btn.innerHTML = `
                <span class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span>Đang tải lên...</span>
            `;

            try {
                const res = await fetch(`/api/v1/inventory/suppliers/${currentDocSupplierId}/documents`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const json = await res.json();
                if (!res.ok) {
                    const msg = json.message || Object.values(json.errors || {})[0]?.[0] || 'Lỗi khi lưu tài liệu';
                    errBox.innerText = msg;
                    errBox.classList.remove('hidden');
                    return;
                }

                window.showToast('Tải lên hồ sơ ATVSTP thành công!', 'success');
                document.getElementById('upload-doc-form').reset();
                await loadSupplierDocuments(currentDocSupplierId);
                await loadSuppliers(currentSupplierPage);
                await loadExpiringAlerts();
            } catch (err) {
                errBox.innerText = err.message;
                errBox.classList.remove('hidden');
            } finally {
                btn.disabled = false;
                btn.innerHTML = `
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                    </svg>
                    <span>Tải lên & Lưu hồ sơ</span>
                `;
            }
        }

        async function deleteSupplierDocument(docId) {
            if (!currentDocSupplierId) return;
            if (!confirm('Bạn có chắc chắn muốn xóa hồ sơ chứng từ này?')) return;

            try {
                const res = await fetch(`/api/v1/inventory/suppliers/${currentDocSupplierId}/documents/${docId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const json = await res.json();
                if (!res.ok) {
                    window.showToast(json.message || 'Không thể xóa tài liệu', 'error');
                    return;
                }

                window.showToast('Đã xóa tài liệu thành công.', 'success');
                await loadSupplierDocuments(currentDocSupplierId);
                await loadSuppliers(currentSupplierPage);
                await loadExpiringAlerts();
            } catch (err) {
                window.showToast(err.message, 'error');
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    </script>
@endsection
