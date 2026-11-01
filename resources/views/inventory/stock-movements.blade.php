@php
    $warehouses = \App\Models\Warehouse::query()->where('is_active', true)->orderBy('name')->get();
    $ingredients = \App\Models\Ingredient::query()->where('is_active', true)->orderBy('name')->get();
@endphp

@extends('layouts.auth')

@section('title', 'Thẻ kho & Sổ cái - MESA Operations')
@section('heading', 'Thẻ kho & Sổ cái Biến động')

@section('content')
    <div class="space-y-6" id="stock-movements-app">
        <!-- HEADER & TOOLBAR -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200">
            <div>
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Thẻ kho & Sổ cái Biến động (Stock Ledger)</h1>
                <p class="text-xs text-slate-500 mt-1">Tra cứu biến động nhập/xuất/hủy bất biến (Immutable Ledger) và mức tồn kho, giá vốn bình quân</p>
            </div>
        </div>

        <!-- FILTER & SELECTION BAR -->
        <div class="bg-white p-4 rounded-xl border border-slate-200">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">1. Chọn Kho hàng <span class="text-rose-500">*</span></label>
                    <select id="select-warehouse" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-emerald-600 font-medium" onchange="onLedgerFilterChange()">
                        <option value="">-- Chọn kho hàng --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->type->value ?? 'branch' }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">2. Chọn Nguyên vật liệu <span class="text-rose-500">*</span></label>
                    <select id="select-ingredient" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-emerald-600 font-medium" onchange="onLedgerFilterChange()">
                        <option value="">-- Chọn nguyên liệu --</option>
                        @foreach($ingredients as $ing)
                            <option value="{{ $ing->id }}" data-uom="{{ $ing->base_uom }}" data-min="{{ $ing->min_stock }}">
                                {{ $ing->name }} ({{ $ing->sku }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">3. Từ ngày</label>
                    <input type="date" id="filter-from-date" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-emerald-600" onchange="onLedgerFilterChange()">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">4. Đến ngày</label>
                    <input type="date" id="filter-to-date" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-emerald-600" onchange="onLedgerFilterChange()">
                </div>
            </div>
        </div>

        <!-- CURRENT STOCK SUMMARY STATS -->
        <div id="stock-summary-card" class="grid grid-cols-1 sm:grid-cols-3 gap-4 hidden">
            <div class="bg-white p-4 rounded-xl border border-slate-200">
                <span class="text-xs font-medium text-slate-500">Số lượng tồn kho tức thời:</span>
                <div class="mt-2 flex items-baseline gap-2">
                    <span id="stat-quantity" class="text-2xl font-black text-slate-900 font-mono">0.000</span>
                    <span id="stat-uom" class="text-xs text-slate-500 font-medium">g</span>
                </div>
                <div id="stat-warning" class="mt-2 text-[11px] text-amber-700 font-medium hidden">
                    ⚠️ Tồn kho đang ở dưới mức tối thiểu (<span id="stat-min-stock"></span>)
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl border border-slate-200">
                <span class="text-xs font-medium text-slate-500">Giá vốn bình quân gia quyền:</span>
                <div class="mt-2 flex items-baseline gap-2">
                    <span id="stat-avg-cost" class="text-2xl font-black text-emerald-700 font-mono">0</span>
                    <span class="text-xs text-slate-500 font-medium">VNĐ / đơn vị</span>
                </div>
                <p class="mt-2 text-[11px] text-slate-400">Tự động tái tính toán sau mỗi lần nhập mua</p>
            </div>

            <div class="bg-white p-4 rounded-xl border border-slate-200 flex flex-col justify-between">
                <div>
                    <span class="text-xs font-medium text-slate-500">Giá trị tồn kho ước tính:</span>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span id="stat-total-value" class="text-2xl font-black text-slate-900 font-mono">0</span>
                        <span class="text-xs text-slate-500 font-medium">VNĐ</span>
                    </div>
                </div>
                <p class="text-[10px] text-slate-400">= Số lượng tồn &times; Giá vốn bình quân</p>
            </div>
        </div>

        <!-- LEDGER MOVEMENTS TABLE -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Thẻ kho chi tiết (Sổ cái phát sinh)</h2>
                    <p class="text-[11px] text-slate-500 mt-0.5">Dữ liệu bất biến (Append-Only) ghi nhận mọi giao dịch nhập, xuất, hủy</p>
                </div>
                <span id="movements-count" class="text-xs font-medium text-slate-600">0 biến động</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-white border-b border-slate-200 text-slate-500 uppercase tracking-wider font-semibold text-[11px]">
                            <th class="py-3 px-4">Thời gian</th>
                            <th class="py-3 px-4">Loại biến động</th>
                            <th class="py-3 px-4 text-right">Số lượng</th>
                            <th class="py-3 px-4 text-right">Đơn giá vốn</th>
                            <th class="py-3 px-4">Tham chiếu chứng từ</th>
                            <th class="py-3 px-4">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody id="movements-tbody" class="divide-y divide-slate-100 text-slate-700">
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                Vui lòng chọn Kho hàng và Nguyên vật liệu phía trên để hiển thị Thẻ kho.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- CLIENT JAVASCRIPT GỌI API /api/v1/inventory/warehouses/{w}/ingredients/{i}/... -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const wSelect = document.getElementById('select-warehouse');
            const iSelect = document.getElementById('select-ingredient');
            if (wSelect.options.length > 1) wSelect.selectedIndex = 1;
            if (iSelect.options.length > 1) iSelect.selectedIndex = 1;
            onLedgerFilterChange();
        });

        async function onLedgerFilterChange() {
            const warehouseId = document.getElementById('select-warehouse').value;
            const ingredientId = document.getElementById('select-ingredient').value;
            const fromDate = document.getElementById('filter-from-date').value;
            const toDate = document.getElementById('filter-to-date').value;

            const summaryCard = document.getElementById('stock-summary-card');
            const tbody = document.getElementById('movements-tbody');
            const countLabel = document.getElementById('movements-count');

            if (!warehouseId || !ingredientId) {
                summaryCard.classList.add('hidden');
                tbody.innerHTML = '<tr><td colspan="6" class="py-12 text-center text-slate-400">Vui lòng chọn Kho hàng và Nguyên vật liệu phía trên để hiển thị Thẻ kho.</td></tr>';
                countLabel.innerText = '0 biến động';
                return;
            }

            // 1. Tải Mức tồn tức thời (Stock Level)
            loadStockLevel(warehouseId, ingredientId);

            // 2. Tải Sổ cái Thẻ kho (Ledger Card)
            loadLedgerCard(warehouseId, ingredientId, fromDate, toDate);
        }

        async function loadStockLevel(warehouseId, ingredientId) {
            const summaryCard = document.getElementById('stock-summary-card');
            try {
                const res = await fetch(`/api/v1/inventory/warehouses/${warehouseId}/ingredients/${ingredientId}/stock`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (res.ok) {
                    const json = await res.json();
                    const data = json.data || {};
                    const ingSelect = document.getElementById('select-ingredient');
                    const selectedOpt = ingSelect.options[ingSelect.selectedIndex];
                    const uom = selectedOpt ? selectedOpt.getAttribute('data-uom') : 'g';
                    const minStock = parseFloat(selectedOpt ? selectedOpt.getAttribute('data-min') : 0) || 0;

                    const qty = parseFloat(data.quantity || 0);
                    const avgCost = parseFloat(data.avg_cost || 0);
                    const totalVal = qty * avgCost;

                    document.getElementById('stat-quantity').innerText = qty.toLocaleString('vi-VN', { maximumFractionDigits: 3 });
                    document.getElementById('stat-uom').innerText = uom;
                    document.getElementById('stat-avg-cost').innerText = Math.round(avgCost).toLocaleString('vi-VN');
                    document.getElementById('stat-total-value').innerText = Math.round(totalVal).toLocaleString('vi-VN');

                    const warn = document.getElementById('stat-warning');
                    if (qty <= minStock && minStock > 0) {
                        document.getElementById('stat-min-stock').innerText = minStock.toLocaleString('vi-VN') + ' ' + uom;
                        warn.classList.remove('hidden');
                    } else {
                        warn.classList.add('hidden');
                    }

                    summaryCard.classList.remove('hidden');
                } else {
                    summaryCard.classList.add('hidden');
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function loadLedgerCard(warehouseId, ingredientId, fromDate, toDate) {
            const tbody = document.getElementById('movements-tbody');
            const countLabel = document.getElementById('movements-count');

            const params = new URLSearchParams();
            if (fromDate) params.append('from_date', fromDate);
            if (toDate) params.append('to_date', toDate);

            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="py-8 text-center text-slate-400">
                        <span class="inline-flex items-center gap-2">
                            <span class="w-3.5 h-3.5 border-2 border-emerald-600 border-t-transparent rounded-full animate-spin"></span>
                            Đang tải thẻ kho phát sinh...
                        </span>
                    </td>
                </tr>
            `;

            try {
                const url = `/api/v1/inventory/warehouses/${warehouseId}/ingredients/${ingredientId}/ledger?${params.toString()}`;
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!res.ok) throw new Error('Không thể tải dữ liệu thẻ kho');

                const json = await res.json();
                const items = json.data || [];

                countLabel.innerText = `${items.length} biến động`;

                if (items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="py-8 text-center text-slate-400">Chưa có phát sinh biến động kho nào cho nguyên liệu này trong kỳ tra cứu.</td></tr>';
                    return;
                }

                tbody.innerHTML = items.map(m => {
                    const qty = parseFloat(m.quantity || 0);
                    const isPositive = qty > 0;
                    const qtyClass = isPositive ? 'text-emerald-700 font-bold' : 'text-slate-800 font-bold';
                    const qtyDisplay = (isPositive ? '+' : '') + qty.toLocaleString('vi-VN', { maximumFractionDigits: 3 });

                    // Map movement type sang tiếng Việt
                    const typeMap = {
                        'purchase_in': { label: 'Nhập mua hàng', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
                        'sale_out': { label: 'Xuất bán món', class: 'bg-slate-100 text-slate-700 border-slate-200' },
                        'sale_return': { label: 'Khách trả hàng', class: 'bg-blue-50 text-blue-700 border-blue-200' },
                        'adjust': { label: 'Điều chỉnh kiểm kê', class: 'bg-amber-50 text-amber-700 border-amber-200' },
                        'waste': { label: 'Xuất hủy / Hao hụt', class: 'bg-rose-50 text-rose-700 border-rose-200' }
                    };

                    const typeInfo = typeMap[m.movement_type] || { label: m.movement_type, class: 'bg-slate-100 text-slate-600 border-slate-200' };

                    return `
                        <tr class="hover:bg-slate-50/60">
                            <td class="py-3 px-4 font-mono text-slate-600">${escapeHtml(m.created_at || '—')}</td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium border ${typeInfo.class}">
                                    ${typeInfo.label}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right font-mono ${qtyClass}">${qtyDisplay}</td>
                            <td class="py-3 px-4 text-right font-mono text-slate-700">${m.unit_cost ? Number(m.unit_cost).toLocaleString('vi-VN') + ' đ' : '—'}</td>
                            <td class="py-3 px-4 font-mono text-slate-600">
                                ${m.ref_type ? `<span class="bg-slate-100 px-1.5 py-0.5 rounded text-[10px]">${escapeHtml(m.ref_type)} #${m.ref_id || ''}</span>` : '—'}
                            </td>
                            <td class="py-3 px-4 text-slate-500 text-[11px] truncate max-w-xs" title="${escapeHtml(m.note || '')}">${escapeHtml(m.note || '—')}</td>
                        </tr>
                    `;
                }).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="6" class="py-8 text-center text-rose-500 font-medium">${err.message}</td></tr>`;
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
