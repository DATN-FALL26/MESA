{{-- Biến thể (size) của món. Hiện trên trang sửa món; thêm/sửa bằng modal giống các trang quản lý khác. --}}
@php
    $basePrice = (int) $menuItem->base_price;
    $variantCreateConfig = [
        'action' => route('menu.variants.store', $menuItem),
        'title' => 'Thêm biến thể (size)',
        'submit' => 'Lưu biến thể',
        'values' => ['price_delta' => 0, 'is_default' => false, 'is_active' => true],
    ];
@endphp

<div id="variants" class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs scroll-mt-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-6 py-4 border-b border-slate-100">
        <div>
            <h2 class="text-sm font-bold text-slate-900">Biến thể (size)</h2>
            <p class="text-[10px] text-slate-400 mt-0.5">Giá bán cuối = giá bán + mức chênh lệch. Ví dụ tô lớn +10.000đ; mức chênh có thể âm.</p>
        </div>
        <x-menu.button data-modal-open="variant-modal" data-config="{{ json_encode($variantCreateConfig, JSON_UNESCAPED_UNICODE) }}">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <span>Thêm biến thể</span>
        </x-menu.button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider font-semibold">
                    <th class="py-3 px-4">Tên biến thể</th>
                    <th class="py-3 px-4 text-right">Chênh lệch</th>
                    <th class="py-3 px-4 text-right">Giá bán cuối</th>
                    <th class="py-3 px-4 text-center">Mặc định</th>
                    <th class="py-3 px-4 text-center">Trạng thái</th>
                    <th class="py-3 px-4 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse ($menuItem->variants as $variant)
                    @php
                        $delta = (int) $variant->price_delta;
                        $editConfig = [
                            'action' => route('menu.variants.update', $variant),
                            'method' => 'PUT',
                            'title' => 'Chỉnh sửa biến thể',
                            'submit' => 'Lưu thay đổi',
                            'values' => [
                                'name' => $variant->name,
                                'price_delta' => $delta,
                                'is_default' => $variant->is_default,
                                'is_active' => $variant->is_active,
                            ],
                        ];
                        $deleteConfig = [
                            'action' => route('menu.variants.destroy', $variant),
                            'method' => 'DELETE',
                            'title' => 'Xóa biến thể',
                            'subtitle' => 'Biến thể đã dùng trong đơn hàng hoặc công thức sẽ không xóa được.',
                            'message' => 'Bạn có chắc chắn muốn xóa biến thể "'.$variant->name.'"? Nếu không xóa được, hãy tắt trạng thái hoạt động.',
                            'confirm' => 'Xác nhận xóa',
                        ];
                    @endphp
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        <td class="py-3 px-4 font-semibold text-slate-900">{{ $variant->name }}</td>
                        <td class="py-3 px-4 text-right tabular-nums text-slate-600">{{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 0, ',', '.') }}đ</td>
                        <td class="py-3 px-4 text-right tabular-nums font-semibold text-slate-900">{{ number_format($basePrice + $delta, 0, ',', '.') }}đ</td>
                        <td class="py-3 px-4 text-center">
                            @if ($variant->is_default)
                                <x-menu.badge tone="info">Mặc định</x-menu.badge>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <x-menu.badge :tone="$variant->is_active ? 'success' : 'neutral'">{{ $variant->is_active ? 'Đang bán' : 'Đang tắt' }}</x-menu.badge>
                        </td>
                        <td class="py-3 px-4 text-right whitespace-nowrap">
                            <button type="button" data-modal-open="variant-modal" data-config="{{ json_encode($editConfig, JSON_UNESCAPED_UNICODE) }}"
                                class="px-2 py-1 text-slate-600 hover:text-emerald-700 font-medium hover:underline">Sửa</button>
                            <button type="button" data-confirm="{{ json_encode($deleteConfig, JSON_UNESCAPED_UNICODE) }}"
                                class="px-2 py-1 text-slate-500 hover:text-rose-600 font-medium hover:underline">Xóa</button>
                        </td>
                    </tr>
                @empty
                    <x-menu.empty-row :colspan="6" message="Món chưa có biến thể. Khách sẽ gọi món với giá bán thông thường." />
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL THÊM / SỬA BIẾN THỂ -->
<x-menu.modal id="variant-modal" title="Thêm biến thể (size)">
    <x-menu.modal-form modal="variant-modal" :action="route('menu.variants.store', $menuItem)" submit="Lưu biến thể" data-base-price="{{ $basePrice }}">
        <div class="flex flex-wrap items-center gap-1.5">
            <span class="text-[10px] text-slate-400">Gợi ý nhanh:</span>
            @foreach ([['Nhỏ', 0], ['Vừa', 0], ['Lớn', 10000], ['Đặc biệt', 20000]] as [$presetName, $presetDelta])
                <button type="button" data-variant-preset data-name="{{ $presetName }}" data-delta="{{ $presetDelta }}"
                    class="px-2 py-1 rounded-md border border-slate-300 bg-white text-[11px] font-medium text-slate-600 hover:border-emerald-500 hover:text-emerald-700">{{ $presetName }}</button>
            @endforeach
        </div>

        <x-menu.field label="Tên biến thể" name="name" modal="variant-modal" required>
            <x-menu.input name="name" modal="variant-modal" required maxlength="50" placeholder="VD: Lớn, Nhỏ, Đặc biệt" autocomplete="off" />
        </x-menu.field>

        <x-menu.field label="Mức chênh lệch so với giá bán" name="price_delta" modal="variant-modal" required hint="Nhập 0 nếu không đổi giá; số âm nếu rẻ hơn.">
            <x-menu.input name="price_delta" type="number" step="1" value="0" modal="variant-modal" suffix="VND" required />
        </x-menu.field>

        <p class="text-slate-500">Giá bán cuối: <strong id="variant-final-price" class="text-slate-900 tabular-nums">{{ number_format($basePrice, 0, ',', '.') }}đ</strong></p>

        <x-menu.checkbox name="is_default" label="Biến thể mặc định" modal="variant-modal" hint="Mỗi món chỉ có một biến thể mặc định; đặt biến thể này sẽ thay biến thể mặc định hiện tại." />
        <x-menu.checkbox name="is_active" label="Đang bán" :checked="true" modal="variant-modal" />
    </x-menu.modal-form>
</x-menu.modal>

<script>
    (function () {
        var modal = document.getElementById('variant-modal');
        var form = modal.querySelector('form');
        var base = parseInt(form.dataset.basePrice, 10) || 0;
        var money = new Intl.NumberFormat('vi-VN');
        var nameInput = form.querySelector('[name="name"]');
        var deltaInput = form.querySelector('[name="price_delta"]');
        var label = document.getElementById('variant-final-price');

        function refresh() {
            label.textContent = money.format(base + (parseInt(deltaInput.value || '0', 10) || 0)) + 'đ';
        }

        deltaInput.addEventListener('input', refresh);
        modal.addEventListener('menu:opened', refresh);
        form.querySelectorAll('[data-variant-preset]').forEach(function (button) {
            button.addEventListener('click', function () {
                nameInput.value = button.dataset.name;
                deltaInput.value = button.dataset.delta;
                refresh();
                nameInput.focus();
            });
        });
    })();
</script>
