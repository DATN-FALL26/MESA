@extends('layouts.auth')

@section('title', 'Nguyên vật liệu - MESA Operations')
@section('heading', 'Quản lý Nguyên vật liệu')

@section('content')
    <div class="space-y-6" id="ingredients-app">
        <!-- HEADER & TOOLBAR -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200">
            <div>
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Quản lý Nguyên vật liệu</h1>
                <p class="text-xs text-slate-500 mt-1">Danh mục nguyên vật liệu thô và sơ chế dùng cho chế biến & định lượng món</p>
            </div>
            <div class="flex items-center gap-3">
                <button 
                    type="button" 
                    onclick="openCreateModal()" 
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition-colors shadow-2xs"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Thêm nguyên liệu</span>
                </button>
            </div>
        </div>

        <!-- SEARCH & FILTERS -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 bg-white p-4 rounded-xl border border-slate-200">
            <div class="md:col-span-2 relative">
                <input 
                    type="text" 
                    id="filter-search" 
                    placeholder="Tìm theo tên hoặc mã SKU..." 
                    class="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                    onkeyup="if(event.key === 'Enter') applyFilter()"
                >
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>
            <div>
                <select 
                    id="filter-type" 
                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-emerald-600"
                    onchange="applyFilter()"
                >
                    <option value="">-- Phân loại nguyên liệu --</option>
                    <option value="raw">Thô (Raw)</option>
                    <option value="semi_finished">Sơ chế / Nước dùng (Semi-finished)</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <select 
                    id="filter-status" 
                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-emerald-600"
                    onchange="applyFilter()"
                >
                    <option value="">-- Trạng thái --</option>
                    <option value="1">Đang sử dụng (Active)</option>
                    <option value="0">Ngừng dùng (Inactive)</option>
                </select>
                <button 
                    type="button" 
                    onclick="resetFilter()" 
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
                            <th class="py-3 px-4">Mã SKU</th>
                            <th class="py-3 px-4">Tên nguyên vật liệu</th>
                            <th class="py-3 px-4">Phân loại</th>
                            <th class="py-3 px-4">Đơn vị cơ sở (UOM)</th>
                            <th class="py-3 px-4 text-right">Tồn tối thiểu</th>
                            <th class="py-3 px-4 text-center">Trạng thái</th>
                            <th class="py-3 px-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="ingredients-table-body" class="divide-y divide-slate-100 text-slate-700">
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="inline-flex items-center gap-2">
                                    <svg class="w-4 h-4 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>Đang tải dữ liệu nguyên liệu...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div class="flex items-center justify-between px-4 py-3 bg-slate-50 border-t border-slate-200 text-xs text-slate-600">
                <div id="pagination-info">Hiển thị 0 / 0 nguyên liệu</div>
                <div class="flex items-center gap-2" id="pagination-buttons">
                    <button id="btn-prev" onclick="changePage(-1)" disabled class="px-3 py-1.5 border border-slate-300 rounded-md bg-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-100">Trước</button>
                    <span id="current-page-display" class="px-2 font-semibold text-slate-800">1</span>
                    <button id="btn-next" onclick="changePage(1)" disabled class="px-3 py-1.5 border border-slate-300 rounded-md bg-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-100">Sau</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL THÊM / SỬA NGUYÊN LIỆU -->
    <div id="ingredient-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs hidden">
        <div class="bg-white rounded-xl shadow-lg border border-slate-200 w-full max-w-md p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 id="modal-title" class="text-sm font-bold text-slate-900">Thêm nguyên vật liệu mới</h3>
                <button type="button" onclick="closeModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="ingredient-form" onsubmit="saveIngredient(event)" class="space-y-3.5 text-xs">
                <input type="hidden" id="ingredient-id" value="">

                <div>
                    <label class="block font-medium text-slate-700 mb-1">Mã SKU <span class="text-rose-500">*</span></label>
                    <input 
                        type="text" 
                        id="input-sku" 
                        required 
                        placeholder="VD: PHO-TUOI, THIT-BO-TAI" 
                        class="w-full px-3 py-2 rounded-lg border border-slate-300 uppercase font-mono text-xs focus:outline-none focus:border-emerald-600"
                    >
                    <p class="text-[10px] text-slate-400 mt-1">Mã định danh duy nhất (tự động in hoa)</p>
                </div>

                <div>
                    <label class="block font-medium text-slate-700 mb-1">Tên nguyên vật liệu <span class="text-rose-500">*</span></label>
                    <input 
                        type="text" 
                        id="input-name" 
                        required 
                        placeholder="VD: Bánh phở tươi, Thịt bò tái" 
                        class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-emerald-600"
                    >
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Phân loại <span class="text-rose-500">*</span></label>
                        <select id="input-type" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-emerald-600">
                            <option value="raw">Thô (raw)</option>
                            <option value="semi_finished">Sơ chế (semi_finished)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Đơn vị cơ sở (UOM) <span class="text-rose-500">*</span></label>
                        <input 
                            type="text" 
                            id="input-base-uom" 
                            required 
                            placeholder="g, ml, cái, quả" 
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-emerald-600"
                        >
                    </div>
                </div>

                <div>
                    <label class="block font-medium text-slate-700 mb-1">Mức tồn tối thiểu (Min Stock)</label>
                    <input 
                        type="number" 
                        id="input-min-stock" 
                        step="0.001" 
                        min="0" 
                        value="0" 
                        placeholder="0.000" 
                        class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-emerald-600"
                    >
                    <p class="text-[10px] text-slate-400 mt-1">Cảnh báo khi tồn kho tại cửa hàng giảm xuống dưới mức này</p>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" id="input-is-active" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <label for="input-is-active" class="text-slate-700 font-medium">Đang sử dụng trong kho & thực đơn</label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                    <button 
                        type="button" 
                        onclick="closeModal()" 
                        class="px-3.5 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition-colors"
                    >
                        Hủy
                    </button>
                    <button 
                        type="submit" 
                        id="btn-save" 
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors"
                    >
                        Lưu nguyên liệu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL XÁC NHẬN XÓA (SAFE GUARD) -->
    <div id="delete-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs hidden">
        <div class="bg-white rounded-xl shadow-lg border border-slate-200 w-full max-w-sm p-5 space-y-4 text-xs">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-sm">Xóa nguyên vật liệu</h3>
                    <p class="text-slate-500 mt-0.5">Thao tác này kiểm tra ràng buộc Safe Guard trước khi xóa.</p>
                </div>
            </div>

            <p class="text-slate-700">
                Bạn có chắc chắn muốn xóa nguyên vật liệu <strong id="delete-ingredient-name" class="text-slate-900"></strong>?
            </p>

            <div id="delete-error-box" class="p-2.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 hidden"></div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button 
                    type="button" 
                    onclick="closeDeleteModal()" 
                    class="px-3.5 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition-colors"
                >
                    Hủy bỏ
                </button>
                <button 
                    type="button" 
                    id="btn-confirm-delete" 
                    onclick="confirmDelete()" 
                    class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-medium rounded-lg transition-colors"
                >
                    Xác nhận xóa
                </button>
            </div>
        </div>
    </div>

    <!-- CLIENT JAVASCRIPT: GỌI API /api/v1/inventory/ingredients -->
    <script>
        let currentPage = 1;
        let lastPage = 1;
        let deleteTargetId = null;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        document.addEventListener('DOMContentLoaded', () => {
            loadIngredients();
        });

        async function loadIngredients(page = 1) {
            currentPage = page;
            const search = document.getElementById('filter-search').value.trim();
            const type = document.getElementById('filter-type').value;
            const status = document.getElementById('filter-status').value;

            const params = new URLSearchParams({
                page: currentPage,
                per_page: 15
            });

            if (search) params.append('search', search);
            if (type) params.append('ingredient_type', type);
            if (status !== '') params.append('is_active', status);

            const tbody = document.getElementById('ingredients-table-body');
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="py-8 text-center text-slate-400">
                        <span class="inline-flex items-center gap-2">
                            <span class="w-3.5 h-3.5 border-2 border-emerald-600 border-t-transparent rounded-full animate-spin"></span>
                            Đang tải dữ liệu...
                        </span>
                    </td>
                </tr>
            `;

            try {
                const res = await fetch(`/api/v1/inventory/ingredients?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!res.ok) throw new Error('Không thể tải danh sách nguyên vật liệu');

                const json = await res.json();
                renderTable(json.data || []);
                renderPagination(json.meta || {});
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

        function renderTable(items) {
            const tbody = document.getElementById('ingredients-table-body');
            if (items.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">
                            Không tìm thấy nguyên vật liệu nào phù hợp với bộ lọc.
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = items.map(item => {
                const isRaw = item.ingredient_type === 'raw';
                const typeBadge = isRaw 
                    ? `<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">Thô (raw)</span>`
                    : `<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-200">Sơ chế (semi_finished)</span>`;

                const statusBadge = item.is_active
                    ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Hoạt động</span>`
                    : `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-500 border border-slate-200">Ngừng</span>`;

                const itemDataEscaped = encodeURIComponent(JSON.stringify(item));

                return `
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="py-3 px-4 font-mono font-semibold text-slate-800">${escapeHtml(item.sku)}</td>
                        <td class="py-3 px-4 font-medium text-slate-900">${escapeHtml(item.name)}</td>
                        <td class="py-3 px-4">${typeBadge}</td>
                        <td class="py-3 px-4 font-medium text-slate-600">${escapeHtml(item.base_uom)}</td>
                        <td class="py-3 px-4 text-right font-mono text-slate-700">${Number(item.min_stock || 0).toLocaleString('vi-VN')}</td>
                        <td class="py-3 px-4 text-center">${statusBadge}</td>
                        <td class="py-3 px-4 text-right whitespace-nowrap">
                            <button 
                                type="button" 
                                onclick="openEditModal('${itemDataEscaped}')" 
                                class="px-2 py-1 text-slate-600 hover:text-emerald-700 font-medium hover:underline"
                            >
                                Sửa
                            </button>
                            <span class="text-slate-300">|</span>
                            <button 
                                type="button" 
                                onclick="openDeleteModal(${item.id}, '${escapeHtml(item.name)}')" 
                                class="px-2 py-1 text-slate-500 hover:text-rose-600 font-medium hover:underline"
                            >
                                Xóa
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function renderPagination(meta) {
            currentPage = meta.current_page || 1;
            lastPage = meta.last_page || 1;
            const total = meta.total || 0;

            document.getElementById('pagination-info').innerText = `Hiển thị trang ${currentPage} / ${lastPage} (Tổng số ${total} nguyên liệu)`;
            document.getElementById('current-page-display').innerText = currentPage;

            document.getElementById('btn-prev').disabled = currentPage <= 1;
            document.getElementById('btn-next').disabled = currentPage >= lastPage;
        }

        function changePage(delta) {
            const next = currentPage + delta;
            if (next >= 1 && next <= lastPage) {
                loadIngredients(next);
            }
        }

        function applyFilter() {
            loadIngredients(1);
        }

        function resetFilter() {
            document.getElementById('filter-search').value = '';
            document.getElementById('filter-type').value = '';
            document.getElementById('filter-status').value = '';
            loadIngredients(1);
        }

        // --- MODAL THÊM / SỬA ---
        function openCreateModal() {
            document.getElementById('modal-title').innerText = 'Thêm nguyên vật liệu mới';
            document.getElementById('ingredient-id').value = '';
            document.getElementById('input-sku').value = '';
            document.getElementById('input-sku').disabled = false;
            document.getElementById('input-name').value = '';
            document.getElementById('input-type').value = 'raw';
            document.getElementById('input-base-uom').value = '';
            document.getElementById('input-min-stock').value = '0';
            document.getElementById('input-is-active').checked = true;

            document.getElementById('ingredient-modal').classList.remove('hidden');
        }

        function openEditModal(encodedItem) {
            const item = JSON.parse(decodeURIComponent(encodedItem));
            document.getElementById('modal-title').innerText = `Cập nhật nguyên liệu: ${item.name}`;
            document.getElementById('ingredient-id').value = item.id;
            document.getElementById('input-sku').value = item.sku;
            document.getElementById('input-sku').disabled = false;
            document.getElementById('input-name').value = item.name;
            document.getElementById('input-type').value = item.ingredient_type;
            document.getElementById('input-base-uom').value = item.base_uom;
            document.getElementById('input-min-stock').value = item.min_stock || 0;
            document.getElementById('input-is-active').checked = !!item.is_active;

            document.getElementById('ingredient-modal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('ingredient-modal').classList.add('hidden');
        }

        async function saveIngredient(e) {
            e.preventDefault();
            const id = document.getElementById('ingredient-id').value;
            const isEditing = !!id;

            const payload = {
                sku: document.getElementById('input-sku').value.trim().toUpperCase(),
                name: document.getElementById('input-name').value.trim(),
                ingredient_type: document.getElementById('input-type').value,
                base_uom: document.getElementById('input-base-uom').value.trim(),
                min_stock: parseFloat(document.getElementById('input-min-stock').value) || 0,
                is_active: document.getElementById('input-is-active').checked
            };

            const url = isEditing ? `/api/v1/inventory/ingredients/${id}` : '/api/v1/inventory/ingredients';
            const method = isEditing ? 'PUT' : 'POST';

            const btnSave = document.getElementById('btn-save');
            btnSave.disabled = true;
            btnSave.innerText = 'Đang lưu...';

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
                    const message = data.message || Object.values(data.errors || {})[0]?.[0] || 'Lỗi khi lưu nguyên vật liệu';
                    window.showToast(message, 'error');
                    return;
                }

                window.showToast(isEditing ? 'Cập nhật nguyên liệu thành công.' : 'Thêm mới nguyên liệu thành công.', 'success');
                closeModal();
                loadIngredients(currentPage);
            } catch (err) {
                window.showToast(err.message, 'error');
            } finally {
                btnSave.disabled = false;
                btnSave.innerText = 'Lưu nguyên liệu';
            }
        }

        // --- MODAL XÓA SAFE GUARD ---
        function openDeleteModal(id, name) {
            deleteTargetId = id;
            document.getElementById('delete-ingredient-name').innerText = name;
            document.getElementById('delete-error-box').classList.add('hidden');
            document.getElementById('delete-error-box').innerText = '';
            document.getElementById('delete-modal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('delete-modal').classList.add('hidden');
            deleteTargetId = null;
        }

        async function confirmDelete() {
            if (!deleteTargetId) return;

            const btn = document.getElementById('btn-confirm-delete');
            const errBox = document.getElementById('delete-error-box');
            btn.disabled = true;
            btn.innerText = 'Đang xóa...';
            errBox.classList.add('hidden');

            try {
                const res = await fetch(`/api/v1/inventory/ingredients/${deleteTargetId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await res.json();

                if (!res.ok) {
                    // Safe Guard chặn xóa (422) từ IngredientService
                    const msg = data.message || 'Không thể xóa nguyên vật liệu này do ràng buộc dữ liệu an toàn.';
                    errBox.innerText = msg;
                    errBox.classList.remove('hidden');
                    return;
                }

                window.showToast(data.message || 'Đã xóa nguyên vật liệu thành công.', 'success');
                closeDeleteModal();
                loadIngredients(currentPage);
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
