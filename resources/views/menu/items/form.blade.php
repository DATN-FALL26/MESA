@extends('layouts.auth')

@php
    $isEditing = $menuItem->exists;
    $viewExists = fn (string $name): bool => \Illuminate\Support\Facades\View::exists($name);
    $stationByType = [
        \App\Enums\ItemType::DISH->value => \App\Enums\StationCode::PHO->value,
        \App\Enums\ItemType::DRINK->value => \App\Enums\StationCode::DRINK->value,
        \App\Enums\ItemType::SIDE->value => \App\Enums\StationCode::SIDE->value,
    ];
@endphp

@section('title', ($isEditing ? 'Sửa món' : 'Thêm món').' - MESA Operations')
@section('heading', $isEditing ? 'Chỉnh sửa món ăn' : 'Thêm món ăn')

@section('content')
    <div class="space-y-6 max-w-5xl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200">
            <div class="min-w-0">
                <a href="{{ route('menu.items.index') }}" class="inline-flex items-center gap-1 text-xs font-medium text-slate-500 hover:text-emerald-700 mb-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Danh sách món ăn
                </a>
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">{{ $isEditing ? $menuItem->name : 'Thêm món mới' }}</h1>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $isEditing ? 'Mã món '.$menuItem->sku.'. Cập nhật thông tin, biến thể (size) và topping của món.' : 'Nhập thông tin món. Biến thể (size) và topping thêm được sau khi tạo món.' }}
                </p>
            </div>
            @if ($isEditing)
                <x-menu.badge :tone="$menuItem->is_active ? 'success' : 'neutral'">{{ $menuItem->is_active ? 'Đang kinh doanh' : 'Tạm ngừng' }}</x-menu.badge>
            @endif
        </div>

        <form method="POST" action="{{ $isEditing ? route('menu.items.update', $menuItem) : route('menu.items.store') }}" enctype="multipart/form-data"
            class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs text-xs" id="item-form">
            @csrf
            @if ($isEditing)
                @method('PUT')
            @endif

            <!-- THÔNG TIN CHUNG -->
            <div class="p-6 space-y-4">
                <h2 class="text-sm font-bold text-slate-900">Thông tin chung</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-menu.field label="Tên món" name="name" required>
                        <x-menu.input name="name" id="name" :value="$menuItem->name" required maxlength="150" placeholder="VD: Phở tái nạm" autocomplete="off" />
                    </x-menu.field>
                    <x-menu.field label="Mã món (SKU)" name="sku" required hint="Chữ in hoa, số và . _ - ; không trùng món nào, kể cả món đã xóa.">
                        <x-menu.input name="sku" id="sku" :value="$menuItem->sku" required maxlength="40" placeholder="PHO-TAI-NAM" autocomplete="off"
                            data-auto="{{ $isEditing ? '0' : '1' }}" class="uppercase font-mono" />
                    </x-menu.field>
                    <x-menu.field label="Danh mục" name="category_id" required>
                        <x-menu.select name="category_id" id="category_id" required>
                            <option value="">-- Chọn danh mục --</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((int) old('category_id', $menuItem->category_id) === $category->id)>
                                    {{ $category->parent_id ? '— ' : '' }}{{ $category->name }}{{ $category->is_active ? '' : ' (đang tắt)' }}
                                </option>
                            @endforeach
                        </x-menu.select>
                    </x-menu.field>
                    <x-menu.field label="Mô tả ngắn" name="description" hint="Hiển thị cho khách khi gọi món (không bắt buộc).">
                        <x-menu.input name="description" id="description" :value="$menuItem->description" maxlength="255" autocomplete="off" />
                    </x-menu.field>
                </div>
            </div>

            <!-- PHÂN LOẠI & CHẾ BIẾN -->
            <div class="p-6 space-y-4 border-t border-slate-100">
                <h2 class="text-sm font-bold text-slate-900">Phân loại & chế biến</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-menu.field label="Loại món" name="item_type" required>
                        <x-menu.select name="item_type" id="item_type" required>
                            @foreach ($itemTypes as $type)
                                <option value="{{ $type->value }}" @selected(old('item_type', $menuItem->item_type?->value ?? 'dish') === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </x-menu.select>
                    </x-menu.field>
                    <x-menu.field label="Quầy chế biến" name="station_code" required hint="Phiếu bếp (KDS) chia theo quầy này.">
                        <x-menu.select name="station_code" id="station_code" required>
                            @foreach ($stations as $station)
                                <option value="{{ $station->value }}" @selected(old('station_code', $menuItem->station_code?->value ?? 'PHO') === $station->value)>{{ $station->label() }}</option>
                            @endforeach
                        </x-menu.select>
                    </x-menu.field>
                    <x-menu.field label="Thời gian chế biến" name="prep_time_seconds" required hint="Bếp dùng để canh giờ nấu lệch nhau cho ra món cùng lúc.">
                        <x-menu.input name="prep_time_seconds" id="prep_time_seconds" type="number" min="0" max="86400" step="1" :value="$isEditing ? $menuItem->prep_time_seconds : 180" suffix="giây" required />
                    </x-menu.field>
                </div>
            </div>

            <!-- GIÁ & THUẾ -->
            <div class="p-6 space-y-4 border-t border-slate-100">
                <h2 class="text-sm font-bold text-slate-900">Giá & thuế</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-menu.field label="Giá bán gốc" name="base_price" required hint="Số nguyên VND. Mỗi chi nhánh có thể đặt giá riêng.">
                        <x-menu.input name="base_price" id="base_price" type="number" min="0" step="1" :value="$isEditing ? (int) $menuItem->base_price : null" suffix="VND" required placeholder="55000" />
                    </x-menu.field>
                    <x-menu.field label="Thuế VAT" name="tax_rate" required>
                        <x-menu.input name="tax_rate" id="tax_rate" type="number" min="0" max="100" step="0.01" :value="$isEditing ? $menuItem->tax_rate : '8.00'" suffix="%" required />
                    </x-menu.field>
                    <div class="flex items-end pb-1">
                        <p class="text-slate-500">Giá hiển thị: <strong id="price-preview" class="text-slate-900 tabular-nums">0đ</strong></p>
                    </div>
                </div>
            </div>

            <!-- HÌNH ẢNH -->
            <div class="p-6 space-y-4 border-t border-slate-100">
                <h2 class="text-sm font-bold text-slate-900">Hình ảnh</h2>
                <div class="flex flex-wrap items-center gap-4">
                    <img id="image-preview" src="{{ $menuItem->image_path ? asset('storage/'.$menuItem->image_path) : '' }}" alt=""
                        class="w-20 h-20 rounded-lg object-cover border border-slate-200 {{ $menuItem->image_path ? '' : 'hidden' }}">
                    <div class="flex-1 min-w-60">
                        <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp"
                            class="block w-full text-xs text-slate-600 file:mr-3 file:px-3 file:py-2 file:rounded-lg file:border file:border-slate-300 file:bg-white file:text-xs file:font-medium file:text-slate-700 hover:file:bg-slate-50">
                        <p class="text-[10px] text-slate-400 mt-1">jpg, png hoặc webp, tối đa 2MB.</p>
                        @error('image')<p class="text-[10px] text-rose-600 font-medium mt-1">{{ $message }}</p>@enderror
                        @if ($menuItem->image_path)
                            <label class="inline-flex items-center gap-2 mt-2 text-slate-600">
                                <input type="checkbox" name="remove_image" value="1" class="rounded border-slate-300 accent-rose-600"> Xóa ảnh hiện tại
                            </label>
                        @endif
                    </div>
                </div>
            </div>

            <!-- TRẠNG THÁI & CHI NHÁNH -->
            <div class="p-6 space-y-4 border-t border-slate-100">
                <h2 class="text-sm font-bold text-slate-900">Trạng thái</h2>
                <x-menu.checkbox name="is_active" label="Đang kinh doanh" :checked="$isEditing ? $menuItem->is_active : true"
                    hint="Tắt để tạm ngừng bán món ở mọi chi nhánh mà không cần xóa." />

                @unless ($isEditing)
                    @php($selectedBranches = array_map('intval', (array) old('branch_ids', $branches->pluck('id')->all())))
                    <div class="pt-2">
                        <div class="flex items-center justify-between mb-1">
                            <p class="font-medium text-slate-700">Áp dụng cho chi nhánh</p>
                            @if ($branches->count() > 1)
                                <button type="button" id="toggle-branches" class="text-emerald-700 hover:underline font-medium">Bỏ chọn tất cả</button>
                            @endif
                        </div>
                        <p class="text-[10px] text-slate-400 mb-2">Khách đặt qua QR chỉ thấy món đã áp dụng và đang còn hàng tại chi nhánh. Có thể đổi lại ở mục Còn hàng & giá chi nhánh.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @forelse ($branches as $branch)
                                <label class="flex items-center gap-2.5 rounded-lg border border-slate-200 px-3 py-2 text-slate-700 hover:bg-slate-50 cursor-pointer">
                                    <input type="checkbox" name="branch_ids[]" value="{{ $branch->id }}" class="rounded border-slate-300 accent-emerald-600" @checked(in_array($branch->id, $selectedBranches, true))>
                                    {{ $branch->name }}
                                </label>
                            @empty
                                <p class="text-slate-500">Bạn chưa được gán chi nhánh nào nên món sẽ chưa hiện ở chi nhánh nào.</p>
                            @endforelse
                        </div>
                    </div>
                @endunless
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4 bg-slate-50 border-t border-slate-200">
                <a href="{{ route('menu.items.index') }}" class="px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 transition-colors">Hủy</a>
                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors">{{ $isEditing ? 'Lưu thay đổi' : 'Tạo món' }}</button>
            </div>
        </form>

        @if ($isEditing)
            @includeIf('menu.items._variants')
            @includeIf('menu.items._modifier-groups')
        @endif
    </div>

    <x-menu.confirm-modal />
    <x-menu.scripts />
    <script>
        (function () {
            var byId = function (id) { return document.getElementById(id); };
            var money = new Intl.NumberFormat('vi-VN');
            var stationByType = @json($stationByType);
            var name = byId('name'), sku = byId('sku'), price = byId('base_price');
            var skuAuto = sku && sku.dataset.auto === '1' && sku.value.trim() === '';
            var stationTouched = false;

            function toSlug(text) {
                return text.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/đ/g, 'd').replace(/Đ/g, 'D')
                    .toUpperCase().replace(/[^A-Z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 40);
            }

            function refreshPrice() {
                var amount = parseInt(price.value || '0', 10) || 0;
                byId('price-preview').textContent = money.format(amount) + 'đ';
            }

            // Tự gợi ý mã SKU từ tên món (chỉ khi thêm mới và người dùng chưa tự gõ SKU).
            name.addEventListener('input', function () { if (skuAuto) sku.value = toSlug(name.value); });
            sku.addEventListener('input', function () { skuAuto = false; sku.value = sku.value.toUpperCase(); });
            price.addEventListener('input', refreshPrice);
            refreshPrice();

            // Chọn loại món thì gợi ý quầy chế biến tương ứng, trừ khi người dùng đã tự chọn quầy.
            byId('station_code').addEventListener('change', function () { stationTouched = true; });
            byId('item_type').addEventListener('change', function (event) {
                var suggested = stationByType[event.target.value];
                if (suggested && !stationTouched) byId('station_code').value = suggested;
            });

            // Xem trước ảnh vừa chọn.
            byId('image').addEventListener('change', function (event) {
                var file = event.target.files && event.target.files[0];
                var preview = byId('image-preview');
                if (!file) return;
                preview.src = URL.createObjectURL(file);
                preview.classList.remove('hidden');
            });

            var toggleAll = byId('toggle-branches');
            if (toggleAll) {
                toggleAll.addEventListener('click', function () {
                    var boxes = document.querySelectorAll('input[name="branch_ids[]"]');
                    var allChecked = Array.prototype.every.call(boxes, function (box) { return box.checked; });
                    boxes.forEach(function (box) { box.checked = !allChecked; });
                    toggleAll.textContent = allChecked ? 'Chọn tất cả' : 'Bỏ chọn tất cả';
                });
            }
        })();
    </script>
@endsection
