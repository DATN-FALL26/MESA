@php
    // Gợi ý quầy chế biến theo loại món (lấy giá trị từ enum, không gõ cứng chuỗi).
    $stationByType = [
        \App\Enums\ItemType::DISH->value => \App\Enums\StationCode::PHO->value,
        \App\Enums\ItemType::DRINK->value => \App\Enums\StationCode::DRINK->value,
        \App\Enums\ItemType::SIDE->value => \App\Enums\StationCode::SIDE->value,
    ];
@endphp
<script>
    (function () {
        var byId = function (id) { return document.getElementById(id); };
        var money = new Intl.NumberFormat('vi-VN');
        var stationByType = @json($stationByType);

        var name = byId('name');
        var sku = byId('sku');
        var price = byId('base_price');
        var prep = byId('prep_time_seconds');
        var category = byId('category_id');
        var stationTouched = false;
        var skuAuto = sku && sku.dataset.auto === '1' && sku.value.trim() === '';

        function toSlug(text) {
            return text.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/đ/g, 'd').replace(/Đ/g, 'D')
                .toUpperCase().replace(/[^A-Z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 40);
        }

        function checkedLabel(inputName) {
            var input = document.querySelector('input[name="' + inputName + '"]:checked');
            return input && input.nextElementSibling ? input.nextElementSibling.textContent.trim() : '';
        }

        function refresh() {
            var amount = parseInt(price && price.value ? price.value : '0', 10) || 0;
            var seconds = parseInt(prep && prep.value ? prep.value : '0', 10) || 0;
            var categoryText = category && category.selectedIndex > 0
                ? category.options[category.selectedIndex].text.replace(/^—\s*/, '').trim() : '';

            if (byId('pv-name')) byId('pv-name').textContent = (name && name.value.trim()) || 'Tên món';
            if (byId('pv-price')) byId('pv-price').textContent = money.format(amount) + 'đ';
            if (byId('price-hint')) byId('price-hint').textContent = amount > 0 ? money.format(amount) + 'đ' : '';
            if (byId('prep-hint')) {
                byId('prep-hint').textContent = seconds >= 60 ? '≈ ' + (Math.round(seconds / 6) / 10) + ' phút' : (seconds > 0 ? seconds + ' giây' : '');
            }
            if (byId('pv-meta')) {
                var parts = [categoryText, checkedLabel('item_type'), checkedLabel('station_code')].filter(Boolean);
                byId('pv-meta').textContent = parts.length ? parts.join(' · ') : 'Chưa chọn danh mục';
            }
        }

        if (name) {
            name.addEventListener('input', function () {
                if (skuAuto && sku) sku.value = toSlug(name.value);
                refresh();
            });
        }
        if (sku) {
            sku.addEventListener('input', function () {
                skuAuto = false;
                sku.value = sku.value.toUpperCase();
            });
        }
        [price, prep, category].forEach(function (element) {
            if (element) { element.addEventListener('input', refresh); element.addEventListener('change', refresh); }
        });

        // Chọn loại món thì gợi ý quầy chế biến tương ứng, trừ khi người dùng đã tự chọn quầy.
        document.querySelectorAll('input[name="station_code"]').forEach(function (radio) {
            radio.addEventListener('change', function () { stationTouched = true; refresh(); });
        });
        document.querySelectorAll('input[name="item_type"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                var suggested = stationByType[radio.value];
                if (suggested && !stationTouched) {
                    var target = document.querySelector('input[name="station_code"][value="' + suggested + '"]');
                    if (target) target.checked = true;
                }
                refresh();
            });
        });

        // Nút gợi ý nhanh (thuế, thời gian chế biến, size...).
        document.querySelectorAll('[data-fill]').forEach(function (button) {
            button.addEventListener('click', function () {
                var target = byId(button.dataset.fill);
                if (!target) return;
                target.value = button.dataset.value;
                target.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });

        // Xem trước ảnh vừa chọn.
        var image = byId('image');
        if (image) {
            image.addEventListener('change', function () {
                var file = image.files && image.files[0];
                if (!file) return;
                if (byId('image-label')) byId('image-label').textContent = file.name;
                var preview = byId('pv-image');
                if (preview) {
                    preview.src = URL.createObjectURL(file);
                    preview.classList.remove('hidden');
                }
                if (byId('pv-placeholder')) byId('pv-placeholder').classList.add('hidden');
            });
        }

        // Chọn tất cả / bỏ chọn tất cả chi nhánh (trang thêm món).
        var toggleAll = byId('toggle-branches');
        if (toggleAll) {
            toggleAll.addEventListener('click', function () {
                var boxes = document.querySelectorAll('input[name="branch_ids[]"]');
                var allChecked = Array.prototype.every.call(boxes, function (box) { return box.checked; });
                boxes.forEach(function (box) { box.checked = !allChecked; });
                toggleAll.textContent = allChecked ? 'Chọn tất cả' : 'Bỏ chọn tất cả';
            });
        }

        refresh();
    })();
</script>
