@extends('layouts.auth')

@section('title', 'Topping của nhóm - MESA Operations')
@section('heading', 'Nhóm topping')

@section('content')
    @php
        $groupEditConfig = [
            'action' => route('menu.modifier-groups.update', $group),
            'method' => 'PUT',
            'title' => 'Chỉnh sửa nhóm topping',
            'submit' => 'Lưu thay đổi',
            'values' => [
                'name' => $group->name,
                'min_select' => $group->min_select,
                'max_select' => $group->max_select,
                'is_active' => $group->is_active,
            ],
        ];
        $modifierCreateConfig = [
            'action' => route('menu.modifiers.store', $group),
            'title' => 'Thêm topping',
            'submit' => 'Lưu topping',
            'values' => ['price' => 0, 'is_active' => true],
        ];
        $groupDeleteConfig = [
            'action' => route('menu.modifier-groups.destroy', $group),
            'method' => 'DELETE',
            'title' => 'Xóa nhóm topping',
            'subtitle' => 'Nhóm sẽ bị gỡ khỏi các món đang dùng.',
            'message' => 'Bạn có chắc chắn muốn xóa nhóm "'.$group->name.'"?',
            'confirm' => 'Xác nhận xóa',
        ];
    @endphp

    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200">
            <div class="min-w-0">
                <a href="{{ route('menu.modifier-groups.index') }}" class="inline-flex items-center gap-1 text-xs font-medium text-slate-500 hover:text-emerald-700 mb-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Danh sách nhóm topping
                </a>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">{{ $group->name }}</h1>
                    <x-menu.badge :tone="$group->is_active ? 'success' : 'neutral'">{{ $group->is_active ? 'Hoạt động' : 'Đang tắt' }}</x-menu.badge>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $group->min_select === 0 ? 'Khách không bắt buộc chọn' : 'Khách bắt buộc chọn tối thiểu '.$group->min_select }}, tối đa {{ $group->max_select }} topping.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-menu.button variant="outline" data-modal-open="group-modal" data-config="{{ json_encode($groupEditConfig, JSON_UNESCAPED_UNICODE) }}">Sửa nhóm</x-menu.button>
                <x-menu.button data-modal-open="modifier-modal" data-config="{{ json_encode($modifierCreateConfig, JSON_UNESCAPED_UNICODE) }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Thêm topping</span>
                </x-menu.button>
            </div>
        </div>

        <!-- DANH SÁCH TOPPING -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-900">Topping trong nhóm</h2>
                <p class="text-[10px] text-slate-400 mt-0.5">Gắn nguyên liệu và định lượng để kho tự trừ khi khách chọn topping. Bỏ trống nếu topping không trừ kho.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider font-semibold">
                            <th class="py-3 px-4">Tên topping</th>
                            <th class="py-3 px-4 text-right">Phụ thu</th>
                            <th class="py-3 px-4">Nguyên liệu trừ kho</th>
                            <th class="py-3 px-4 text-right">Định lượng</th>
                            <th class="py-3 px-4 text-center">Trạng thái</th>
                            <th class="py-3 px-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($group->modifiers as $modifier)
                            @php
                                $editConfig = [
                                    'action' => route('menu.modifiers.update', $modifier),
                                    'method' => 'PUT',
                                    'title' => 'Chỉnh sửa topping',
                                    'submit' => 'Lưu thay đổi',
                                    'values' => [
                                        'name' => $modifier->name,
                                        'price' => (int) $modifier->price,
                                        'ingredient_id' => $modifier->ingredient_id,
                                        'ingredient_qty' => $modifier->ingredient_qty !== null ? (float) $modifier->ingredient_qty : null,
                                        'is_active' => $modifier->is_active,
                                    ],
                                ];
                                $deleteConfig = [
                                    'action' => route('menu.modifiers.destroy', $modifier),
                                    'method' => 'DELETE',
                                    'title' => 'Xóa topping',
                                    'subtitle' => 'Topping đã dùng trong đơn hàng sẽ không xóa được.',
                                    'message' => 'Bạn có chắc chắn muốn xóa topping "'.$modifier->name.'"? Nếu không xóa được, hãy tắt trạng thái hoạt động.',
                                    'confirm' => 'Xác nhận xóa',
                                ];
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4 font-semibold text-slate-900">{{ $modifier->name }}</td>
                                <td class="py-3 px-4 text-right tabular-nums">{{ $modifier->price > 0 ? '+' : '' }}{{ number_format((float) $modifier->price, 0, ',', '.') }}đ</td>
                                <td class="py-3 px-4 text-slate-500">{{ $modifier->ingredient?->name ?? 'Không trừ kho' }}</td>
                                <td class="py-3 px-4 text-right tabular-nums text-slate-600">
                                    @if ($modifier->ingredient_id)
                                        {{ rtrim(rtrim(number_format((float) $modifier->ingredient_qty, 3, ',', '.'), '0'), ',') }} {{ $modifier->ingredient?->base_uom }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <x-menu.badge :tone="$modifier->is_active ? 'success' : 'neutral'">{{ $modifier->is_active ? 'Đang bán' : 'Đang tắt' }}</x-menu.badge>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <button type="button" data-modal-open="modifier-modal" data-config="{{ json_encode($editConfig, JSON_UNESCAPED_UNICODE) }}"
                                        class="px-2 py-1 text-slate-600 hover:text-emerald-700 font-medium hover:underline">Sửa</button>
                                    <button type="button" data-confirm="{{ json_encode($deleteConfig, JSON_UNESCAPED_UNICODE) }}"
                                        class="px-2 py-1 text-slate-500 hover:text-rose-600 font-medium hover:underline">Xóa</button>
                                </td>
                            </tr>
                        @empty
                            <x-menu.empty-row :colspan="6" message="Nhóm chưa có topping nào. Bấm Thêm topping để tạo topping đầu tiên." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- XÓA NHÓM -->
        <div class="flex flex-wrap items-center justify-between gap-4 bg-white rounded-xl border border-rose-200 px-6 py-4">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Xóa nhóm topping</h2>
                <p class="text-[10px] text-slate-500 mt-0.5">Chỉ xóa được khi nhóm không còn topping. Nhóm sẽ bị gỡ khỏi các món đang dùng.</p>
            </div>
            @if ($group->modifiers->isEmpty())
                <button type="button" data-confirm="{{ json_encode($groupDeleteConfig, JSON_UNESCAPED_UNICODE) }}"
                    class="px-4 py-2 text-xs font-semibold rounded-lg border border-rose-300 text-rose-700 bg-white hover:bg-rose-50 transition-colors">Xóa nhóm</button>
            @else
                <span class="text-xs text-slate-400">Hãy xóa hết topping trước.</span>
            @endif
        </div>
    </div>

    @include('menu.modifier-groups._group-modal')

    <!-- MODAL THÊM / SỬA TOPPING -->
    <x-menu.modal id="modifier-modal" title="Thêm topping">
        <x-menu.modal-form modal="modifier-modal" :action="route('menu.modifiers.store', $group)" submit="Lưu topping">
            <x-menu.field label="Tên topping" name="name" modal="modifier-modal" required>
                <x-menu.input name="name" modal="modifier-modal" required maxlength="100" placeholder="VD: Thêm thịt tái" autocomplete="off" />
            </x-menu.field>
            <x-menu.field label="Giá phụ thu" name="price" modal="modifier-modal" required hint="Nhập 0 nếu miễn phí.">
                <x-menu.input name="price" type="number" min="0" step="1" value="0" modal="modifier-modal" suffix="VND" required />
            </x-menu.field>
            <div class="grid grid-cols-3 gap-3">
                <x-menu.field label="Nguyên liệu trừ kho" name="ingredient_id" modal="modifier-modal" class="col-span-2">
                    <x-menu.select name="ingredient_id" modal="modifier-modal">
                        <option value="">-- Không trừ kho --</option>
                        @foreach ($ingredients as $ingredient)
                            <option value="{{ $ingredient->id }}" @selected((string) (old('_modal') === 'modifier-modal' ? old('ingredient_id') : '') === (string) $ingredient->id)>{{ $ingredient->name }} ({{ $ingredient->base_uom }})</option>
                        @endforeach
                    </x-menu.select>
                </x-menu.field>
                <x-menu.field label="Định lượng" name="ingredient_qty" modal="modifier-modal">
                    <x-menu.input name="ingredient_qty" type="number" min="0" step="0.001" modal="modifier-modal" placeholder="VD: 30" />
                </x-menu.field>
            </div>
            <p class="text-[10px] text-slate-400 -mt-1.5">Chọn nguyên liệu thì bắt buộc nhập định lượng (theo đơn vị tính của nguyên liệu).</p>
            <x-menu.checkbox name="is_active" label="Đang bán" :checked="true" modal="modifier-modal" />
        </x-menu.modal-form>
    </x-menu.modal>

    <x-menu.confirm-modal />
    <x-menu.scripts />
    <script>
        // Chỉ cho nhập định lượng khi đã chọn nguyên liệu trừ kho.
        (function () {
            var modal = document.getElementById('modifier-modal');
            var select = modal.querySelector('[name="ingredient_id"]');
            var qty = modal.querySelector('[name="ingredient_qty"]');

            function sync() {
                var has = select.value !== '';
                qty.disabled = !has;
                qty.required = has;
                if (!has) qty.value = '';
            }

            select.addEventListener('change', sync);
            modal.addEventListener('menu:opened', sync);
            sync();
        })();
    </script>
@endsection
