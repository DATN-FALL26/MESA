@extends('layouts.auth')

@section('title', 'Đơn nhập hàng (PO) - MESA Operations')
@section('heading', 'Quản lý Đơn nhập hàng (Purchase Orders)')

@section('content')
    <div class="space-y-6" id="po-app">
        <!-- HEADER & TOOLBAR -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200">
            <div>
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Quản lý Đơn nhập hàng (Purchase Orders)</h1>
                <p class="text-xs text-slate-500 mt-1">Lập kế hoạch mua hàng từ nhà cung cấp, quy trình phê duyệt ngân sách và kiểm nhận hàng vào kho</p>
            </div>
            <div class="flex items-center gap-3">
                <button 
                    type="button" 
                    onclick="openCreatePoModal()" 
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition-colors shadow-2xs"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Lập đơn nhập hàng mới</span>
                </button>
            </div>
        </div>

        <!-- METRIC CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-xl border border-slate-200 flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium text-slate-500">Tổng số đơn PO</span>
                    <h3 class="text-xl font-bold text-slate-800 mt-1" id="stat-total-po">0</h3>
                </div>
                <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                    </svg>
                </div>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium text-amber-600">Chờ phê duyệt</span>
                    <h3 class="text-xl font-bold text-amber-700 mt-1" id="stat-pending-po">0</h3>
                </div>
                <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium text-blue-600">Đã duyệt chờ nhập</span>
                    <h3 class="text-xl font-bold text-blue-700 mt-1" id="stat-approved-po">0</h3>
                </div>
                <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.948c0-.621-.504-1.125-1.125-1.125H2.25c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75" />
                    </svg>
                </div>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium text-emerald-600">Đã nhận hàng</span>
                    <h3 class="text-xl font-bold text-emerald-700 mt-1" id="stat-received-po">0</h3>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- SEARCH & FILTERS -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 bg-white p-4 rounded-xl border border-slate-200">
            <div class="md:col-span-2 relative">
                <input 
                    type="text" 
                    id="filter-po-search" 
                    placeholder="Tìm theo số đơn PO hoặc ghi chú..." 
                    class="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600"
                    onkeyup="if(event.key === 'Enter') applyPoFilter()"
                >
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>
            <div>
                <select 
                    id="filter-po-status" 
                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-indigo-600"
                    onchange="applyPoFilter()"
                >
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="draft">Bản nháp (Draft)</option>
                    <option value="pending_approval">Chờ duyệt (Pending)</option>
                    <option value="approved">Đã duyệt (Approved)</option>
                    <option value="received">Đã nhận hàng (Received)</option>
                    <option value="cancelled">Đã hủy (Cancelled)</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button 
                    type="button" 
                    onclick="applyPoFilter()" 
                    class="w-full py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-medium rounded-lg transition-colors"
                >
                    Lọc dữ liệu
                </button>
                <button 
                    type="button" 
                    onclick="resetPoFilter()" 
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
                            <th class="py-3 px-4">Mã đơn (PO No)</th>
                            <th class="py-3 px-4">Nhà cung cấp</th>
                            <th class="py-3 px-4">Kho nhập</th>
                            <th class="py-3 px-4 text-center">Trạng thái</th>
                            <th class="py-3 px-4 text-center">Nguồn tạo</th>
                            <th class="py-3 px-4 text-right">Tổng tiền dự kiến</th>
                            <th class="py-3 px-4">Người tạo & Ngày lập</th>
                            <th class="py-3 px-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="po-table-body" class="divide-y divide-slate-100 text-slate-700">
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <div class="inline-flex items-center gap-2">
                                    <svg class="w-4 h-4 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>Đang tải danh sách đơn đặt hàng...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div class="flex items-center justify-between px-4 py-3 bg-slate-50 border-t border-slate-200 text-xs text-slate-600">
                <div id="po-pagination-info">Hiển thị 0 / 0 đơn hàng</div>
                <div class="flex items-center gap-2">
                    <button id="btn-po-prev" onclick="changePoPage(-1)" disabled class="px-3 py-1.5 border border-slate-300 rounded-md bg-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-100">Trước</button>
                    <span id="po-page-display" class="px-2 font-semibold text-slate-800">1</span>
                    <button id="btn-po-next" onclick="changePoPage(1)" disabled class="px-3 py-1.5 border border-slate-300 rounded-md bg-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-100">Sau</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 1: TẠO ĐƠN NHẬP HÀNG MỚI (CREATE PO MODAL) -->
    <div id="modal-create-po" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-3xl rounded-2xl shadow-xl border border-slate-200 overflow-hidden my-8">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Lập Đơn Đặt Hàng Nhà Cung Cấp (PO)</h3>
                    <p class="text-xs text-slate-500">Tạo bản nháp đơn hàng để trình duyệt ngân sách</p>
                </div>
                <button type="button" onclick="closeCreatePoModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="form-create-po" onsubmit="submitCreatePo(event)" class="p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nhà cung cấp <span class="text-rose-500">*</span></label>
                        <select id="po-supplier-id" required class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-indigo-600">
                            <option value="">-- Chọn nhà cung cấp --</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kho nhận hàng <span class="text-rose-500">*</span></label>
                        <select id="po-warehouse-id" required class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-indigo-600">
                            <option value="1">Kho Bếp Ba Đình (Kho chi nhánh)</option>
                            <option value="2">Kho Trung Tâm MESA Hà Nội</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ghi chú đơn đặt hàng</label>
                    <input type="text" id="po-note" placeholder="Ví dụ: Nhập định kỳ đầu tuần hoặc chuẩn bị sự kiện..." class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-indigo-600">
                </div>

                <!-- DANH SÁCH MẶT HÀNG -->
                <div class="pt-2">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-bold text-slate-900 uppercase tracking-wider">Danh sách nguyên vật liệu đặt mua</label>
                        <button type="button" onclick="addPoItemRow()" class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 font-semibold">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            <span>Thêm mặt hàng</span>
                        </button>
                    </div>

                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th class="py-2.5 px-3 text-left font-semibold">Nguyên vật liệu</th>
                                    <th class="py-2.5 px-3 text-right font-semibold w-28">Số lượng</th>
                                    <th class="py-2.5 px-3 text-right font-semibold w-36">Đơn giá dự kiến (VND)</th>
                                    <th class="py-2.5 px-3 text-right font-semibold w-36">Thành tiền</th>
                                    <th class="py-2.5 px-2 text-center w-10"></th>
                                </tr>
                            </thead>
                            <tbody id="po-items-table-body" class="divide-y divide-slate-100">
                                <!-- Dòng mặt hàng sinh động -->
                            </tbody>
                            <tfoot class="bg-slate-50/70 border-t border-slate-200">
                                <tr>
                                    <td colspan="3" class="py-2.5 px-3 font-bold text-right text-slate-700">Tổng giá trị đơn hàng:</td>
                                    <td class="py-2.5 px-3 font-bold text-right text-indigo-700" id="po-create-total-display">0 đ</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeCreatePoModal()" class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50">Hủy</button>
                    <button type="submit" id="btn-submit-create-po" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs">Lưu Bản Nháp</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: XEM CHI TIẾT ĐƠN HÀNG (DETAIL MODAL) -->
    <div id="modal-detail-po" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-3xl rounded-2xl shadow-xl border border-slate-200 overflow-hidden my-8">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <span id="detail-po-no" class="font-bold text-sm text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-md">PO</span>
                    <span id="detail-po-status-badge"></span>
                </div>
                <button type="button" onclick="closeDetailPoModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 bg-slate-50 rounded-xl text-xs">
                    <div>
                        <span class="text-slate-400 block">Nhà cung cấp:</span>
                        <strong id="detail-po-supplier" class="text-slate-800 font-semibold block mt-0.5">-</strong>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Kho nhận:</span>
                        <strong id="detail-po-warehouse" class="text-slate-800 font-semibold block mt-0.5">-</strong>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Người lập:</span>
                        <strong id="detail-po-creator" class="text-slate-800 font-semibold block mt-0.5">-</strong>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Người duyệt:</span>
                        <strong id="detail-po-approver" class="text-slate-800 font-semibold block mt-0.5">-</strong>
                    </div>
                </div>

                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-3 text-left font-semibold">Nguyên vật liệu</th>
                                <th class="py-2.5 px-3 text-right font-semibold">Đơn vị</th>
                                <th class="py-2.5 px-3 text-right font-semibold">Số lượng đặt</th>
                                <th class="py-2.5 px-3 text-right font-semibold">Thực nhận</th>
                                <th class="py-2.5 px-3 text-right font-semibold">Đơn giá</th>
                                <th class="py-2.5 px-3 text-right font-semibold">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody id="detail-po-items-body" class="divide-y divide-slate-100">
                        </tbody>
                        <tfoot class="bg-slate-50/70 border-t border-slate-200">
                            <tr>
                                <td colspan="5" class="py-2.5 px-3 font-bold text-right text-slate-700">Tổng cộng:</td>
                                <td class="py-2.5 px-3 font-bold text-right text-indigo-700" id="detail-po-total">0 đ</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <span id="detail-po-note" class="text-xs text-slate-500 italic"></span>
                    <button type="button" onclick="closeDetailPoModal()" class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 3: XÁC NHẬN NHẬP KHO (RECEIVE GOODS MODAL) -->
    <div id="modal-receive-po" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl border border-slate-200 overflow-hidden my-8">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-emerald-50/50">
                <div>
                    <h3 class="text-sm font-bold text-emerald-950">Kiểm Nhận Hàng & Ghi Sổ Nhập Kho</h3>
                    <p class="text-xs text-emerald-700" id="receive-po-subheading">Xác nhận số lượng thực nhận và ghi tăng tồn kho tức thời</p>
                </div>
                <button type="button" onclick="closeReceivePoModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="form-receive-po" onsubmit="submitReceivePo(event)" class="p-6 space-y-4">
                <input type="hidden" id="receive-po-id">

                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-3 text-left font-semibold">Nguyên vật liệu</th>
                                <th class="py-2.5 px-3 text-right font-semibold">SL Đặt</th>
                                <th class="py-2.5 px-3 text-right font-semibold w-28">SL Thực nhận</th>
                                <th class="py-2.5 px-3 text-right font-semibold w-32">Đơn giá nhập (VND)</th>
                            </tr>
                        </thead>
                        <tbody id="receive-po-items-body" class="divide-y divide-slate-100">
                        </tbody>
                    </table>
                </div>

                <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-[11px] text-amber-800">
                    <p class="font-semibold">Lưu ý nghiệp vụ:</p>
                    <p>Khi xác nhận, hệ thống sẽ tự động cập nhật sổ cái kho (Immutable Ledger) và tính lại đơn giá vốn bình quân gia quyền cho từng nguyên liệu.</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeReceivePoModal()" class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50">Hủy bỏ</button>
                    <button type="submit" id="btn-submit-receive-po" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs">Xác Nhận Nhập Kho</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT APP LOGIC -->
    <script>
        let currentPoPage = 1;
        let activeIngredientsList = [];
        let activeSuppliersList = [];
        let currentViewingPo = null;

        document.addEventListener('DOMContentLoaded', () => {
            loadMasterData();
            loadPurchaseOrders();
        });

        // 1. TẢI MASTER DATA CHO DROPDOWNS
        async function loadMasterData() {
            try {
                // Tải nguyên liệu active
                const resIng = await fetch('/api/v1/inventory/ingredients/active');
                if (resIng.ok) {
                    const data = await resIng.json();
                    activeIngredientsList = data.data || [];
                }

                // Tải nhà cung cấp active
                const resSup = await fetch('/api/v1/inventory/suppliers/active');
                if (resSup.ok) {
                    const data = await resSup.json();
                    activeSuppliersList = data.data || [];
                    populateSupplierDropdown();
                }
            } catch (err) {
                console.error('Lỗi tải master data:', err);
            }
        }

        function populateSupplierDropdown() {
            const select = document.getElementById('po-supplier-id');
            select.innerHTML = '<option value="">-- Chọn nhà cung cấp --</option>';
            activeSuppliersList.forEach(sup => {
                select.innerHTML += `<option value="${sup.id}">${sup.name} (${sup.phone || 'Chưa có SĐT'})</option>`;
            });
        }

        // 2. TẢI DANH SÁCH PURCHASE ORDERS
        async function loadPurchaseOrders(page = 1) {
            currentPoPage = page;
            const search = document.getElementById('filter-po-search').value.trim();
            const status = document.getElementById('filter-po-status').value;

            const params = new URLSearchParams({
                page: currentPoPage,
                per_page: 15
            });
            if (search) params.append('search', search);
            if (status) params.append('status', status);

            const tbody = document.getElementById('po-table-body');
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="py-12 text-center text-slate-400">
                        <div class="inline-flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Đang làm mới dữ liệu đơn đặt hàng...</span>
                        </div>
                    </td>
                </tr>
            `;

            try {
                const res = await fetch(`/api/v1/inventory/purchase-orders?${params.toString()}`);
                if (!res.ok) throw new Error('Không thể tải dữ liệu PO');

                const json = await res.json();
                const orders = json.data || [];
                const meta = json.meta || {};

                renderPoTable(orders);
                updatePagination(meta);
                updateStats(orders);
            } catch (err) {
                console.error(err);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="py-8 text-center text-rose-500 font-medium">
                            Đã xảy ra lỗi khi tải danh sách PO. Vui lòng thử lại!
                        </td>
                    </tr>
                `;
            }
        }

        function renderPoTable(orders) {
            const tbody = document.getElementById('po-table-body');
            if (orders.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            Không tìm thấy đơn nhập hàng nào phù hợp bộ lọc.
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = orders.map(po => {
                const statusBadge = getStatusBadge(po.status, po.status_label);
                const sourceBadge = po.source === 'ai_suggestion'
                    ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">🤖 AI Suggestion</span>`
                    : `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600">Thủ công</span>`;

                const totalFormatted = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(po.total_amount || 0);

                let actionButtons = `
                    <button onclick="viewPoDetail(${po.id})" class="px-2.5 py-1 text-slate-600 hover:text-indigo-600 font-medium hover:bg-slate-100 rounded-md transition-colors" title="Xem chi tiết">
                        Chi tiết
                    </button>
                `;

                if (po.status === 'draft') {
                    actionButtons += `
                        <button onclick="submitPo(${po.id})" class="px-2.5 py-1 text-indigo-600 hover:text-indigo-800 font-semibold hover:bg-indigo-50 rounded-md transition-colors" title="Gửi duyệt">
                            Gửi duyệt
                        </button>
                        <button onclick="cancelPo(${po.id})" class="px-2 py-1 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-md transition-colors" title="Hủy đơn">
                            Hủy
                        </button>
                    `;
                } else if (po.status === 'pending_approval') {
                    actionButtons += `
                        <button onclick="approvePo(${po.id})" class="px-2.5 py-1 text-emerald-600 hover:text-emerald-800 font-semibold hover:bg-emerald-50 rounded-md transition-colors" title="Phê duyệt">
                            Duyệt
                        </button>
                        <button onclick="rejectPo(${po.id})" class="px-2 py-1 text-amber-600 hover:text-amber-800 hover:bg-amber-50 rounded-md transition-colors" title="Từ chối">
                            Trả lại
                        </button>
                    `;
                } else if (po.status === 'approved') {
                    actionButtons += `
                        <button onclick="openReceivePoModal(${po.id})" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-md shadow-2xs transition-colors" title="Nhập hàng về kho">
                            Nhận hàng
                        </button>
                    `;
                }

                return `
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-4 font-bold text-indigo-600">${po.po_no}</td>
                        <td class="py-3 px-4">
                            <div class="font-medium text-slate-800">${po.supplier_name || 'Chưa chỉ định'}</div>
                            <div class="text-[10px] text-slate-400">${po.branch_name || ''}</div>
                        </td>
                        <td class="py-3 px-4 text-slate-600">${po.warehouse_name || 'Kho chính'}</td>
                        <td class="py-3 px-4 text-center">${statusBadge}</td>
                        <td class="py-3 px-4 text-center">${sourceBadge}</td>
                        <td class="py-3 px-4 text-right font-semibold text-slate-900">${totalFormatted}</td>
                        <td class="py-3 px-4">
                            <div class="text-slate-800">${po.creator_name || 'Hệ thống'}</div>
                            <div class="text-[10px] text-slate-400">${formatDate(po.created_at)}</div>
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
                    return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200"><span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>${label}</span>`;
                case 'received':
                    return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>${label}</span>`;
                case 'cancelled':
                    return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium bg-rose-50 text-rose-700 border border-rose-200"><span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>${label}</span>`;
                default:
                    return `<span class="px-2 py-0.5 rounded text-[11px] bg-slate-100 text-slate-600">${label}</span>`;
            }
        }

        function updateStats(orders) {
            document.getElementById('stat-total-po').textContent = orders.length;
            document.getElementById('stat-pending-po').textContent = orders.filter(o => o.status === 'pending_approval').length;
            document.getElementById('stat-approved-po').textContent = orders.filter(o => o.status === 'approved').length;
            document.getElementById('stat-received-po').textContent = orders.filter(o => o.status === 'received').length;
        }

        function updatePagination(meta) {
            const info = document.getElementById('po-pagination-info');
            const btnPrev = document.getElementById('btn-po-prev');
            const btnNext = document.getElementById('btn-po-next');
            const pageDisplay = document.getElementById('po-page-display');

            const total = meta.total || 0;
            const from = meta.from || 0;
            const to = meta.to || 0;
            const lastPage = meta.last_page || 1;

            info.textContent = `Hiển thị ${from} - ${to} / ${total} đơn hàng`;
            pageDisplay.textContent = currentPoPage;
            btnPrev.disabled = currentPoPage <= 1;
            btnNext.disabled = currentPoPage >= lastPage;
        }

        function changePoPage(delta) {
            loadPurchaseOrders(currentPoPage + delta);
        }

        function applyPoFilter() {
            loadPurchaseOrders(1);
        }

        function resetPoFilter() {
            document.getElementById('filter-po-search').value = '';
            document.getElementById('filter-po-status').value = '';
            loadPurchaseOrders(1);
        }

        // 3. THAO TÁC MODAL CREATE PO
        function openCreatePoModal() {
            document.getElementById('form-create-po').reset();
            document.getElementById('po-items-table-body').innerHTML = '';
            addPoItemRow(); // Thêm sẵn 1 dòng
            updateCreateTotalDisplay();
            document.getElementById('modal-create-po').classList.remove('hidden');
        }

        function closeCreatePoModal() {
            document.getElementById('modal-create-po').classList.add('hidden');
        }

        function addPoItemRow() {
            const tbody = document.getElementById('po-items-table-body');
            const rowIndex = tbody.children.length;

            const ingOptions = activeIngredientsList.map(ing => 
                `<option value="${ing.id}">${ing.sku} - ${ing.name} (${ing.base_uom})</option>`
            ).join('');

            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50/50';
            row.innerHTML = `
                <td class="py-2 px-3">
                    <select required class="po-item-ingredient w-full px-2 py-1.5 text-xs rounded border border-slate-300 focus:outline-none focus:border-indigo-600">
                        <option value="">-- Chọn nguyên liệu --</option>
                        ${ingOptions}
                    </select>
                </td>
                <td class="py-2 px-3">
                    <input type="number" step="0.01" min="0.01" required value="1" oninput="calculateRowTotal(this)" class="po-item-qty w-full px-2 py-1.5 text-xs text-right rounded border border-slate-300 focus:outline-none focus:border-indigo-600">
                </td>
                <td class="py-2 px-3">
                    <input type="number" step="1000" min="0" required value="50000" oninput="calculateRowTotal(this)" class="po-item-price w-full px-2 py-1.5 text-xs text-right rounded border border-slate-300 focus:outline-none focus:border-indigo-600">
                </td>
                <td class="py-2 px-3 text-right font-medium text-slate-800 po-item-subtotal">
                    50,000 đ
                </td>
                <td class="py-2 px-2 text-center">
                    <button type="button" onclick="removePoItemRow(this)" class="text-slate-400 hover:text-rose-600 p-1">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                    </button>
                </td>
            `;

            tbody.appendChild(row);
            updateCreateTotalDisplay();
        }

        function removePoItemRow(btn) {
            const tbody = document.getElementById('po-items-table-body');
            if (tbody.children.length <= 1) {
                notifyToast('Đơn đặt hàng phải có ít nhất 1 mặt hàng', 'error');
                return;
            }
            btn.closest('tr').remove();
            updateCreateTotalDisplay();
        }

        function calculateRowTotal(input) {
            const tr = input.closest('tr');
            const qty = parseFloat(tr.querySelector('.po-item-qty').value) || 0;
            const price = parseFloat(tr.querySelector('.po-item-price').value) || 0;
            const subtotal = qty * price;

            tr.querySelector('.po-item-subtotal').textContent = new Intl.NumberFormat('vi-VN').format(subtotal) + ' đ';
            updateCreateTotalDisplay();
        }

        function updateCreateTotalDisplay() {
            let total = 0;
            document.querySelectorAll('#po-items-table-body tr').forEach(tr => {
                const qty = parseFloat(tr.querySelector('.po-item-qty')?.value) || 0;
                const price = parseFloat(tr.querySelector('.po-item-price')?.value) || 0;
                total += (qty * price);
            });
            document.getElementById('po-create-total-display').textContent = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(total);
        }

        async function submitCreatePo(event) {
            event.preventDefault();
            const btn = document.getElementById('btn-submit-create-po');
            btn.disabled = true;
            btn.textContent = 'Đang lưu đơn...';

            const supplierId = document.getElementById('po-supplier-id').value;
            const warehouseId = document.getElementById('po-warehouse-id').value;
            const note = document.getElementById('po-note').value;

            const items = [];
            document.querySelectorAll('#po-items-table-body tr').forEach(tr => {
                const ingId = tr.querySelector('.po-item-ingredient').value;
                const qty = tr.querySelector('.po-item-qty').value;
                const price = tr.querySelector('.po-item-price').value;

                if (ingId) {
                    items.push({
                        ingredient_id: parseInt(ingId),
                        quantity: parseFloat(qty),
                        unit_price: parseFloat(price)
                    });
                }
            });

            if (items.length === 0) {
                notifyToast('Vui lòng chọn ít nhất 1 nguyên vật liệu', 'error');
                btn.disabled = false;
                btn.textContent = 'Lưu Bản Nháp';
                return;
            }

            try {
                const res = await fetch('/api/v1/inventory/purchase-orders', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        branch_id: 1, // Ba Đình
                        warehouse_id: parseInt(warehouseId),
                        supplier_id: parseInt(supplierId),
                        note: note,
                        items: items
                    })
                });

                if (res.ok) {
                    notifyToast('Tạo bản nháp đơn hàng thành công!', 'success');
                    closeCreatePoModal();
                    loadPurchaseOrders(1);
                } else {
                    const data = await res.json();
                    notifyToast(data.message || 'Lỗi kiểm tra dữ liệu đơn hàng', 'error');
                }
            } catch (err) {
                notifyToast('Lỗi kết nối máy chủ', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Lưu Bản Nháp';
            }
        }

        // 4. XEM CHI TIẾT ĐƠN HÀNG
        async function viewPoDetail(id) {
            try {
                const res = await fetch(`/api/v1/inventory/purchase-orders/${id}`);
                if (!res.ok) throw new Error('Không thể tải chi tiết PO');

                const data = await res.json();
                const po = data.data;
                currentViewingPo = po;

                document.getElementById('detail-po-no').textContent = po.po_no;
                document.getElementById('detail-po-status-badge').innerHTML = getStatusBadge(po.status, po.status_label);
                document.getElementById('detail-po-supplier').textContent = po.supplier_name || 'Không rõ';
                document.getElementById('detail-po-warehouse').textContent = po.warehouse_name || 'Kho chính';
                document.getElementById('detail-po-creator').textContent = po.creator_name || 'N/A';
                document.getElementById('detail-po-approver').textContent = po.approver_name || 'Chưa duyệt';
                document.getElementById('detail-po-note').textContent = po.note ? `Ghi chú: ${po.note}` : '';

                const tbody = document.getElementById('detail-po-items-body');
                tbody.innerHTML = (po.items || []).map(item => `
                    <tr>
                        <td class="py-2.5 px-3">
                            <span class="font-bold text-slate-800">${item.ingredient_sku}</span> - ${item.ingredient_name}
                        </td>
                        <td class="py-2.5 px-3 text-right text-slate-500">${item.base_uom}</td>
                        <td class="py-2.5 px-3 text-right font-medium">${item.quantity}</td>
                        <td class="py-2.5 px-3 text-right font-semibold text-emerald-600">${item.received_qty}</td>
                        <td class="py-2.5 px-3 text-right">${new Intl.NumberFormat('vi-VN').format(item.unit_price)} đ</td>
                        <td class="py-2.5 px-3 text-right font-semibold text-slate-900">${new Intl.NumberFormat('vi-VN').format(item.subtotal)} đ</td>
                    </tr>
                `).join('');

                const totalFormatted = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(po.total_amount || 0);
                document.getElementById('detail-po-total').textContent = totalFormatted;

                document.getElementById('modal-detail-po').classList.remove('hidden');
            } catch (err) {
                notifyToast('Lỗi khi tải chi tiết đơn hàng', 'error');
            }
        }

        function closeDetailPoModal() {
            document.getElementById('modal-detail-po').classList.add('hidden');
        }

        // 5. CÁC HÀNH ĐỘNG DUYỆT / HỦY / GỬI DUYỆT
        async function submitPo(id) {
            if (!confirm('Bạn có chắc muốn gửi đơn hàng này đi phê duyệt?')) return;
            try {
                const res = await fetch(`/api/v1/inventory/purchase-orders/${id}/submit`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                if (res.ok) {
                    notifyToast('Đã gửi phê duyệt đơn hàng thành công', 'success');
                    loadPurchaseOrders(currentPoPage);
                } else {
                    const data = await res.json();
                    notifyToast(data.message || 'Không thể gửi duyệt', 'error');
                }
            } catch (e) {
                notifyToast('Lỗi kết nối máy chủ', 'error');
            }
        }

        async function approvePo(id) {
            if (!confirm('Xác nhận phê duyệt đơn đặt hàng này?')) return;
            try {
                const res = await fetch(`/api/v1/inventory/purchase-orders/${id}/approve`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                if (res.ok) {
                    notifyToast('Đã phê duyệt đơn hàng thành công!', 'success');
                    loadPurchaseOrders(currentPoPage);
                } else {
                    const data = await res.json();
                    notifyToast(data.message || 'Không thể phê duyệt', 'error');
                }
            } catch (e) {
                notifyToast('Lỗi kết nối máy chủ', 'error');
            }
        }

        async function rejectPo(id) {
            const note = prompt('Nhập lý do từ chối / yêu cầu chỉnh sửa lại đơn:');
            if (note === null) return;
            try {
                const res = await fetch(`/api/v1/inventory/purchase-orders/${id}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ note: note })
                });
                if (res.ok) {
                    notifyToast('Đã từ chối đơn hàng và chuyển về bản nháp', 'info');
                    loadPurchaseOrders(currentPoPage);
                } else {
                    const data = await res.json();
                    notifyToast(data.message || 'Lỗi từ chối đơn', 'error');
                }
            } catch (e) {
                notifyToast('Lỗi kết nối máy chủ', 'error');
            }
        }

        async function cancelPo(id) {
            if (!confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')) return;
            try {
                const res = await fetch(`/api/v1/inventory/purchase-orders/${id}/cancel`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                if (res.ok) {
                    notifyToast('Đã hủy đơn hàng', 'info');
                    loadPurchaseOrders(currentPoPage);
                } else {
                    const data = await res.json();
                    notifyToast(data.message || 'Không thể hủy đơn', 'error');
                }
            } catch (e) {
                notifyToast('Lỗi kết nối máy chủ', 'error');
            }
        }

        // 6. NHẬN HÀNG VÀO KHO (GOODS RECEIPT)
        async function openReceivePoModal(id) {
            try {
                const res = await fetch(`/api/v1/inventory/purchase-orders/${id}`);
                if (!res.ok) throw new Error('Lỗi tải PO');

                const data = await res.json();
                const po = data.data;

                document.getElementById('receive-po-id').value = po.id;
                document.getElementById('receive-po-subheading').textContent = `Đơn số: ${po.po_no} - Nhà cung cấp: ${po.supplier_name}`;

                const tbody = document.getElementById('receive-po-items-body');
                tbody.innerHTML = (po.items || []).map(item => `
                    <tr data-ingredient-id="${item.ingredient_id}">
                        <td class="py-2.5 px-3">
                            <span class="font-bold text-slate-800">${item.ingredient_sku}</span> - ${item.ingredient_name}
                            <div class="text-[10px] text-slate-400">ĐVT: ${item.base_uom}</div>
                        </td>
                        <td class="py-2.5 px-3 text-right font-medium text-slate-700">${item.quantity}</td>
                        <td class="py-2.5 px-3">
                            <input type="number" step="0.01" min="0" value="${item.quantity}" required class="recv-item-qty w-full px-2 py-1.5 text-xs text-right rounded border border-emerald-300 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </td>
                        <td class="py-2.5 px-3">
                            <input type="number" step="1000" min="0" value="${item.unit_price}" required class="recv-item-price w-full px-2 py-1.5 text-xs text-right rounded border border-slate-300 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </td>
                    </tr>
                `).join('');

                document.getElementById('modal-receive-po').classList.remove('hidden');
            } catch (e) {
                notifyToast('Lỗi khi mở modal nhận hàng', 'error');
            }
        }

        function closeReceivePoModal() {
            document.getElementById('modal-receive-po').classList.add('hidden');
        }

        async function submitReceivePo(event) {
            event.preventDefault();
            const btn = document.getElementById('btn-submit-receive-po');
            btn.disabled = true;
            btn.textContent = 'Đang ghi sổ kho...';

            const poId = document.getElementById('receive-po-id').value;
            const items = [];

            document.querySelectorAll('#receive-po-items-body tr').forEach(tr => {
                const ingId = tr.dataset.ingredientId;
                const qty = tr.querySelector('.recv-item-qty').value;
                const price = tr.querySelector('.recv-item-price').value;

                items.push({
                    ingredient_id: parseInt(ingId),
                    received_qty: parseFloat(qty),
                    unit_price: parseFloat(price)
                });
            });

            try {
                const res = await fetch(`/api/v1/inventory/purchase-orders/${poId}/receive`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ items: items })
                });

                if (res.ok) {
                    notifyToast('Đã nhập hàng vào kho và tính lại giá vốn thành công!', 'success');
                    closeReceivePoModal();
                    loadPurchaseOrders(currentPoPage);
                } else {
                    const data = await res.json();
                    notifyToast(data.message || 'Lỗi khi nhận hàng', 'error');
                }
            } catch (e) {
                notifyToast('Lỗi kết nối máy chủ', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Xác Nhận Nhập Kho';
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
