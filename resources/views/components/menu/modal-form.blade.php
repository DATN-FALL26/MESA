@props(['modal', 'action', 'submit' => 'Lưu'])
@php
    $failed = old('_modal') === $modal;
@endphp
{{-- Form trong modal: JS (MenuUI) đổi action/method/tiêu đề khi mở ở chế độ sửa; các trường _modal/_action/_title/_method
     được gửi kèm để mở lại đúng modal ở trạng thái đang sửa nếu validate lỗi. --}}
<form method="POST" action="{{ $failed && old('_action') ? old('_action') : $action }}" data-menu-form data-default-action="{{ $action }}"
    {{ $attributes->merge(['class' => 'space-y-3.5 text-xs']) }}>
    @csrf
    <input type="hidden" name="_method" value="{{ $failed ? old('_method', 'POST') : 'POST' }}">
    <input type="hidden" name="_modal" value="{{ $modal }}">
    <input type="hidden" name="_action" value="{{ $failed ? old('_action') : '' }}">
    <input type="hidden" name="_title" value="{{ $failed ? old('_title') : '' }}">

    {{ $slot }}

    <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
        <button type="button" data-modal-close class="px-3.5 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition-colors">Hủy</button>
        <button type="submit" data-submit-label class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors">{{ $submit }}</button>
    </div>
</form>
@if ($failed)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            MenuUI.reopen(@js($modal), { action: @js(old('_action')), method: @js(old('_method', 'POST')), title: @js(old('_title')) });
        });
    </script>
@endif
