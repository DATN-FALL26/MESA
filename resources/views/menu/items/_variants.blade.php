{{-- Biến thể (size) của món. Mỗi dòng là một form riêng nên nằm ngoài form thông tin món. --}}
<x-menu.section-card id="variants" title="Biến thể (size)" description="Giá cuối = giá bán + mức chênh lệch. Ví dụ tô lớn +10.000đ. Mức chênh có thể âm.">
    @php($basePrice = (int) $menuItem->base_price)

    @if ($errors->has('variant'))
        <p class="mb-4 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $errors->first('variant') }}</p>
    @endif

    <div class="divide-y divide-slate-100">
        @forelse ($menuItem->variants as $variant)
            <div class="flex flex-wrap items-center gap-3 py-3 first:pt-0">
                <form method="POST" action="{{ route('menu.variants.update', $variant) }}" class="grid min-w-0 flex-1 grid-cols-12 items-center gap-3">
                    @csrf
                    @method('PUT')
                    <div class="col-span-12 sm:col-span-4">
                        <x-menu.input name="name" id="variant-name-{{ $variant->id }}" :value="$variant->name" :old="false" required maxlength="50" aria-label="Tên biến thể" />
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <x-menu.input name="price_delta" id="variant-delta-{{ $variant->id }}" type="number" step="1" :value="(int) $variant->price_delta" :old="false" required suffix="đ"
                            aria-label="Mức chênh lệch giá" data-variant-delta="{{ $basePrice }}" />
                    </div>
                    <div class="col-span-6 text-sm sm:col-span-2">
                        <span class="text-xs text-slate-500">Giá bán</span>
                        <p class="font-semibold tabular-nums text-slate-900" data-variant-final>{{ number_format($basePrice + (int) $variant->price_delta, 0, ',', '.') }}đ</p>
                    </div>
                    <div class="col-span-12 flex flex-wrap items-center justify-between gap-x-4 gap-y-2 sm:col-span-3 sm:justify-end">
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                            <x-menu.switch name="is_default" :checked="$variant->is_default" label="Mặc định" />
                            <x-menu.switch name="is_active" :checked="$variant->is_active" label="Bán" />
                        </div>
                        <button type="submit" class="rounded-lg bg-slate-800 px-3 py-1.5 text-sm font-semibold text-white hover:bg-slate-700">Lưu</button>
                    </div>
                </form>
                <x-menu.confirm :action="route('menu.variants.destroy', $variant)" title="Xóa biến thể “{{ $variant->name }}”?"
                    message="Nếu biến thể đã dùng trong đơn hàng hoặc công thức định lượng thì sẽ không xóa được, khi đó hãy tắt “Bán”." confirm="Xóa biến thể"
                    class="inline-flex size-8 items-center justify-center rounded-lg text-slate-500 hover:bg-rose-50 hover:text-rose-600" aria-label="Xóa biến thể {{ $variant->name }}">
                    <x-menu.icon name="trash" class="size-4" />
                </x-menu.confirm>
            </div>
        @empty
            <p class="pb-1 text-sm text-slate-500">Món chưa có biến thể. Khách gọi món với giá bán thông thường.</p>
        @endforelse
    </div>

    <form method="POST" action="{{ route('menu.variants.store', $menuItem) }}" class="mt-5 rounded-lg bg-slate-50 p-4">
        @csrf
        <p class="text-sm font-medium text-slate-800">Thêm biến thể</p>
        <div class="mt-2 flex flex-wrap items-center gap-1.5">
            <span class="text-xs text-slate-500">Gợi ý nhanh:</span>
            @foreach ([['Nhỏ', 0], ['Vừa', 0], ['Lớn', 10000], ['Đặc biệt', 20000]] as [$presetName, $presetDelta])
                <button type="button" data-variant-preset data-name="{{ $presetName }}" data-delta="{{ $presetDelta }}"
                    class="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-600 hover:border-indigo-400 hover:text-indigo-700">{{ $presetName }}</button>
            @endforeach
        </div>
        <div class="mt-3 grid grid-cols-12 items-end gap-3">
            <div class="col-span-12 sm:col-span-5">
                <x-menu.input name="name" id="new-variant-name" :old="false" required maxlength="50" placeholder="Tên biến thể" aria-label="Tên biến thể mới" />
            </div>
            <div class="col-span-7 sm:col-span-3">
                <x-menu.input name="price_delta" id="new-variant-delta" type="number" step="1" value="0" :old="false" required suffix="đ" aria-label="Mức chênh lệch giá" />
            </div>
            <div class="col-span-5 sm:col-span-4 sm:text-right">
                <input type="hidden" name="is_active" value="1">
                <input type="hidden" name="is_default" value="0">
                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 sm:w-auto">Thêm biến thể</button>
            </div>
        </div>
    </form>

    <script>
        (function () {
            var money = new Intl.NumberFormat('vi-VN');
            document.querySelectorAll('[data-variant-delta]').forEach(function (input) {
                input.addEventListener('input', function () {
                    var base = parseInt(input.dataset.variantDelta, 10) || 0;
                    var total = base + (parseInt(input.value || '0', 10) || 0);
                    var label = input.closest('form').querySelector('[data-variant-final]');
                    if (label) label.textContent = money.format(total) + 'đ';
                });
            });
            document.querySelectorAll('[data-variant-preset]').forEach(function (button) {
                button.addEventListener('click', function () {
                    document.getElementById('new-variant-name').value = button.dataset.name;
                    document.getElementById('new-variant-delta').value = button.dataset.delta;
                    document.getElementById('new-variant-name').focus();
                });
            });
        })();
    </script>
</x-menu.section-card>
