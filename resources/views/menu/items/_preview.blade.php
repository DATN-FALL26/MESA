{{-- Cột bên phải: xem trước món và nút lưu (nút gắn với form qua thuộc tính form="item-form"). --}}
@php
    $previewPrice = old('base_price', $menuItem->exists ? (int) $menuItem->base_price : 0);
    $viewExists = fn (string $name): bool => \Illuminate\Support\Facades\View::exists($name);
@endphp
<aside class="space-y-4 lg:sticky lg:top-6">
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="relative flex aspect-[4/3] items-center justify-center bg-slate-100 text-slate-300">
            <img id="pv-image" src="{{ $menuItem->image_path ? asset('storage/'.$menuItem->image_path) : '' }}" alt="" @class(['absolute inset-0 size-full object-cover', 'hidden' => ! $menuItem->image_path])>
            <div id="pv-placeholder" @class(['hidden' => (bool) $menuItem->image_path])><x-menu.icon name="photo" class="size-10" /></div>
        </div>
        <div class="p-4">
            <p id="pv-name" class="truncate text-base font-semibold text-slate-900">{{ old('name', $menuItem->name) ?: 'Tên món' }}</p>
            <p id="pv-meta" class="mt-0.5 truncate text-xs text-slate-500">Chưa chọn danh mục</p>
            <p id="pv-price" class="mt-3 text-xl font-semibold tabular-nums text-slate-900">{{ number_format((float) $previewPrice, 0, ',', '.') }}đ</p>
        </div>
    </div>

    <div class="space-y-3 rounded-xl border border-slate-200 bg-white p-4">
        <button type="submit" form="item-form" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">{{ $submitLabel }}</button>
        <a href="{{ route('menu.items.index') }}" class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-center text-sm font-medium text-slate-700 hover:bg-slate-50">Hủy</a>
    </div>

    @if ($menuItem->exists)
        <nav class="rounded-xl border border-slate-200 bg-white p-2 text-sm" aria-label="Các phần của món">
            <a href="#info" class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900">Thông tin món</a>
            @if ($viewExists('menu.items._variants'))
                <a href="#variants" class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900">Biến thể (size)</a>
            @endif
            @if ($viewExists('menu.items._modifier-groups'))
                <a href="#groups" class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900">Nhóm topping</a>
            @endif
        </nav>
    @endif
</aside>
