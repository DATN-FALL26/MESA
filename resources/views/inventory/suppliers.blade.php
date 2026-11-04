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
                            <th class="py-3 px-4 text-center">Trạng thái</th>
                            <th class="py-3 px-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="suppliers-table-body" class="divide-y divide-slate-100 text-slate-700">
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
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

    <!-- CLIENT SCRIPT GỌI API /api/v1/inventory/suppliers -->
    <script>
        let currentSupplierPage = 1;
        let lastSupplierPage = 1;
        let deleteSupplierTargetId = null;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        document.addEventListener('DOMContentLoaded', () => {
            loadSuppliers();
        });

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
                    <td colspan="6" class="py-8 text-center text-slate-400">
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
                        <td colspan="6" class="py-8 text-center text-rose-500 font-medium">
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
                        <td colspan="6" class="py-8 text-center text-slate-400">
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

                const itemDataEscaped = encodeURIComponent(JSON.stringify(item));

                return `
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="py-3 px-4 font-semibold text-slate-900">${escapeHtml(item.name)}</td>
                        <td class="py-3 px-4 font-medium text-slate-700">${escapeHtml(item.contact || '—')}</td>
                        <td class="py-3 px-4 font-mono text-slate-600">${escapeHtml(item.phone || '—')}</td>
                        <td class="py-3 px-4 text-slate-600 truncate max-w-xs" title="${escapeHtml(item.address || '')}">${escapeHtml(item.address || '—')}</td>
                        <td class="py-3 px-4 text-center">${statusBadge}</td>
                        <td class="py-3 px-4 text-right whitespace-nowrap">
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
