@props(['label', 'name', 'required' => false, 'hint' => null, 'modal' => null])
@php
    // Chỉ hiện lỗi ở đúng form vừa gửi (modal đang mở lại sau khi validate lỗi), tránh lỗi "chạy" sang form khác cùng trang.
    $showError = old('_modal') === $modal && $errors->has($name);
@endphp
<div {{ $attributes->class(['min-w-0']) }}>
    <label for="{{ $modal ? $modal.'-' : '' }}{{ $name }}" class="block font-medium text-slate-700 mb-1">
        {{ $label }}@if ($required) <span class="text-rose-500">*</span>@endif
    </label>
    {{ $slot }}
    @if ($showError)
        <p class="text-[10px] text-rose-600 font-medium mt-1">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p class="text-[10px] text-slate-400 mt-1">{{ $hint }}</p>
    @endif
</div>
