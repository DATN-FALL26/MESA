{{-- Modal thêm / sửa nhóm topping (dùng ở trang danh sách và trang chi tiết nhóm). --}}
<x-menu.modal id="group-modal" title="Thêm nhóm topping">
    <x-menu.modal-form modal="group-modal" :action="route('menu.modifier-groups.store')" submit="Lưu nhóm">
        <x-menu.field label="Tên nhóm" name="name" modal="group-modal" required>
            <x-menu.input name="name" modal="group-modal" required maxlength="100" placeholder="VD: Thêm thịt, Ăn kèm" autocomplete="off" />
        </x-menu.field>
        <div class="grid grid-cols-2 gap-3">
            <x-menu.field label="Chọn tối thiểu" name="min_select" modal="group-modal" required hint="0 = không bắt buộc chọn.">
                <x-menu.input name="min_select" type="number" min="0" max="20" step="1" value="0" modal="group-modal" required />
            </x-menu.field>
            <x-menu.field label="Chọn tối đa" name="max_select" modal="group-modal" required hint="Phải lớn hơn hoặc bằng tối thiểu.">
                <x-menu.input name="max_select" type="number" min="1" max="20" step="1" value="1" modal="group-modal" required />
            </x-menu.field>
        </div>
        <x-menu.checkbox name="is_active" label="Đang hoạt động" :checked="true" modal="group-modal"
            hint="Tắt để ẩn nhóm khỏi các món đang dùng mà không cần xóa." />
    </x-menu.modal-form>
</x-menu.modal>
