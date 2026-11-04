@php
    $menuItems = \App\Models\MenuItem::query()
        ->with(['variants' => fn($q) => $q->where('is_active', true)])
        ->where('is_active', true)
        ->orderBy('name')
        ->get();
@endphp

@extends('layouts.auth')

@section('title', 'Định lượng BOM - MESA Operations')
@section('heading', 'Công thức Định lượng (BOM)')

@section('content')
    <div class="space-y-6" id="recipes-app">
        <!-- HEADER & TOOLBAR -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200">
            <div>
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Quản lý Định lượng Món ăn (BOM)</h1>
                <p class="text-xs text-slate-500 mt-1">Cấu hình tỷ lệ tiêu hao nguyên vật liệu theo từng món và kích cỡ, quản lý phiên bản (Versioning)</p>
            </div>
            <div class="flex items-center gap-3">
                <button 
                    type="button" 
                    onclick="openCreateVersionModal()" 
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition-colors shadow-2xs"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Tạo phiên bản công thức mới</span>
                </button>
            </div>
        </div>

        <!-- SELECTOR BAR -->
        <div class="bg-white p-4 rounded-xl border border-slate-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">1. Chọn món ăn trong thực đơn <span class="text-rose-500">*</span></label>
                    <select 
                        id="select-menu-item" 
                        class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-emerald-600 font-medium"
                        onchange="onMenuItemChanged()"
                    >
                        <option value="">-- Chọn món ăn --</option>
                        @foreach($menuItems as $mItem)
                            <option value="{{ $mItem->id }}" data-variants="{{ json_encode($mItem->variants) }}">
                                {{ $mItem->name }} ({{ $mItem->sku }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">2. Chọn kích cỡ / Biến thể (Variant)</label>
                    <select 
                        id="select-variant" 
                        class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-emerald-600"
                        onchange="loadRecipesForSelected()"
                    >
                        <option value="">-- Công thức mặc định (Tất cả cỡ) --</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button 
                        type="button" 
                        onclick="loadRecipesForSelected()" 
                        class="w-full py-2 px-4 bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium rounded-lg transition-colors flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <span>Tra cứu công thức</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- MAIN CONTENT AREA: 2 COLUMNS (RECIPE DETAILS & EXPLOSION SIMULATOR) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- LEFT 2 COLUMNS: VERSION LIST & INGREDIENT ITEMS -->
            <div class="lg:col-span-2 space-y-4">
                <!-- VERSIONS LIST BAR -->
                <div class="bg-white p-4 rounded-xl border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-3">
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Lịch sử các phiên bản (Versions)</span>
                        <span id="version-count" class="text-xs text-slate-500">0 phiên bản</span>
                    </div>
                    <div id="versions-container" class="flex flex-wrap gap-2">
                        <p class="text-xs text-slate-400 py-1">Vui lòng chọn món ăn phía trên để tải phiên bản công thức.</p>
                    </div>
                </div>

                <!-- RECIPE ITEMS TABLE -->
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
                    <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider" id="active-recipe-title">Chi tiết Định lượng Nguyên liệu</h2>
                            <p class="text-[11px] text-slate-500 mt-0.5" id="active-recipe-subtitle">Định lượng tiêu hao chuẩn cho 1 phần ăn</p>
                        </div>
                        <div id="recipe-action-buttons" class="flex items-center gap-2 hidden">
                            <button 
                                type="button" 
                                id="btn-activate-recipe" 
                                onclick="activateCurrentRecipe()" 
                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium rounded-lg transition-colors"
                            >
                                Kích hoạt bản này
                            </button>
                            <button 
                                type="button" 
                                id="btn-clone-recipe" 
                                onclick="cloneCurrentRecipe()" 
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium rounded-lg transition-colors border border-slate-300"
                            >
                                Nhân bản làm bản nháp
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-white border-b border-slate-200 text-slate-500 uppercase font-semibold text-[11px]">
                                    <th class="py-2.5 px-4">Mã SKU</th>
                                    <th class="py-2.5 px-4">Tên nguyên vật liệu</th>
                                    <th class="py-2.5 px-4">Phân loại</th>
                                    <th class="py-2.5 px-4 text-right">Định lượng / Suất</th>
                                    <th class="py-2.5 px-4">Đơn vị (UOM)</th>
                                </tr>
                            </thead>
                            <tbody id="recipe-items-tbody" class="divide-y divide-slate-100 text-slate-700">
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-slate-400">
                                        Chưa có công thức nào được chọn để hiển thị.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- RIGHT 1 COLUMN: EXPLOSION SIMULATOR WIDGET -->
            <div class="space-y-4">
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                        <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
                            ⚡
                        </div>
                        <div>
                            <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Mô phỏng Bóc tách Định lượng</h2>
                            <p class="text-[10px] text-slate-500">Tính tổng nguyên liệu tiêu hao từ API</p>
                        </div>
                    </div>

                    <div class="space-y-3.5 pt-3 text-xs">
                        <div>
                            <label class="block font-medium text-slate-700 mb-1">Số lượng suất ăn (Multiplier)</label>
                            <div class="flex items-center gap-2">
                                <input 
                                    type="number" 
                                    id="sim-multiplier" 
                                    min="1" 
                                    value="50" 
                                    class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono font-semibold text-slate-800 focus:outline-none focus:border-emerald-600"
                                >
                                <span class="text-slate-500 shrink-0 font-medium">phần/tô</span>
                            </div>
                        </div>

                        <button 
                            type="button" 
                            id="btn-run-explosion" 
                            onclick="runExplosionSimulator()" 
                            class="w-full py-2 px-3 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors flex items-center justify-center gap-2"
                        >
                            <span>Tính toán bóc tách</span>
                        </button>

                        <div id="explosion-results" class="pt-3 border-t border-slate-100 hidden">
                            <p class="font-semibold text-slate-800 text-xs mb-2">Tổng nguyên liệu cần xuất:</p>
                            <div id="explosion-list" class="space-y-2 max-h-60 overflow-y-auto pr-1"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL TẠO PHIÊN BẢN CÔNG THỨC MỚI (BOM BUILDER) -->
    <div id="create-version-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs hidden p-4">
        <div class="bg-white rounded-xl shadow-lg border border-slate-200 w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden text-xs">
            <div class="flex items-center justify-between p-4 border-b border-slate-200">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Thiết lập Phiên bản Công thức mới</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Hệ thống sẽ tự động tạo version tiếp theo trong chuỗi phiên bản</p>
                </div>
                <button type="button" onclick="closeCreateVersionModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="bom-builder-form" onsubmit="submitNewRecipeVersion(event)" class="flex-1 overflow-y-auto p-4 space-y-4">
                <div class="grid grid-cols-2 gap-3 bg-slate-50 p-3 rounded-lg border border-slate-200">
                    <div>
                        <span class="block text-slate-500 font-medium">Món ăn:</span>
                        <strong id="modal-dish-name" class="text-slate-900 font-semibold">—</strong>
                    </div>
                    <div>
                        <span class="block text-slate-500 font-medium">Kích cỡ / Biến thể:</span>
                        <strong id="modal-variant-name" class="text-slate-900 font-semibold">Mặc định (Tất cả cỡ)</strong>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="font-bold text-slate-800">Danh sách Nguyên vật liệu định lượng <span class="text-rose-500">*</span></label>
                        <button 
                            type="button" 
                            onclick="addBomRow()" 
                            class="px-2.5 py-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-md font-semibold border border-emerald-200 transition-colors flex items-center gap-1"
                        >
                            <span>+ Thêm nguyên liệu</span>
                        </button>
                    </div>

                    <div class="border border-slate-200 rounded-lg overflow-hidden">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th class="py-2 px-3">Nguyên vật liệu</th>
                                    <th class="py-2 px-3 w-36">Định lượng</th>
                                    <th class="py-2 px-3 w-28">Đơn vị (UOM)</th>
                                    <th class="py-2 px-3 w-16 text-center">Xóa</th>
                                </tr>
                            </thead>
                            <tbody id="bom-builder-rows" class="divide-y divide-slate-100">
                                <!-- Dynamic Rows -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" id="modal-activate-now" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <label for="modal-activate-now" class="text-slate-700 font-medium">Kích hoạt phiên bản này ngay (Tự động chuyển các bản cũ thành Draft)</label>
                </div>
            </form>

            <div class="flex items-center justify-end gap-2 p-4 bg-slate-50 border-t border-slate-200">
                <button 
                    type="button" 
                    onclick="closeCreateVersionModal()" 
                    class="px-3.5 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-white transition-colors"
                >
                    Hủy bỏ
                </button>
                <button 
                    type="submit" 
                    form="bom-builder-form" 
                    id="btn-submit-bom" 
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors"
                >
                    Tạo phiên bản
                </button>
            </div>
        </div>
    </div>

    <!-- CLIENT JAVASCRIPT GỌI API /api/v1/inventory/recipes -->
    <script>
        let currentLoadedRecipes = [];
        let selectedRecipeId = null;
        let activeIngredientsList = [];

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        document.addEventListener('DOMContentLoaded', () => {
            loadActiveIngredients();
            // Nếu có món đầu tiên trong dropdown thì tự động chọn
            const mSelect = document.getElementById('select-menu-item');
            if (mSelect.options.length > 1) {
                mSelect.selectedIndex = 1;
                onMenuItemChanged();
            }
        });

        async function loadActiveIngredients() {
            try {
                const res = await fetch('/api/v1/inventory/ingredients/active', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    const json = await res.json();
                    activeIngredientsList = json.data || [];
                }
            } catch (err) {
                console.error('Không thể nạp danh sách nguyên liệu:', err);
            }
        }

        function onMenuItemChanged() {
            const mSelect = document.getElementById('select-menu-item');
            const vSelect = document.getElementById('select-variant');
            vSelect.innerHTML = '<option value="">-- Công thức mặc định (Tất cả cỡ) --</option>';

            const selectedOption = mSelect.options[mSelect.selectedIndex];
            if (!selectedOption || !selectedOption.value) {
                loadRecipesForSelected();
                return;
            }

            try {
                const variants = JSON.parse(selectedOption.getAttribute('data-variants') || '[]');
                variants.forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v.id;
                    opt.textContent = `${v.name} (+${Number(v.price_delta || 0).toLocaleString('vi-VN')} đ)`;
                    vSelect.appendChild(opt);
                });
            } catch (e) {
                console.error(e);
            }

            loadRecipesForSelected();
        }

        async function loadRecipesForSelected() {
            const menuItemId = document.getElementById('select-menu-item').value;
            const variantId = document.getElementById('select-variant').value;

            const vContainer = document.getElementById('versions-container');
            const vCount = document.getElementById('version-count');
            const tbody = document.getElementById('recipe-items-tbody');

            if (!menuItemId) {
                vContainer.innerHTML = '<p class="text-xs text-slate-400 py-1">Vui lòng chọn món ăn phía trên để tải công thức.</p>';
                vCount.innerText = '0 phiên bản';
                tbody.innerHTML = '<tr><td colspan="5" class="py-10 text-center text-slate-400">Chưa có món ăn nào được chọn.</td></tr>';
                document.getElementById('recipe-action-buttons').classList.add('hidden');
                return;
            }

            const params = new URLSearchParams({ menu_item_id: menuItemId });
            if (variantId) params.append('variant_id', variantId);

            vContainer.innerHTML = '<span class="text-xs text-slate-400">Đang tải các phiên bản...</span>';

            try {
                const res = await fetch(`/api/v1/inventory/recipes?${params.toString()}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!res.ok) throw new Error('Không thể tải công thức định lượng');

                const json = await res.json();
                currentLoadedRecipes = json.data || [];
                renderVersionsList();
            } catch (err) {
                vContainer.innerHTML = `<span class="text-xs text-rose-500">${err.message}</span>`;
            }
        }

        function renderVersionsList() {
            const vContainer = document.getElementById('versions-container');
            const vCount = document.getElementById('version-count');

            vCount.innerText = `${currentLoadedRecipes.length} phiên bản`;

            if (currentLoadedRecipes.length === 0) {
                vContainer.innerHTML = '<p class="text-xs text-slate-400 py-1">Chưa có phiên bản công thức nào cho món ăn/biến thể này. Hãy bấm "Tạo phiên bản mới".</p>';
                renderRecipeDetails(null);
                return;
            }

            // Mặc định chọn phiên bản đang Active, nếu không có thì lấy bản đầu tiên
            const activeRecipe = currentLoadedRecipes.find(r => r.is_active) || currentLoadedRecipes[0];
            selectedRecipeId = activeRecipe.id;

            vContainer.innerHTML = currentLoadedRecipes.map(r => {
                const isSelected = r.id === selectedRecipeId;
                const activeBadge = r.is_active 
                    ? '<span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>' 
                    : '<span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>';

                const borderClass = isSelected ? 'border-emerald-600 bg-emerald-50/50 text-emerald-950 font-semibold' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50';

                return `
                    <button 
                        type="button" 
                        onclick="selectVersion(${r.id})" 
                        class="px-3 py-1.5 rounded-lg border text-xs flex items-center gap-2 transition-all ${borderClass}"
                    >
                        ${activeBadge}
                        <span>Phiên bản ${r.version}</span>
                        ${r.is_active ? '<span class="text-[10px] text-emerald-600 uppercase font-bold">(Đang dùng)</span>' : '<span class="text-[10px] text-slate-400">(Draft)</span>'}
                    </button>
                `;
            }).join('');

            renderRecipeDetails(activeRecipe);
        }

        async function selectVersion(id) {
            selectedRecipeId = id;
            // Highlight nút version
            renderVersionsListButtonsOnly();

            try {
                const res = await fetch(`/api/v1/inventory/recipes/${id}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    const json = await res.json();
                    renderRecipeDetails(json.data);
                }
            } catch (e) {
                console.error(e);
            }
        }

        function renderVersionsListButtonsOnly() {
            const buttons = document.querySelectorAll('#versions-container button');
            // Cập nhật giao diện nút đã chọn
            currentLoadedRecipes.forEach((r, idx) => {
                if (buttons[idx]) {
                    if (r.id === selectedRecipeId) {
                        buttons[idx].className = 'px-3 py-1.5 rounded-lg border text-xs flex items-center gap-2 transition-all border-emerald-600 bg-emerald-50/50 text-emerald-950 font-semibold';
                    } else {
                        buttons[idx].className = 'px-3 py-1.5 rounded-lg border text-xs flex items-center gap-2 transition-all border-slate-200 bg-white text-slate-700 hover:bg-slate-50';
                    }
                }
            });
        }

        function renderRecipeDetails(recipe) {
            const tbody = document.getElementById('recipe-items-tbody');
            const title = document.getElementById('active-recipe-title');
            const subtitle = document.getElementById('active-recipe-subtitle');
            const actionButtons = document.getElementById('recipe-action-buttons');
            const btnActivate = document.getElementById('btn-activate-recipe');

            if (!recipe) {
                title.innerText = 'Chi tiết Định lượng';
                subtitle.innerText = 'Chưa có công thức';
                actionButtons.classList.add('hidden');
                tbody.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-slate-400">Không có dữ liệu nguyên liệu.</td></tr>';
                return;
            }

            actionButtons.classList.remove('hidden');
            if (recipe.is_active) {
                btnActivate.classList.add('hidden');
            } else {
                btnActivate.classList.remove('hidden');
            }

            title.innerText = `Định lượng Phiên bản v${recipe.version} ${recipe.is_active ? '• Đang áp dụng' : '• Bản nháp'}`;
            subtitle.innerText = `Cập nhật lúc: ${recipe.created_at || 'Mới khởi tạo'}`;

            const items = recipe.items || [];
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-slate-400">Phiên bản này chưa có dòng nguyên liệu nào.</td></tr>';
                return;
            }

            tbody.innerHTML = items.map(it => {
                const ing = it.ingredient || {};
                const isRaw = ing.ingredient_type === 'raw';
                const typeLabel = isRaw ? 'Thô' : 'Sơ chế';

                return `
                    <tr class="hover:bg-slate-50/60">
                        <td class="py-2.5 px-4 font-mono font-semibold text-slate-800">${escapeHtml(ing.sku || '—')}</td>
                        <td class="py-2.5 px-4 font-medium text-slate-900">${escapeHtml(ing.name || '—')}</td>
                        <td class="py-2.5 px-4 text-slate-500">${typeLabel}</td>
                        <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-900">${Number(it.quantity || 0).toLocaleString('vi-VN', { maximumFractionDigits: 3 })}</td>
                        <td class="py-2.5 px-4 font-medium text-slate-600">${escapeHtml(ing.base_uom || '—')}</td>
                    </tr>
                `;
            }).join('');
        }

        async function activateCurrentRecipe() {
            if (!selectedRecipeId) return;
            try {
                const res = await fetch(`/api/v1/inventory/recipes/${selectedRecipeId}/activate`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const json = await res.json();
                if (res.ok) {
                    window.showToast('Đã kích hoạt phiên bản công thức thành công.', 'success');
                    loadRecipesForSelected();
                } else {
                    window.showToast(json.message || 'Lỗi kích hoạt công thức', 'error');
                }
            } catch (e) {
                window.showToast(e.message, 'error');
            }
        }

        async function cloneCurrentRecipe() {
            if (!selectedRecipeId) return;
            try {
                const res = await fetch(`/api/v1/inventory/recipes/${selectedRecipeId}/clone`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const json = await res.json();
                if (res.ok) {
                    window.showToast('Đã nhân bản công thức thành bản nháp mới thành công.', 'success');
                    loadRecipesForSelected();
                } else {
                    window.showToast(json.message || 'Lỗi nhân bản công thức', 'error');
                }
            } catch (e) {
                window.showToast(e.message, 'error');
            }
        }

        // --- EXPLOSION SIMULATOR ---
        async function runExplosionSimulator() {
            const menuItemId = document.getElementById('select-menu-item').value;
            const variantId = document.getElementById('select-variant').value;
            const multiplier = parseInt(document.getElementById('sim-multiplier').value) || 1;

            if (!menuItemId) {
                window.showToast('Vui lòng chọn món ăn trước khi mô phỏng bóc tách.', 'warning');
                return;
            }

            const btn = document.getElementById('btn-run-explosion');
            btn.disabled = true;
            btn.innerText = 'Đang tính toán...';

            try {
                const res = await fetch('/api/v1/inventory/recipes/explode', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        menu_item_id: parseInt(menuItemId),
                        variant_id: variantId ? parseInt(variantId) : null,
                        multiplier: multiplier
                    })
                });

                const json = await res.json();
                const list = document.getElementById('explosion-list');
                const resultBox = document.getElementById('explosion-results');

                if (!res.ok) {
                    window.showToast(json.message || 'Lỗi bóc tách định lượng', 'error');
                    return;
                }

                const data = json.data || [];
                if (data.length === 0) {
                    list.innerHTML = '<p class="text-xs text-slate-400">Không có nguyên liệu tiêu hao.</p>';
                } else {
                    list.innerHTML = data.map(item => `
                        <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200">
                            <div>
                                <span class="font-medium text-slate-900 block">${escapeHtml(item.name)}</span>
                                <span class="font-mono text-[10px] text-slate-500">${escapeHtml(item.sku)}</span>
                            </div>
                            <div class="text-right">
                                <span class="font-mono font-bold text-emerald-700 text-sm">${Number(item.quantity || 0).toLocaleString('vi-VN', { maximumFractionDigits: 3 })}</span>
                                <span class="text-[10px] text-slate-500 block">${escapeHtml(item.base_uom)}</span>
                            </div>
                        </div>
                    `).join('');
                }

                resultBox.classList.remove('hidden');
            } catch (e) {
                window.showToast(e.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Tính toán bóc tách';
            }
        }

        // --- MODAL BOM BUILDER ---
        function openCreateVersionModal() {
            const mSelect = document.getElementById('select-menu-item');
            const vSelect = document.getElementById('select-variant');

            if (!mSelect.value) {
                window.showToast('Vui lòng chọn món ăn trước khi tạo công thức.', 'warning');
                return;
            }

            document.getElementById('modal-dish-name').innerText = mSelect.options[mSelect.selectedIndex].text;
            document.getElementById('modal-variant-name').innerText = vSelect.value 
                ? vSelect.options[vSelect.selectedIndex].text 
                : 'Mặc định (Tất cả cỡ)';

            const rowsContainer = document.getElementById('bom-builder-rows');
            rowsContainer.innerHTML = '';

            // Nếu đang có một phiên bản được chọn, sao chép các dòng nguyên liệu qua để người dùng sửa nhanh
            const currentRecipe = currentLoadedRecipes.find(r => r.id === selectedRecipeId);
            if (currentRecipe && currentRecipe.items && currentRecipe.items.length > 0) {
                currentRecipe.items.forEach(it => {
                    addBomRow(it.ingredient_id, it.quantity);
                });
            } else {
                addBomRow();
            }

            document.getElementById('create-version-modal').classList.remove('hidden');
        }

        function closeCreateVersionModal() {
            document.getElementById('create-version-modal').classList.add('hidden');
        }

        function addBomRow(selectedIngId = null, initialQty = '') {
            const tbody = document.getElementById('bom-builder-rows');
            const rowId = 'row-' + Date.now() + '-' + Math.random().toString(36).substring(2, 7);

            const tr = document.createElement('tr');
            tr.id = rowId;
            tr.className = 'hover:bg-slate-50/50';

            const ingOptions = activeIngredientsList.map(ing => `
                <option value="${ing.id}" data-uom="${escapeHtml(ing.base_uom)}" ${ing.id === selectedIngId ? 'selected' : ''}>
                    ${escapeHtml(ing.name)} (${escapeHtml(ing.sku)})
                </option>
            `).join('');

            tr.innerHTML = `
                <td class="py-2 px-3">
                    <select class="bom-ing-select w-full px-2 py-1.5 border border-slate-300 rounded text-xs focus:outline-none focus:border-emerald-600" onchange="updateRowUom('${rowId}')">
                        <option value="">-- Chọn nguyên liệu --</option>
                        ${ingOptions}
                    </select>
                </td>
                <td class="py-2 px-3">
                    <input type="number" step="0.001" min="0.001" required value="${initialQty}" placeholder="0.000" class="bom-qty-input w-full px-2 py-1.5 border border-slate-300 rounded text-xs font-mono font-medium focus:outline-none focus:border-emerald-600">
                </td>
                <td class="py-2 px-3 text-slate-500 font-medium bom-uom-label">
                    —
                </td>
                <td class="py-2 px-3 text-center">
                    <button type="button" onclick="document.getElementById('${rowId}').remove()" class="text-rose-500 hover:text-rose-700 font-bold text-sm">
                        &times;
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
            updateRowUom(rowId);
        }

        function updateRowUom(rowId) {
            const tr = document.getElementById(rowId);
            if (!tr) return;
            const select = tr.querySelector('.bom-ing-select');
            const uomLabel = tr.querySelector('.bom-uom-label');
            const selectedOpt = select.options[select.selectedIndex];
            uomLabel.innerText = selectedOpt ? (selectedOpt.getAttribute('data-uom') || '—') : '—';
        }

        async function submitNewRecipeVersion(e) {
            e.preventDefault();
            const menuItemId = document.getElementById('select-menu-item').value;
            const variantId = document.getElementById('select-variant').value;
            const activateNow = document.getElementById('modal-activate-now').checked;

            const rows = document.querySelectorAll('#bom-builder-rows tr');
            const items = [];

            for (const r of rows) {
                const ingId = r.querySelector('.bom-ing-select')?.value;
                const qty = r.querySelector('.bom-qty-input')?.value;
                if (!ingId || !qty) {
                    window.showToast('Vui lòng chọn đầy đủ nguyên liệu và nhập số lượng định lượng.', 'warning');
                    return;
                }
                items.push({
                    ingredient_id: parseInt(ingId),
                    quantity: parseFloat(qty)
                });
            }

            if (items.length === 0) {
                window.showToast('Công thức phải có ít nhất 1 nguyên vật liệu.', 'warning');
                return;
            }

            const payload = {
                menu_item_id: parseInt(menuItemId),
                variant_id: variantId ? parseInt(variantId) : null,
                items: items,
                activate_immediately: activateNow
            };

            const btn = document.getElementById('btn-submit-bom');
            btn.disabled = true;
            btn.innerText = 'Đang lưu...';

            try {
                const res = await fetch('/api/v1/inventory/recipes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                });

                const json = await res.json();

                if (!res.ok) {
                    window.showToast(json.message || 'Lỗi khi tạo công thức mới', 'error');
                    return;
                }

                window.showToast('Đã tạo phiên bản công thức mới thành công.', 'success');
                closeCreateVersionModal();
                loadRecipesForSelected();
            } catch (err) {
                window.showToast(err.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Tạo phiên bản';
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
