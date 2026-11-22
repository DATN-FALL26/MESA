{{-- Modal Xác nhận / Nhập nguyên liệu hàng ngày cho Bếp (KDS Check-in Gatekeeper - Blue Theme) --}}
<div id="kitchen-daily-checkin-modal"
     class="{{ $needsDailyCheckin ? 'fixed' : 'hidden fixed' }} inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-sm transition-opacity"
     data-is-gatekeeper="{{ $needsDailyCheckin ? 'true' : 'false' }}">
    <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-6">
        <div class="relative w-full max-w-5xl transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all border border-slate-200">
            
            {{-- Header (Blue Theme) --}}
            <div class="border-b border-blue-900/40 bg-gradient-to-r from-blue-700 via-blue-800 to-indigo-900 px-6 py-5 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3.5">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 backdrop-blur font-bold text-xl text-sky-200 shadow-inner">
                            📋
                        </div>
                        <div>
                            <h3 id="checkin-modal-title" class="text-xl font-bold tracking-tight text-white">
                                {{ $needsDailyCheckin ? 'Bắt đầu ca: Kiểm tra & Nhập nguyên liệu hôm nay' : 'Nhập bổ sung nguyên liệu trong ca' }}
                            </h3>
                            <p class="text-xs text-blue-100 mt-0.5">
                                Chi nhánh: <span class="font-semibold text-white">{{ $currentBranch?->name ?? 'Mặc định' }}</span>
                                @if ($currentWarehouse)
                                    · Kho: <span class="font-semibold text-white">{{ $currentWarehouse->name }}</span>
                                @endif
                                · Ngày: <span class="font-semibold text-sky-300">{{ now()->format('d/m/Y') }}</span>
                                · <span class="text-sky-200 font-medium">Tự động đồng bộ Đơn nhập kho (PO) vào Admin</span>
                            </p>
                        </div>
                    </div>
                    
                    {{-- Nút đóng chỉ xuất hiện khi mở thủ công (không phải ở chế độ Gatekeeper bắt buộc) --}}
                    <button type="button"
                            id="checkin-modal-close-btn"
                            class="{{ $needsDailyCheckin ? 'hidden' : '' }} rounded-lg p-2 text-white/80 hover:bg-white/10 hover:text-white transition"
                            onclick="closeDailyCheckinModal()">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Body --}}
            <form id="kitchen-checkin-form" action="{{ route('kitchen.daily-checkin.store') }}" method="POST">
                @csrf
                <input type="hidden" name="branch_id" value="{{ $currentBranch?->id }}">
                <input type="hidden" id="checkin-type-input" name="type" value="{{ $needsDailyCheckin ? 'start_shift' : 'in_shift_addition' }}">

                <div class="p-6 space-y-6">
                    @if ($needsDailyCheckin)
                        <div class="rounded-xl bg-blue-50 border border-blue-200 p-4 text-sm text-blue-900 flex items-start gap-3">
                            <svg class="h-5 w-5 text-blue-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <p class="font-bold text-blue-950">Quy định mở ca làm việc:</p>
                                <p class="mt-0.5 text-blue-800">
                                    Vui lòng kiểm tra số lượng và đơn giá thực nhận sáng nay. Hệ thống sẽ lập tức tạo <strong>Đơn nhập hàng (PO)</strong> hoàn tất trong Admin, tăng tồn kho và mở khóa màn hình Bếp để nhận đơn.
                                </p>
                            </div>
                        </div>
                    @endif

                    @if ($dailyIngredients->isEmpty())
                        <div class="text-center py-8">
                            <div class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl mb-3">
                                📦
                            </div>
                            <h4 class="text-base font-semibold text-slate-800">Không có nguyên liệu tươi nhập theo ngày</h4>
                            <p class="text-sm text-slate-500 max-w-md mx-auto mt-1">
                                Chưa có nguyên liệu nào được đánh dấu "Nhập theo ngày" trong danh mục. Bạn có thể mở ca làm việc bình thường.
                            </p>
                        </div>
                    @else
                        <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-sm">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-slate-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold">Nguyên liệu</th>
                                        <th class="px-3 py-3 text-center font-semibold">Tồn hiện tại</th>
                                        <th class="px-3 py-3 text-left font-semibold">Trạng thái nhận</th>
                                        <th class="px-3 py-3 text-center font-semibold w-44">Số lượng nhận</th>
                                        <th class="px-3 py-3 text-center font-semibold w-40">Đơn giá (VND)</th>
                                        <th class="px-4 py-3 text-right font-semibold w-36">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($dailyIngredients as $idx => $ing)
                                        <tr class="hover:bg-blue-50/40 transition-colors" data-ingredient-row="{{ $ing->id }}">
                                            <input type="hidden" name="items[{{ $idx }}][ingredient_id]" value="{{ $ing->id }}">
                                            
                                            {{-- Tên nguyên liệu --}}
                                            <td class="px-4 py-3.5">
                                                <div class="font-bold text-slate-900 text-base">{{ $ing->name }}</div>
                                                <div class="text-xs text-slate-400 font-mono mt-0.5">
                                                    {{ $ing->sku }} · ĐVT: <span class="font-semibold text-blue-700">{{ $ing->base_uom }}</span>
                                                </div>
                                            </td>

                                            {{-- Tồn kho hiện thời --}}
                                            <td class="px-3 py-3.5 text-center">
                                                <span class="inline-block rounded-full px-2.5 py-1 text-xs font-bold tabular-nums {{ (float)$ing->current_stock_qty > 0 ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                                    {{ number_format((float)$ing->current_stock_qty, 2) }} {{ $ing->base_uom }}
                                                </span>
                                            </td>

                                            {{-- Trạng thái hành động --}}
                                            <td class="px-3 py-3.5">
                                                <div class="flex flex-wrap gap-1.5">
                                                    <label class="cursor-pointer">
                                                        <input type="radio"
                                                               name="items[{{ $idx }}][action]"
                                                               value="inward"
                                                               checked
                                                               onchange="onActionChange({{ $ing->id }}, 'inward')"
                                                               class="peer sr-only">
                                                        <span class="inline-flex items-center rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600 peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:text-blue-700 transition">
                                                            ✓ Nhập mới
                                                        </span>
                                                    </label>

                                                    <label class="cursor-pointer">
                                                        <input type="radio"
                                                               name="items[{{ $idx }}][action]"
                                                               value="use_existing"
                                                               onchange="onActionChange({{ $ing->id }}, 'use_existing')"
                                                               class="peer sr-only">
                                                        <span class="inline-flex items-center rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600 peer-checked:border-slate-600 peer-checked:bg-slate-100 peer-checked:text-slate-800 transition">
                                                            Dùng tồn cũ
                                                        </span>
                                                    </label>

                                                    <label class="cursor-pointer">
                                                        <input type="radio"
                                                               name="items[{{ $idx }}][action]"
                                                               value="out_of_stock"
                                                               onchange="onActionChange({{ $ing->id }}, 'out_of_stock')"
                                                               class="peer sr-only">
                                                        <span class="inline-flex items-center rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600 peer-checked:border-rose-600 peer-checked:bg-rose-50 peer-checked:text-rose-700 transition"
                                                              title="Báo hết món dùng nguyên liệu này trên POS">
                                                            Hết hàng
                                                        </span>
                                                    </label>
                                                </div>
                                            </td>

                                            {{-- Số lượng nhận --}}
                                            <td class="px-3 py-3.5 text-center">
                                                <div id="qty-wrapper-{{ $ing->id }}" class="flex items-center justify-center gap-1">
                                                    <button type="button"
                                                            onclick="adjustQty({{ $ing->id }}, -1)"
                                                            class="h-8 w-8 rounded-lg border border-slate-300 bg-slate-50 font-bold text-slate-700 hover:bg-slate-200 active:scale-95 text-base flex items-center justify-center transition">
                                                        -
                                                    </button>
                                                    <input type="number"
                                                           step="0.1"
                                                           min="0"
                                                           id="qty-input-{{ $ing->id }}"
                                                           name="items[{{ $idx }}][quantity]"
                                                           value="0"
                                                           oninput="calcSubtotal({{ $ing->id }})"
                                                           class="w-20 rounded-lg border border-slate-300 px-2 py-1.5 text-center font-bold text-slate-900 focus:border-blue-600 focus:ring-blue-600 text-sm tabular-nums">
                                                    <button type="button"
                                                            onclick="adjustQty({{ $ing->id }}, 1)"
                                                            class="h-8 w-8 rounded-lg border border-slate-300 bg-slate-50 font-bold text-slate-700 hover:bg-slate-200 active:scale-95 text-base flex items-center justify-center transition">
                                                        +
                                                    </button>
                                                </div>
                                                <span id="qty-badge-{{ $ing->id }}" class="hidden text-xs font-medium text-slate-400 py-1.5">
                                                    0 {{ $ing->base_uom }}
                                                </span>
                                            </td>

                                            {{-- Đơn giá nhập (VND) --}}
                                            <td class="px-3 py-3.5 text-center">
                                                <div id="price-wrapper-{{ $ing->id }}">
                                                    <input type="number"
                                                           step="100"
                                                           min="0"
                                                           id="price-input-{{ $ing->id }}"
                                                           name="items[{{ $idx }}][unit_price]"
                                                           value="{{ (float)$ing->avg_cost > 0 ? (int)$ing->avg_cost : 0 }}"
                                                           oninput="calcSubtotal({{ $ing->id }})"
                                                           class="w-28 rounded-lg border border-slate-300 px-2.5 py-1.5 text-right font-medium text-slate-900 focus:border-blue-600 focus:ring-blue-600 text-sm tabular-nums">
                                                </div>
                                                <span id="price-badge-{{ $ing->id }}" class="hidden text-xs text-slate-400">
                                                    -
                                                </span>
                                            </td>

                                            {{-- Thành tiền dự kiến --}}
                                            <td class="px-4 py-3.5 text-right">
                                                <span id="subtotal-{{ $ing->id }}" class="font-bold text-slate-800 tabular-nums">
                                                    0 đ
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-blue-50/50 border-t border-slate-200 font-semibold text-slate-800">
                                    <tr>
                                        <td colspan="5" class="px-4 py-3 text-right text-xs uppercase tracking-wider text-slate-600">
                                            Tổng giá trị đơn nhập hàng dự kiến:
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span id="grand-total" class="text-base font-extrabold text-blue-700 tabular-nums">0 đ</span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- Ghi chú ca --}}
                        <div>
                            <label for="checkin-note" class="block text-xs font-semibold text-slate-600 mb-1">
                                Ghi chú của Bếp (tùy chọn)
                            </label>
                            <input type="text"
                                   id="checkin-note"
                                   name="note"
                                   placeholder="Ví dụ: NCC Ba Vì giao thịt tươi lúc 6h30, bánh phở tươi dẻo..."
                                   class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-blue-600 focus:ring-blue-600">
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="border-t border-slate-200 bg-slate-50 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="text-xs text-slate-500 flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                        Đơn nhập sẽ đồng bộ ngay lập tức vào menu <strong>Kho & Nhập hàng (Admin)</strong>.
                    </div>

                    <div class="flex items-center gap-3">
                        @if (! $needsDailyCheckin)
                            <button type="button"
                                    onclick="closeDailyCheckinModal()"
                                    class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                                Đóng
                            </button>
                        @endif

                        <button type="submit"
                                id="submit-checkin-btn"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-7 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-600/30 hover:bg-blue-700 active:scale-95 transition">
                            <span id="submit-checkin-text">
                                {{ $needsDailyCheckin ? 'Xác nhận & Mở ca bếp' : 'Lưu đơn nhập bổ sung' }}
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function adjustQty(ingredientId, delta) {
        const input = document.getElementById(`qty-input-${ingredientId}`);
        if (! input) return;
        const current = parseFloat(input.value) || 0;
        const next = Math.max(0, parseFloat((current + delta).toFixed(2)));
        input.value = next;
        calcSubtotal(ingredientId);
    }

    function calcSubtotal(ingredientId) {
        const qtyInput = document.getElementById(`qty-input-${ingredientId}`);
        const priceInput = document.getElementById(`price-input-${ingredientId}`);
        const subtotalEl = document.getElementById(`subtotal-${ingredientId}`);
        if (! qtyInput || ! priceInput || ! subtotalEl) return;

        const qty = parseFloat(qtyInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        const subtotal = Math.round(qty * price);

        subtotalEl.textContent = new Intl.NumberFormat('vi-VN').format(subtotal) + ' đ';
        calcGrandTotal();
    }

    function calcGrandTotal() {
        let grandTotal = 0;
        document.querySelectorAll('[data-ingredient-row]').forEach((row) => {
            const ingId = row.dataset.ingredientRow;
            const qtyInput = document.getElementById(`qty-input-${ingId}`);
            const priceInput = document.getElementById(`price-input-${ingId}`);
            const actionInward = row.querySelector('input[type="radio"][value="inward"]');
            if (actionInward && actionInward.checked) {
                const qty = parseFloat(qtyInput?.value) || 0;
                const price = parseFloat(priceInput?.value) || 0;
                grandTotal += (qty * price);
            }
        });

        const grandTotalEl = document.getElementById('grand-total');
        if (grandTotalEl) {
            grandTotalEl.textContent = new Intl.NumberFormat('vi-VN').format(Math.round(grandTotal)) + ' đ';
        }
    }

    function onActionChange(ingredientId, action) {
        const qtyInput = document.getElementById(`qty-input-${ingredientId}`);
        const qtyWrapper = document.getElementById(`qty-wrapper-${ingredientId}`);
        const qtyBadge = document.getElementById(`qty-badge-${ingredientId}`);
        const priceWrapper = document.getElementById(`price-wrapper-${ingredientId}`);
        const priceBadge = document.getElementById(`price-badge-${ingredientId}`);
        const subtotalEl = document.getElementById(`subtotal-${ingredientId}`);

        if (! qtyInput || ! qtyWrapper || ! qtyBadge) return;

        if (action === 'inward') {
            qtyWrapper.classList.remove('hidden');
            qtyBadge.classList.add('hidden');
            priceWrapper?.classList.remove('hidden');
            priceBadge?.classList.add('hidden');
            if (parseFloat(qtyInput.value) === 0) {
                qtyInput.value = 1;
            }
        } else {
            qtyWrapper.classList.add('hidden');
            qtyBadge.classList.remove('hidden');
            priceWrapper?.classList.add('hidden');
            priceBadge?.classList.remove('hidden');
            qtyBadge.textContent = action === 'use_existing' ? 'Dùng tồn cũ' : 'Hết hàng (Tắt món)';
            qtyInput.value = 0;
            if (subtotalEl) {
                subtotalEl.textContent = '0 đ';
            }
        }

        calcSubtotal(ingredientId);
    }

    function openDailyCheckinModal(type = 'in_shift_addition') {
        const modal = document.getElementById('kitchen-daily-checkin-modal');
        const title = document.getElementById('checkin-modal-title');
        const typeInput = document.getElementById('checkin-type-input');
        const closeBtn = document.getElementById('checkin-modal-close-btn');
        const submitText = document.getElementById('submit-checkin-text');

        if (! modal) return;

        if (type === 'in_shift_addition') {
            title.textContent = 'Nhập bổ sung nguyên liệu trong ca';
            typeInput.value = 'in_shift_addition';
            closeBtn?.classList.remove('hidden');
            if (submitText) submitText.textContent = 'Lưu đơn nhập bổ sung';
        } else {
            title.textContent = 'Bắt đầu ca: Kiểm tra & Nhập nguyên liệu hôm nay';
            typeInput.value = 'start_shift';
            closeBtn?.classList.add('hidden');
            if (submitText) submitText.textContent = 'Xác nhận & Mở ca bếp';
        }

        modal.classList.remove('hidden');
        modal.classList.add('fixed');
        calcGrandTotal();
    }

    function closeDailyCheckinModal() {
        const modal = document.getElementById('kitchen-daily-checkin-modal');
        if (! modal) return;
        if (modal.dataset.isGatekeeper === 'true') {
            return;
        }
        modal.classList.add('hidden');
        modal.classList.remove('fixed');
    }

    document.addEventListener('DOMContentLoaded', () => {
        const checkinForm = document.getElementById('kitchen-checkin-form');
        const submitBtn = document.getElementById('submit-checkin-btn');

        if (checkinForm) {
            checkinForm.addEventListener('submit', () => {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-75');
                    submitBtn.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Đang đồng bộ PO & mở ca...
                    `;
                }
            });
        }

        // Khởi tạo thành tiền ban đầu
        document.querySelectorAll('[data-ingredient-row]').forEach((row) => {
            const ingId = row.dataset.ingredientRow;
            calcSubtotal(ingId);
        });
    });
</script>
