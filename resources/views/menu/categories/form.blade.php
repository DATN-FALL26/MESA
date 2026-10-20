@extends('layouts.auth')

@section('title', $category->exists ? 'Sửa danh mục | MESA' : 'Thêm danh mục | MESA')
@section('heading', 'Thực đơn')

@section('content')
    @php($isEditing = $category->exists)

    <a href="{{ route('menu.categories.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800">
        <x-menu.icon name="arrow-left" class="size-4" /> Danh mục
    </a>

    <x-menu.page-header :title="$isEditing ? 'Sửa danh mục' : 'Thêm danh mục'"
        :description="$isEditing ? 'Đổi tên, vị trí hoặc tắt danh mục khi không dùng nữa.' : 'Danh mục gốc hiện ở cấp trên cùng. Chọn danh mục cha nếu muốn tạo danh mục con.'" />

    <form method="POST" action="{{ $isEditing ? route('menu.categories.update', $category) : route('menu.categories.store') }}" class="max-w-2xl space-y-5">
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif

        <div class="grid gap-5 rounded-xl border border-slate-200 bg-white p-5 sm:grid-cols-2">
            <x-menu.field label="Tên danh mục" name="name" required class="sm:col-span-2">
                <x-menu.input name="name" :value="$category->name" required maxlength="100" placeholder="Ví dụ: Phở bò" autofocus />
            </x-menu.field>

            <x-menu.field label="Danh mục cha" name="parent_id" hint="Để trống nếu đây là danh mục gốc.">
                <x-menu.select name="parent_id">
                    <option value="">Không có (danh mục gốc)</option>
                    @foreach ($parents as $parent)
                        <option value="{{ $parent->id }}" @selected((int) old('parent_id', $category->parent_id) === $parent->id)>{{ $parent->name }}</option>
                    @endforeach
                </x-menu.select>
            </x-menu.field>

            <x-menu.field label="Thứ tự hiển thị" name="sort_order" hint="Số nhỏ hiện trước.">
                <x-menu.input name="sort_order" type="number" min="0" max="9999" step="1" :value="$category->sort_order ?? 0" />
            </x-menu.field>

            <div class="sm:col-span-2">
                <x-menu.switch name="is_active" :checked="(bool) old('is_active', $category->is_active)" label="Đang hoạt động" />
                <p class="mt-1.5 text-xs text-slate-500">Tắt để ẩn danh mục khỏi thực đơn mà không cần xóa.</p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('menu.categories.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Hủy</a>
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                {{ $isEditing ? 'Lưu thay đổi' : 'Tạo danh mục' }}
            </button>
        </div>
    </form>
@endsection
