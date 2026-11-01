@extends('layouts.auth')

@section('title', 'Kiểm kê & Điều chỉnh Kho - MESA Operations')
@section('heading', 'Kiểm kê, Cân đối & Xuất hủy Kho')

@section('content')
    <div class="space-y-6" id="adjustments-app">
        <!-- HEADER & TOOLBAR -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200">
            <div>
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Kiểm kê & Điều chỉnh Kho (Stocktake & Waste)</h1>
                <p class="text-xs text-slate-500 mt-1">Kiểm đếm định kỳ, đối soát số liệu sổ sách với thực tế, phê duyệt cân đối và xử lý xuất hủy hao hụt</p>
            </div>
            <div class="flex items-center gap-2">
                <button 
                    type="button" 
                    onclick="openDirectWasteModal()" 
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold rounded-lg transition-colors"
                >
                    <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                    <span>Xuất hủy nhanh</span>
                </button>
                <button 
                    type="button" 
                    onclick="openCreateAdjModal()" 
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition-colors shadow-2xs"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Tạo phiếu kiểm kê mới</span>
                </button>
            </div>
        </div>

        <!-- METRIC CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-xl border border-slate-200 flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium text-slate-500">Tổng phiếu kiểm kê</span>
                    <h3 class="text-xl font-bold text-slate-800 mt-1" id="stat-total-adj">0</h3>
                </div>
                <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                    </svg>
                </div>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium text-amber-600">Chờ duyệt cân đối</span>
                    <h3 class="text-xl font-bold text-amber-700 mt-1" id="stat-pending-adj">0</h3>
                </div>
                <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium text-emerald-600">Đã cân đối (Approved)</span>
                    <h3 class="text-xl font-bold text-emerald-700 mt-1" id="stat-approved-adj">0</h3>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium text-rose-600">Bị từ chối</span>
                    <h3 class="text-xl font-bold text-rose-700 mt-1" id="stat-rejected-adj">0</h3>
                </div>
                <div class="w-10 h-10 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- SEARCH & FILTERS -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 bg-white p-4 rounded-xl border border-slate-200">
            <div class="relative">
                <input 
                    type="text" 
                    id="filter-adj-search" 
                    placeholder="Tìm theo mã phiếu hoặc ghi chú..." 
                    class="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600"
                    onkeyup="if(event.key === 'Enter') applyAdjFilter()"
                >
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>
            <div>
                <select 
                    id="filter-adj-reason" 
                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-indigo-600"
                    onchange="applyAdjFilter()"
                >
                    <option value="">-- Tất cả lý do --</option>
                    <option value="stocktake">Kiểm kê định kỳ</option>
                    <option value="waste">Hao hụt tự nhiên</option>
                    <option value="damage">Hư hỏng vật tư</option>
                    <option value="correction">Sửa sai số liệu</option>
                    <option value="other">Lý do khác</option>
                </select>
            </div>
            <div>
                <select 
                    id="filter-adj-status" 
                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-indigo-600"
                    onchange="applyAdjFilter()"
                >
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="draft">Bản nháp (Đang đếm)</option>
                    <option value="pending_approval">Chờ duyệt cân đối</option>
                    <option value="approved">Đã duyệt cân đối</option>
                    <option value="rejected">Từ chối</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button 
                    type="button" 
                    onclick="applyAdjFilter()" 
                    class="w-full py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-medium rounded-lg transition-colors"
                >
                    Lọc dữ liệu
                </button>
                <button 
                    type="button" 
                    onclick="resetAdjFilter()" 
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
                            <th class="py-3 px-4">Mã phiếu (Adj No)</th>
                            <th class="py-3 px-4">Kho kiểm kê</th>
                            <th class="py-3 px-4 text-center">Lý do</th>
                            <th class="py-3 px-4 text-center">Trạng thái</th>
                            <th class="py-3 px-4 text-center">Số mặt hàng</th>
                            <th class="py-3 px-4">Người lập & Ngày tạo</th>
                            <th class="py-3 px-4">Người duyệt</th>
                            <th class="py-3 px-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="adj-table-body" class="divide-y divide-slate-100 text-slate-700">
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <div class="inline-flex items-center gap-2">
                                    <svg class="w-4 h-4 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>Đang tải danh sách phiếu kiểm kê...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div class="flex items-center justify-between px-4 py-3 bg-slate-50 border-t border-slate-200 text-xs text-slate-600">
                <div id="adj-pagination-info">Hiển thị 0 / 0 phiếu</div>
                <div class="flex items-center gap-2">
                    <button id="btn-adj-prev" onclick="changeAdjPage(-1)" disabled class="px-3 py-1.5 border border-slate-300 rounded-md bg-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-100">Trước</button>
                    <span id="adj-page-display" class="px-2 font-semibold text-slate-800">1</span>
                    <button id="btn-adj-next" onclick="changeAdjPage(1)" disabled class="px-3 py-1.5 border border-slate-300 rounded-md bg-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-100">Sau</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 1: TẠO PHIẾU KIỂM KÊ MỚI (CREATE MODAL) -->
    <div id="modal-create-adj" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl border border-slate-200 overflow-hidden my-8">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Lập Phiếu Kiểm Kê Kho Mới</h3>
                    <p class="text-xs text-slate-500">Hệ thống sẽ tự động chụp snapshot tồn sổ sách tức thời</p>
                </div>
                <button type="button" onclick="closeCreateAdjModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="form-create-adj" onsubmit="submitCreateAdj(event)" class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kho kiểm kê <span class="text-rose-500">*</span></label>
                    <select id="adj-warehouse-id" required class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-indigo-600">
                        <option value="1">Kho Bếp Ba Đình (Kho chi nhánh)</option>
                        <option value="2">Kho Trung Tâm MESA Hà Nội</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Lý do kiểm kê / điều chỉnh <span class="text-rose-500">*</span></label>
                    <select id="adj-reason" required class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-indigo-600">
                        <option value="stocktake">Kiểm kê định kỳ (Cuối ngày / Cuối tuần)</option>
                        <option value="waste">Hao hụt tự nhiên trong quá trình bảo quản</option>
                        <option value="damage">Hư hỏng nguyên vật liệu</option>
                        <option value="correction">Điều chỉnh sai sót nhập liệu trước đó</option>
                        <option value="other">Lý do khác</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ghi chú phiếu kiểm kê</label>
                    <textarea id="adj-note" rows="2" placeholder="Ví dụ: Kiểm kê định kỳ chốt tồn cuối tuần ca tối..." class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-indigo-600"></textarea>
                </div>

                <div class="p-3 bg-indigo-50 border border-indigo-100 rounded-lg text-[11px] text-indigo-800">
                    <p class="font-semibold">Cơ chế Snapshot tự động:</p>
                    <p class="mt-0.5">Khi tạo phiếu, số lượng tồn kho tức thời của tất cả nguyên liệu sẽ được chụp lại để nhân viên tiến hành đếm thực tế và đối chiếu chênh lệch.</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeCreateAdjModal()" class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50">Hủy</button>
                    <button type="submit" id="btn-submit-create-adj" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs">Khởi Tạo Phiếu</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: CHI TIẾT & CẬP NHẬT ĐẾM THỰC TẾ (DETAIL / COUNT MODAL) -->
    <div id="modal-detail-adj" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-4xl rounded-2xl shadow-xl border border-slate-200 overflow-hidden my-8">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <span id="detail-adj-no" class="font-bold text-sm text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-md">ADJ</span>
                    <span id="detail-adj-status-badge"></span>
                    <span id="detail-adj-reason-badge"></span>
                </div>
                <button type="button" onclick="closeDetailAdjModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 bg-slate-50 rounded-xl text-xs">
                    <div>
                        <span class="text-slate-400 block">Kho kiểm kê:</span>
                        <strong id="detail-adj-warehouse" class="text-slate-800 font-semibold block mt-0.5">-</strong>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Người lập phiếu:</span>
                        <strong id="detail-adj-creator" class="text-slate-800 font-semibold block mt-0.5">-</strong>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Người phê duyệt:</span>
                        <strong id="detail-adj-approver" class="text-slate-800 font-semibold block mt-0.5">-</strong>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Ngày tạo:</span>
                        <strong id="detail-adj-created-at" class="text-slate-800 font-semibold block mt-0.5">-</strong>
                    </div>
                </div>

                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-3 text-left font-semibold">Nguyên vật liệu</th>
                                <th class="py-2.5 px-3 text-right font-semibold">ĐVT</th>
                                <th class="py-2.5 px-3 text-right font-semibold">Tồn sổ sách (Snapshot)</th>
                                <th class="py-2.5 px-3 text-right font-semibold w-32">Thực tế đếm</th>
                                <th class="py-2.5 px-3 text-right font-semibold w-28">Chênh lệch</th>
                                <th class="py-2.5 px-3 text-left font-semibold">Lý do chênh lệch</th>
                            </tr>
                        </thead>
                        <tbody id="detail-adj-items-body" class="divide-y divide-slate-100">
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                    <div id="detail-adj-actions" class="flex items-center gap-2">
                        <!-- Nút lưu số đếm hoặc gửi duyệt sinh động -->
                    </div>
                    <button type="button" onclick="closeDetailAdjModal()" class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 3: XUẤT HỦY HAO HỤT NHANH (DIRECT WASTE MODAL) -->
    <div id="modal-direct-waste" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl border border-slate-200 overflow-hidden my-8">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-rose-50/50">
                <div>
                    <h3 class="text-sm font-bold text-rose-950">Ghi Nhận Xuất Hủy / Hao Hụt Nhanh</h3>
                    <p class="text-xs text-rose-700">Trừ trực tiếp số lượng nguyên vật liệu bị hỏng hóc hoặc hết hạn</p>
                </div>
                <button type="button" onclick="closeDirectWasteModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="form-direct-waste" onsubmit="submitDirectWaste(event)" class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kho phát sinh <span class="text-rose-500">*</span></label>
                    <select id="waste-warehouse-id" required class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-rose-600">
                        <option value="1">Kho Bếp Ba Đình (Kho chi nhánh)</option>
                        <option value="2">Kho Trung Tâm MESA Hà Nội</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nguyên vật liệu xuất hủy <span class="text-rose-500">*</span></label>
                    <select id="waste-ingredient-id" required class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-rose-600">
                        <option value="">-- Chọn nguyên vật liệu --</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Số lượng xuất hủy <span class="text-rose-500">*</span></label>
                    <input type="number" id="waste-quantity" step="0.01" min="0.01" required placeholder="Ví dụ: 2.5" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-rose-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Lý do xuất hủy cụ thể <span class="text-rose-500">*</span></label>
                    <textarea id="waste-note" required rows="2" placeholder="Ví dụ: Bánh phở bị chua do bảo quản sai nhiệt độ tủ mát..." class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-rose-600"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeDirectWasteModal()" class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50">Hủy bỏ</button>
                    <button type="submit" id="btn-submit-waste" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold shadow-xs">Xác Nhận Xuất Hủy</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT APP LOGIC -->
    <script>
        let currentAdjPage = 1;
        let activeIngredientsList = [];
        let currentViewingAdj = null;

        document.addEventListener('DOMContentLoaded', () => {
            loadMasterData();
            loadAdjustments();
        });

        // 1. TẢI MASTER DATA CHO DROPDOWNS
        async function loadMasterData() {
            try {
                const res = await fetch('/api/v1/inventory/ingredients/active');
                if (res.ok) {
                    const data = await res.json();
                    activeIngredientsList = data.data || [];
                    populateWasteIngredientDropdown();
                }
            } catch (err) {
                console.error('Lỗi tải master data:', err);
            }
        }

        function populateWasteIngredientDropdown() {
            const select = document.getElementById('waste-ingredient-id');
            select.innerHTML = '<option value="">-- Chọn nguyên vật liệu --</option>';
            activeIngredientsList.forEach(ing => {
                select.innerHTML += `<option value="${ing.id}">${ing.sku} - ${ing.name} (${ing.base_uom})</option>`;
            });
        }

        // 2. TẢI DANH SÁCH ADJUSTMENTS
        async function loadAdjustments(page = 1) {
            currentAdjPage = page;
            const search = document.getElementById('filter-adj-search').value.trim();
            const reason = document.getElementById('filter-adj-reason').value;
            const status = document.getElementById('filter-adj-status').value;

            const params = new URLSearchParams({
                page: currentAdjPage,
                per_page: 15
            });
            if (search) params.append('search', search);
            if (reason) params.append('reason', reason);
            if (status) params.append('status', status);

            const tbody = document.getElementById('adj-table-body');
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="py-12 text-center text-slate-400">
                        <div class="inline-flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Đang làm mới dữ liệu kiểm kê...</span>
                        </div>
                    </td>
                </tr>
            `;

            try {
                const res = await fetch(`/api/v1/inventory/adjustments?${params.toString()}`);
                if (!res.ok) throw new Error('Lỗi tải dữ liệu kiểm kê');

                const json = await res.json();
                const adjustments = json.data || [];
                const meta = json.meta || {};

                renderAdjTable(adjustments);
                updatePagination(meta);
                updateStats(adjustments);
            } catch (err) {
                console.error(err);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="py-8 text-center text-rose-500 font-medium">
                            Đã xảy ra lỗi khi tải danh sách kiểm kê. Vui lòng thử lại!
                        </td>
                    </tr>
                `;
            }
        }

        function renderAdjTable(adjustments) {
            const tbody = document.getElementById('adj-table-body');
            if (adjustments.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            Không tìm thấy phiếu kiểm kê nào phù hợp bộ lọc.
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = adjustments.map(adj => {
                const statusBadge = getStatusBadge(adj.status, adj.status_label);
                const reasonBadge = getReasonBadge(adj.reason, adj.reason_label);

                let actionButtons = `
                    <button onclick="viewAdjDetail(${adj.id})" class="px-2.5 py-1 text-slate-600 hover:text-indigo-600 font-medium hover:bg-slate-100 rounded-md transition-colors" title="Xem chi tiết / Đếm">
                        ${adj.status === 'draft' ? 'Đếm thực tế' : 'Chi tiết'}
                    </button>
                `;

                if (adj.status === 'draft') {
                    actionButtons += `
                        <button onclick="submitAdj(${adj.id})" class="px-2.5 py-1 text-indigo-600 hover:text-indigo-800 font-semibold hover:bg-indigo-50 rounded-md transition-colors" title="Gửi duyệt cân đối">
                            Gửi duyệt
                        </button>
                    `;
                } else if (adj.status === 'pending_approval') {
                    actionButtons += `
                        <button onclick="approveAdj(${adj.id})" class="px-2.5 py-1 text-emerald-600 hover:text-emerald-800 font-semibold hover:bg-emerald-50 rounded-md transition-colors" title="Duyệt & Cân đối kho">
                            Duyệt
                        </button>
                        <button onclick="rejectAdj(${adj.id})" class="px-2 py-1 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-md transition-colors" title="Từ chối">
                            Từ chối
                        </button>
                    `;
                }

                return `
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-4 font-bold text-indigo-600">${adj.adjustment_no}</td>
                        <td class="py-3 px-4">
                            <div class="font-medium text-slate-800">${adj.warehouse_name || 'Kho Bếp'}</div>
                            <div class="text-[10px] text-slate-400">${adj.branch_name || ''}</div>
                        </td>
                        <td class="py-3 px-4 text-center">${reasonBadge}</td>
                        <td class="py-3 px-4 text-center">${statusBadge}</td>
                        <td class="py-3 px-4 text-center font-semibold text-slate-800">${adj.items_count || 0} món</td>
                        <td class="py-3 px-4">
                            <div class="text-slate-800">${adj.creator_name || 'Hệ thống'}</div>
                            <div class="text-[10px] text-slate-400">${formatDate(adj.created_at)}</div>
                        </td>
                        <td class="py-3 px-4 text-slate-600">
                            ${adj.approver_name || '<span class="text-slate-400 italic">Chưa duyệt</span>'}
                        </td>
                        <td class="py-3 px-4 text-right space-x-1">${actionButtons}</td>
                    </tr>
                `;
            }).join('');
        }

        function getStatusBadge(status, label) {
            switch (status) {
                case 'draft':
                    return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 text-slate-700"><span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>${label}</span>`;
                case 'pending_approval':
                    return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>${label}</span>`;
                case 'approved':
                    return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>${label}</span>`;
                case 'rejected':
                    return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium bg-rose-50 text-rose-700 border border-rose-200"><span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>${label}</span>`;
                default:
                    return `<span class="px-2 py-0.5 rounded text-[11px] bg-slate-100 text-slate-600">${label}</span>`;
            }
        }

        function getReasonBadge(reason, label) {
            switch (reason) {
                case 'stocktake':
                    return `<span class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">${label}</span>`;
                case 'waste':
                case 'damage':
                    return `<span class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-rose-50 text-rose-700 border border-rose-200">${label}</span>`;
                case 'correction':
                    return `<span class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-200">${label}</span>`;
                default:
                    return `<span class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-100 text-slate-600">${label}</span>`;
            }
        }

        function updateStats(adjustments) {
            document.getElementById('stat-total-adj').textContent = adjustments.length;
            document.getElementById('stat-pending-adj').textContent = adjustments.filter(a => a.status === 'pending_approval').length;
            document.getElementById('stat-approved-adj').textContent = adjustments.filter(a => a.status === 'approved').length;
            document.getElementById('stat-rejected-adj').textContent = adjustments.filter(a => a.status === 'rejected').length;
        }

        function updatePagination(meta) {
            const info = document.getElementById('adj-pagination-info');
            const btnPrev = document.getElementById('btn-adj-prev');
            const btnNext = document.getElementById('btn-adj-next');
            const pageDisplay = document.getElementById('adj-page-display');

            const total = meta.total || 0;
            const from = meta.from || 0;
            const to = meta.to || 0;
            const lastPage = meta.last_page || 1;

            info.textContent = `Hiển thị ${from} - ${to} / ${total} phiếu kiểm kê`;
            pageDisplay.textContent = currentAdjPage;
            btnPrev.disabled = currentAdjPage <= 1;
            btnNext.disabled = currentAdjPage >= lastPage;
        }

        function changeAdjPage(delta) {
            loadAdjustments(currentAdjPage + delta);
        }

        function applyAdjFilter() {
            loadAdjustments(1);
        }

        function resetAdjFilter() {
            document.getElementById('filter-adj-search').value = '';
            document.getElementById('filter-adj-reason').value = '';
            document.getElementById('filter-adj-status').value = '';
            loadAdjustments(1);
        }

        // 3. TẠO PHIẾU KIỂM KÊ MỚI
        function openCreateAdjModal() {
            document.getElementById('form-create-adj').reset();
            document.getElementById('modal-create-adj').classList.remove('hidden');
        }

        function closeCreateAdjModal() {
            document.getElementById('modal-create-adj').classList.add('hidden');
        }

        async function submitCreateAdj(event) {
            event.preventDefault();
            const btn = document.getElementById('btn-submit-create-adj');
            btn.disabled = true;
            btn.textContent = 'Đang khởi tạo snapshot...';

            const warehouseId = document.getElementById('adj-warehouse-id').value;
            const reason = document.getElementById('adj-reason').value;
            const note = document.getElementById('adj-note').value;

            try {
                const res = await fetch('/api/v1/inventory/adjustments', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        warehouse_id: parseInt(warehouseId),
                        reason: reason,
                        note: note
                    })
                });

                if (res.ok) {
                    notifyToast('Khởi tạo phiếu kiểm kê và snapshot tồn kho thành công!', 'success');
                    closeCreateAdjModal();
                    loadAdjustments(1);
                } else {
                    const data = await res.json();
                    notifyToast(data.message || 'Không thể tạo phiếu kiểm kê', 'error');
                }
            } catch (e) {
                notifyToast('Lỗi kết nối máy chủ', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Khởi Tạo Phiếu';
            }
        }

        // 4. XEM CHI TIẾT & CẬP NHẬT SỐ ĐẾM THỰC TẾ
        async function viewAdjDetail(id) {
            try {
                const res = await fetch(`/api/v1/inventory/adjustments/${id}`);
                if (!res.ok) throw new Error('Không thể tải chi tiết');

                const data = await res.json();
                const adj = data.data;
                currentViewingAdj = adj;

                document.getElementById('detail-adj-no').textContent = adj.adjustment_no;
                document.getElementById('detail-adj-status-badge').innerHTML = getStatusBadge(adj.status, adj.status_label);
                document.getElementById('detail-adj-reason-badge').innerHTML = getReasonBadge(adj.reason, adj.reason_label);
                document.getElementById('detail-adj-warehouse').textContent = adj.warehouse_name || 'Kho chính';
                document.getElementById('detail-adj-creator').textContent = adj.creator_name || 'N/A';
                document.getElementById('detail-adj-approver').textContent = adj.approver_name || 'Chưa duyệt';
                document.getElementById('detail-adj-created-at').textContent = formatDate(adj.created_at);

                const isDraft = adj.status === 'draft';
                const tbody = document.getElementById('detail-adj-items-body');

                tbody.innerHTML = (adj.items || []).map(item => {
                    const diff = item.difference_qty;
                    let diffDisplay = `<span class="text-slate-400 font-medium">Khớp (0)</span>`;
                    if (diff < 0) {
                        diffDisplay = `<span class="text-rose-600 font-bold">Thiếu ${Math.abs(diff)}</span>`;
                    } else if (diff > 0) {
                        diffDisplay = `<span class="text-emerald-600 font-bold">Thừa +${diff}</span>`;
                    }

                    const inputField = isDraft
                        ? `<input type="number" step="0.01" min="0" value="${item.actual_qty}" data-item-id="${item.id}" data-system-qty="${item.system_qty}" oninput="recalculateItemDiff(this)" class="adj-item-actual-input w-full px-2 py-1.5 text-xs text-right rounded border border-indigo-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 font-semibold text-slate-900">`
                        : `<span class="font-bold text-slate-800 text-right block">${item.actual_qty}</span>`;

                    const noteField = isDraft
                        ? `<input type="text" value="${item.note || ''}" placeholder="Lý do lệch..." class="adj-item-note-input w-full px-2 py-1 text-[11px] rounded border border-slate-200 focus:outline-none focus:border-indigo-500">`
                        : `<span class="text-slate-500 text-[11px] italic">${item.note || '-'}</span>`;

                    return `
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-2.5 px-3">
                                <span class="font-bold text-slate-800">${item.ingredient_sku}</span> - ${item.ingredient_name}
                            </td>
                            <td class="py-2.5 px-3 text-right text-slate-500">${item.base_uom}</td>
                            <td class="py-2.5 px-3 text-right font-semibold text-slate-700">${item.system_qty}</td>
                            <td class="py-2.5 px-3">${inputField}</td>
                            <td class="py-2.5 px-3 text-right adj-item-diff-cell">${diffDisplay}</td>
                            <td class="py-2.5 px-3">${noteField}</td>
                        </tr>
                    `;
                }).join('');

                const actionsContainer = document.getElementById('detail-adj-actions');
                if (isDraft) {
                    actionsContainer.innerHTML = `
                        <button type="button" onclick="saveActualCounts(${adj.id})" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-2xs">Lưu Số Đếm Thực Tế</button>
                        <button type="button" onclick="submitAdj(${adj.id})" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-2xs">Gửi Phê Duyệt Cân Đối</button>
                    `;
                } else if (adj.status === 'pending_approval') {
                    actionsContainer.innerHTML = `
                        <button type="button" onclick="approveAdj(${adj.id})" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-2xs">Phê Duyệt & Cân Đối Sổ Cái</button>
                        <button type="button" onclick="rejectAdj(${adj.id})" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold shadow-2xs">Từ Chối</button>
                    `;
                } else {
                    actionsContainer.innerHTML = '';
                }

                document.getElementById('modal-detail-adj').classList.remove('hidden');
            } catch (err) {
                notifyToast('Lỗi khi tải chi tiết phiếu kiểm kê', 'error');
            }
        }

        function closeDetailAdjModal() {
            document.getElementById('modal-detail-adj').classList.add('hidden');
        }

        function recalculateItemDiff(input) {
            const systemQty = parseFloat(input.dataset.systemQty) || 0;
            const actualQty = parseFloat(input.value) || 0;
            const diff = actualQty - systemQty;
            const tr = input.closest('tr');
            const diffCell = tr.querySelector('.adj-item-diff-cell');

            if (Math.abs(diff) < 0.0001) {
                diffCell.innerHTML = `<span class="text-slate-400 font-medium">Khớp (0)</span>`;
            } else if (diff < 0) {
                diffCell.innerHTML = `<span class="text-rose-600 font-bold">Thiếu ${Math.abs(diff).toFixed(2)}</span>`;
            } else {
                diffCell.innerHTML = `<span class="text-emerald-600 font-bold">Thừa +${diff.toFixed(2)}</span>`;
            }
        }

        async function saveActualCounts(id) {
            const items = [];
            document.querySelectorAll('#detail-adj-items-body tr').forEach(tr => {
                const inputQty = tr.querySelector('.adj-item-actual-input');
                const inputNote = tr.querySelector('.adj-item-note-input');
                if (inputQty) {
                    items.push({
                        id: parseInt(inputQty.dataset.itemId),
                        actual_qty: parseFloat(inputQty.value) || 0,
                        note: inputNote ? inputNote.value.trim() : null
                    });
                }
            });

            try {
                const res = await fetch(`/api/v1/inventory/adjustments/${id}/items`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ items: items })
                });

                if (res.ok) {
                    notifyToast('Đã lưu kết quả kiểm đếm thực tế thành công!', 'success');
                    viewAdjDetail(id);
                    loadAdjustments(currentAdjPage);
                } else {
                    const data = await res.json();
                    notifyToast(data.message || 'Lỗi khi lưu số đếm', 'error');
                }
            } catch (e) {
                notifyToast('Lỗi kết nối máy chủ', 'error');
            }
        }

        // 5. CÁC HÀNH ĐỘNG GỬI DUYỆT / DUYỆT / TỪ CHỐI
        async function submitAdj(id) {
            if (!confirm('Bạn có chắc muốn gửi phiếu kiểm kê này đi phê duyệt cân đối?')) return;
            try {
                const res = await fetch(`/api/v1/inventory/adjustments/${id}/submit`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                if (res.ok) {
                    notifyToast('Đã gửi phê duyệt cân đối thành công', 'success');
                    closeDetailAdjModal();
                    loadAdjustments(currentAdjPage);
                } else {
                    const data = await res.json();
                    notifyToast(data.message || 'Không thể gửi duyệt', 'error');
                }
            } catch (e) {
                notifyToast('Lỗi kết nối máy chủ', 'error');
            }
        }

        async function approveAdj(id) {
            if (!confirm('Xác nhận phê duyệt và tự động ghi sổ cân đối kho? Hành động này sẽ cập nhật trực tiếp sổ cái biến động kho!')) return;
            try {
                const res = await fetch(`/api/v1/inventory/adjustments/${id}/approve`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                if (res.ok) {
                    notifyToast('Đã phê duyệt và cân đối sổ cái kho thành công!', 'success');
                    closeDetailAdjModal();
                    loadAdjustments(currentAdjPage);
                } else {
                    const data = await res.json();
                    notifyToast(data.message || 'Không thể phê duyệt', 'error');
                }
            } catch (e) {
                notifyToast('Lỗi kết nối máy chủ', 'error');
            }
        }

        async function rejectAdj(id) {
            const note = prompt('Nhập lý do từ chối phiếu kiểm kê:');
            if (note === null) return;
            try {
                const res = await fetch(`/api/v1/inventory/adjustments/${id}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ note: note })
                });
                if (res.ok) {
                    notifyToast('Đã từ chối phiếu kiểm kê', 'info');
                    closeDetailAdjModal();
                    loadAdjustments(currentAdjPage);
                } else {
                    const data = await res.json();
                    notifyToast(data.message || 'Lỗi khi từ chối', 'error');
                }
            } catch (e) {
                notifyToast('Lỗi kết nối máy chủ', 'error');
            }
        }

        // 6. XUẤT HỦY NHANH (DIRECT WASTE)
        function openDirectWasteModal() {
            document.getElementById('form-direct-waste').reset();
            document.getElementById('modal-direct-waste').classList.remove('hidden');
        }

        function closeDirectWasteModal() {
            document.getElementById('modal-direct-waste').classList.add('hidden');
        }

        async function submitDirectWaste(event) {
            event.preventDefault();
            const btn = document.getElementById('btn-submit-waste');
            btn.disabled = true;
            btn.textContent = 'Đang xử lý xuất hủy...';

            const warehouseId = document.getElementById('waste-warehouse-id').value;
            const ingredientId = document.getElementById('waste-ingredient-id').value;
            const quantity = document.getElementById('waste-quantity').value;
            const note = document.getElementById('waste-note').value;

            try {
                const res = await fetch('/api/v1/inventory/waste', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        warehouse_id: parseInt(warehouseId),
                        ingredient_id: parseInt(ingredientId),
                        quantity: parseFloat(quantity),
                        note: note
                    })
                });

                if (res.ok) {
                    notifyToast('Đã ghi nhận xuất hủy hao hụt và trừ tồn kho thành công!', 'success');
                    closeDirectWasteModal();
                    loadAdjustments(1);
                } else {
                    const data = await res.json();
                    notifyToast(data.message || 'Lỗi xuất hủy', 'error');
                }
            } catch (e) {
                notifyToast('Lỗi kết nối máy chủ', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Xác Nhận Xuất Hủy';
            }
        }

        function formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('vi-VN') + ' ' + d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
        }

        function notifyToast(msg, type = 'info') {
            if (window.toast) {
                window.toast(msg, type);
            } else {
                alert(msg);
            }
        }
    </script>
@endsection
