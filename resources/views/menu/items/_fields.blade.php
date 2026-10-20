{{-- Các mục nhập của món (dùng chung cho thêm mới và chỉnh sửa). Nằm trong <form id="item-form">. --}}
@php
    $chipClass = 'rounded-md border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-600 hover:border-indigo-400 hover:text-indigo-700';
    $optionClass = 'flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-600 transition-colors hover:border-slate-400 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 peer-checked:font-medium peer-checked:text-indigo-700 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-500';
@endphp

<x-menu.section-card id="info" title="Thông tin món" description="Tên và mã món hiển thị cho nhân viên, bếp và hóa đơn.">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-menu.field label="Tên món" name="name" required>
            <x-menu.input name="name" :value="$menuItem->name" required maxlength="150" placeholder="Ví dụ: Phở tái nạm" autocomplete="off" />
        </x-menu.field>

        <x-menu.field label="Mã món (SKU)" name="sku" required hint="Chữ in hoa, số và . _ - ; không trùng món nào, kể cả món đã xóa. Tự gợi ý từ tên món.">
            <x-menu.input name="sku" :value="$menuItem->sku" required maxlength="40" placeholder="PHO-TAI-NAM" autocomplete="off"
                data-auto="{{ $menuItem->exists ? '0' : '1' }}" class="font-mono uppercase" />
        </x-menu.field>

        <x-menu.field label="Danh mục" name="category_id" required class="sm:col-span-2">
            <x-menu.select name="category_id" required>
                <option value="">Chọn danh mục</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) old('category_id', $menuItem->category_id) === $category->id)>
                        {{ $category->parent_id ? '— ' : '' }}{{ $category->name }}{{ $category->is_active ? '' : ' (đang tắt)' }}
                    </option>
                @endforeach
            </x-menu.select>
        </x-menu.field>

        <x-menu.field label="Mô tả ngắn" name="description" class="sm:col-span-2">
            <textarea id="description" name="description" rows="2" maxlength="255" placeholder="Hiển thị cho khách khi gọi món (không bắt buộc)"
                @class([
                    'block w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 shadow-xs placeholder:text-slate-400 focus:outline-hidden focus:ring-2',
                    'border-rose-400 focus:border-rose-500 focus:ring-rose-200' => $errors->has('description'),
                    'border-slate-300 focus:border-indigo-500 focus:ring-indigo-200' => ! $errors->has('description'),
                ])>{{ old('description', $menuItem->description) }}</textarea>
        </x-menu.field>
    </div>
</x-menu.section-card>

<x-menu.section-card title="Phân loại và chế biến" description="Quầy chế biến quyết định phiếu bếp (KDS) của món hiện ở đâu.">
    <div class="space-y-5">
        <fieldset>
            <legend class="mb-1.5 text-sm font-medium text-slate-800">Loại món</legend>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach ($itemTypes as $type)
                    <label class="cursor-pointer">
                        <input type="radio" name="item_type" value="{{ $type->value }}" class="peer sr-only" required
                            @checked(old('item_type', $menuItem->item_type?->value ?? 'dish') === $type->value)>
                        <span class="{{ $optionClass }}">{{ $type->label() }}</span>
                    </label>
                @endforeach
            </div>
            @error('item_type')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
        </fieldset>

        <fieldset>
            <legend class="mb-1.5 text-sm font-medium text-slate-800">Quầy chế biến</legend>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                @foreach ($stations as $station)
                    <label class="cursor-pointer">
                        <input type="radio" name="station_code" value="{{ $station->value }}" class="peer sr-only" required
                            @checked(old('station_code', $menuItem->station_code?->value ?? 'PHO') === $station->value)>
                        <span class="{{ $optionClass }}">{{ $station->label() }}</span>
                    </label>
                @endforeach
            </div>
            @error('station_code')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
        </fieldset>
    </div>
</x-menu.section-card>

<x-menu.section-card title="Giá và thời gian" description="Giá gốc áp dụng cho mọi chi nhánh, trừ chi nhánh có giá riêng.">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-menu.field label="Giá bán gốc" name="base_price" required hint="Số nguyên VND, không có phần lẻ.">
            <x-menu.input name="base_price" type="number" min="0" step="1" :value="$menuItem->exists ? (int) $menuItem->base_price : null" required suffix="đ" placeholder="55000" inputmode="numeric" />
            <p id="price-hint" class="mt-1.5 text-sm font-medium text-slate-700"></p>
        </x-menu.field>

        <x-menu.field label="Thuế VAT" name="tax_rate" required>
            <x-menu.input name="tax_rate" type="number" min="0" max="100" step="0.01" :value="$menuItem->exists ? $menuItem->tax_rate : '8.00'" required suffix="%" />
            <div class="mt-2 flex gap-1.5">
                @foreach ([0, 5, 8, 10] as $rate)
                    <button type="button" data-fill="tax_rate" data-value="{{ $rate }}" class="{{ $chipClass }}">{{ $rate }}%</button>
                @endforeach
            </div>
        </x-menu.field>

        <x-menu.field label="Thời gian chế biến" name="prep_time_seconds" required class="sm:col-span-2" hint="Bếp dùng số này để canh giờ nấu lệch nhau, giúp các món ra cùng lúc.">
            <div class="max-w-xs">
                <x-menu.input name="prep_time_seconds" type="number" min="0" max="86400" step="1" :value="$menuItem->exists ? $menuItem->prep_time_seconds : 180" required suffix="giây" />
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                @foreach ([60 => '1 phút', 120 => '2 phút', 180 => '3 phút', 300 => '5 phút', 480 => '8 phút'] as $seconds => $text)
                    <button type="button" data-fill="prep_time_seconds" data-value="{{ $seconds }}" class="{{ $chipClass }}">{{ $text }}</button>
                @endforeach
                <span id="prep-hint" class="ml-1 text-xs text-slate-500"></span>
            </div>
        </x-menu.field>
    </div>
</x-menu.section-card>

<x-menu.section-card title="Hình ảnh" description="Ảnh vuông hoặc ngang, jpg, png hoặc webp, tối đa 2MB.">
    <div class="flex flex-wrap items-center gap-4">
        @if ($menuItem->image_path)
            <img src="{{ asset('storage/'.$menuItem->image_path) }}" alt="{{ $menuItem->name }}" class="size-20 rounded-lg object-cover ring-1 ring-slate-200">
        @endif
        <div class="min-w-0 flex-1">
            <label for="image" class="flex cursor-pointer items-center gap-3 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-600 hover:border-indigo-400 hover:bg-indigo-50/40">
                <x-menu.icon name="photo" class="size-5 shrink-0 text-slate-400" />
                <span id="image-label" class="truncate">{{ $menuItem->image_path ? 'Chọn ảnh khác để thay thế' : 'Chọn ảnh món' }}</span>
            </label>
            <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only">
            @error('image')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            @if ($menuItem->image_path)
                <label class="mt-2 inline-flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remove_image" value="1" class="size-4 accent-rose-600"> Xóa ảnh hiện tại
                </label>
            @endif
        </div>
    </div>
</x-menu.section-card>

<x-menu.section-card title="Trạng thái">
    <x-menu.switch name="is_active" :checked="(bool) old('is_active', $menuItem->exists ? $menuItem->is_active : true)" label="Đang kinh doanh" />
    <p class="mt-1.5 text-xs text-slate-500">Tắt để tạm ngừng bán món ở mọi chi nhánh mà không cần xóa.</p>
</x-menu.section-card>
