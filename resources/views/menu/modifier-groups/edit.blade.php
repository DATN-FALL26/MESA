@extends('layouts.auth')

@section('title', 'Sửa nhóm topping | MESA')
@section('heading', 'Thực đơn')

@section('content')
    <a href="{{ route('menu.modifier-groups.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800">
        <x-menu.icon name="arrow-left" class="size-4" /> Nhóm topping
    </a>

    <x-menu.page-header :title="$group->name" description="Chỉnh luật chọn của nhóm và quản lý các topping bên trong." />

    <div class="max-w-5xl space-y-5">
        <form method="POST" action="{{ route('menu.modifier-groups.update', $group) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <x-menu.section-card title="Thiết lập nhóm" description="Số lượng khách phải hoặc được chọn trong nhóm này.">
                @include('menu.modifier-groups._fields')
            </x-menu.section-card>
            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">Lưu nhóm</button>
            </div>
        </form>

        <x-menu.section-card id="toppings" title="Topping trong nhóm" description="Gắn nguyên liệu và định lượng để kho tự trừ khi khách chọn topping. Để trống nếu topping không trừ kho.">
            @if ($errors->has('modifier'))
                <p class="mb-4 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $errors->first('modifier') }}</p>
            @endif

            {{-- Thêm topping mới --}}
            <form method="POST" action="{{ route('menu.modifiers.store', $group) }}" class="rounded-lg bg-slate-50 p-4" data-modifier-form>
                @csrf
                <p class="mb-3 text-sm font-medium text-slate-800">Thêm topping</p>
                <div class="grid grid-cols-12 items-start gap-3">
                    <div class="col-span-12 sm:col-span-4">
                        <x-menu.input name="name" id="new-modifier-name" required maxlength="100" placeholder="Ví dụ: Thêm thịt tái" aria-label="Tên topping mới" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <x-menu.input name="price" id="new-modifier-price" type="number" min="0" step="1" value="0" required suffix="đ" aria-label="Giá phụ thu" />
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <x-menu.select name="ingredient_id" id="new-modifier-ingredient" data-ingredient aria-label="Nguyên liệu trừ kho">
                            <option value="">Không trừ kho</option>
                            @foreach ($ingredients as $ingredient)
                                <option value="{{ $ingredient->id }}" @selected((int) old('ingredient_id') === $ingredient->id)>{{ $ingredient->name }} ({{ $ingredient->base_uom }})</option>
                            @endforeach
                        </x-menu.select>
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <x-menu.input name="ingredient_qty" id="new-modifier-qty" type="number" min="0" step="0.001" data-ingredient-qty placeholder="Định lượng" aria-label="Định lượng trừ kho" />
                    </div>
                    <div class="col-span-6 sm:col-span-1">
                        <input type="hidden" name="is_active" value="1">
                        <button type="submit" class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Thêm</button>
                    </div>
                </div>
            </form>

            {{-- Danh sách topping --}}
            <div class="mt-5">
                @if ($group->modifiers->isEmpty())
                    <p class="text-sm text-slate-500">Nhóm chưa có topping nào.</p>
                @else
                    <div class="hidden grid-cols-12 gap-3 px-1 pb-2 text-xs font-medium text-slate-500 sm:grid">
                        <span class="col-span-4">Tên topping</span>
                        <span class="col-span-2">Phụ thu</span>
                        <span class="col-span-3">Nguyên liệu trừ kho</span>
                        <span class="col-span-3">Định lượng, trạng thái</span>
                    </div>
                    <div class="divide-y divide-slate-100 border-t border-slate-100">
                        @foreach ($group->modifiers as $modifier)
                            <div class="flex items-start gap-2 py-3">
                                <form method="POST" action="{{ route('menu.modifiers.update', $modifier) }}" class="grid min-w-0 flex-1 grid-cols-12 items-center gap-3" data-modifier-form>
                                    @csrf
                                    @method('PUT')
                                    <div class="col-span-12 sm:col-span-4">
                                        <x-menu.input name="name" id="modifier-name-{{ $modifier->id }}" :value="$modifier->name" :old="false" required maxlength="100" aria-label="Tên topping" />
                                    </div>
                                    <div class="col-span-6 sm:col-span-2">
                                        <x-menu.input name="price" id="modifier-price-{{ $modifier->id }}" type="number" min="0" step="1" :value="(int) $modifier->price" :old="false" required suffix="đ" aria-label="Giá phụ thu" />
                                    </div>
                                    <div class="col-span-6 sm:col-span-3">
                                        <x-menu.select name="ingredient_id" id="modifier-ingredient-{{ $modifier->id }}" :old="false" data-ingredient aria-label="Nguyên liệu trừ kho">
                                            <option value="">Không trừ kho</option>
                                            @foreach ($ingredients as $ingredient)
                                                <option value="{{ $ingredient->id }}" @selected($modifier->ingredient_id === $ingredient->id)>{{ $ingredient->name }} ({{ $ingredient->base_uom }})</option>
                                            @endforeach
                                        </x-menu.select>
                                    </div>
                                    <div class="col-span-12 flex flex-wrap items-center gap-3 sm:col-span-3">
                                        <div class="w-28">
                                            <x-menu.input name="ingredient_qty" id="modifier-qty-{{ $modifier->id }}" type="number" min="0" step="0.001" :value="$modifier->ingredient_qty !== null ? (float) $modifier->ingredient_qty : null" :old="false" data-ingredient-qty placeholder="Định lượng" aria-label="Định lượng trừ kho" />
                                        </div>
                                        <x-menu.switch name="is_active" :checked="$modifier->is_active" label="Bán" />
                                        <button type="submit" class="rounded-lg bg-slate-800 px-3 py-1.5 text-sm font-semibold text-white hover:bg-slate-700">Lưu</button>
                                    </div>
                                </form>
                                <x-menu.confirm :action="route('menu.modifiers.destroy', $modifier)" title="Xóa topping “{{ $modifier->name }}”?"
                                    message="Nếu topping đã được dùng trong đơn hàng thì sẽ không xóa được, khi đó hãy tắt “Bán”." confirm="Xóa topping"
                                    class="mt-0.5 inline-flex size-8 items-center justify-center rounded-lg text-slate-500 hover:bg-rose-50 hover:text-rose-600" aria-label="Xóa topping {{ $modifier->name }}">
                                    <x-menu.icon name="trash" class="size-4" />
                                </x-menu.confirm>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-menu.section-card>

        <section class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-rose-200 bg-rose-50/40 px-5 py-4">
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Xóa nhóm</h3>
                <p class="mt-0.5 text-xs text-slate-600">Chỉ xóa được khi nhóm không còn topping. Nhóm sẽ bị gỡ khỏi các món đang dùng.</p>
            </div>
            @if ($group->modifiers->isEmpty())
                <x-menu.confirm :action="route('menu.modifier-groups.destroy', $group)" title="Xóa nhóm “{{ $group->name }}”?" message="Nhóm sẽ bị gỡ khỏi các món đang dùng." confirm="Xóa nhóm"
                    class="rounded-lg border border-rose-300 bg-white px-3.5 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Xóa nhóm</x-menu.confirm>
            @else
                <span class="text-xs text-slate-500">Hãy xóa hết topping trước.</span>
            @endif
        </section>
    </div>

    <script>
        // Chỉ cho nhập định lượng khi đã chọn nguyên liệu trừ kho.
        document.querySelectorAll('[data-modifier-form]').forEach(function (form) {
            var select = form.querySelector('[data-ingredient]');
            var qty = form.querySelector('[data-ingredient-qty]');
            if (!select || !qty) return;
            function sync() {
                var hasIngredient = select.value !== '';
                qty.disabled = !hasIngredient;
                qty.required = hasIngredient;
                if (!hasIngredient) qty.value = '';
            }
            select.addEventListener('change', sync);
            sync();
        });
    </script>
@endsection
