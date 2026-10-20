<div class="grid gap-5 sm:grid-cols-3">
    <x-menu.field label="Tên nhóm" name="name" required class="sm:col-span-3">
        <x-menu.input name="name" :value="$group->name" required maxlength="100" placeholder="Ví dụ: Thêm thịt, Ăn kèm" autofocus />
    </x-menu.field>
    <x-menu.field label="Chọn tối thiểu" name="min_select" hint="0 nghĩa là không bắt buộc chọn.">
        <x-menu.input name="min_select" type="number" min="0" max="20" step="1" :value="$group->exists ? $group->min_select : 0" required />
    </x-menu.field>
    <x-menu.field label="Chọn tối đa" name="max_select" hint="Phải lớn hơn hoặc bằng tối thiểu.">
        <x-menu.input name="max_select" type="number" min="1" max="20" step="1" :value="$group->exists ? $group->max_select : 1" required />
    </x-menu.field>
    <div class="flex items-center sm:pt-7">
        <x-menu.switch name="is_active" :checked="(bool) old('is_active', $group->exists ? $group->is_active : true)" label="Đang hoạt động" />
    </div>
</div>
