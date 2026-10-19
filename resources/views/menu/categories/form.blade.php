@extends('layouts.auth')

@section('title', $category->exists ? 'Sửa danh mục | MESA' : 'Thêm danh mục | MESA')
@section('heading', $category->exists ? 'Sửa danh mục' : 'Thêm danh mục')

@section('content')
    @php($isEditing = $category->exists)
    <form method="POST" action="{{ $isEditing ? route('menu.categories.update', $category) : route('menu.categories.store') }}" class="space-y-6">
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif

        <section class="grid gap-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 md:grid-cols-2">
            <div>
                <label for="name" class="mb-1 block text-sm font-medium">Tên danh mục <span class="text-red-600">*</span></label>
                <input id="name" name="name" value="{{ old('name', $category->name) }}" required maxlength="100" class="w-full rounded-lg border-slate-300">
            </div>
            <div>
                <label for="parent_id" class="mb-1 block text-sm font-medium">Danh mục cha</label>
                <select id="parent_id" name="parent_id" class="w-full rounded-lg border-slate-300">
                    <option value="">— Danh mục gốc —</option>
                    @foreach ($parents as $parent)
                        <option value="{{ $parent->id }}" @selected((int) old('parent_id', $category->parent_id) === $parent->id)>{{ $parent->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="sort_order" class="mb-1 block text-sm font-medium">Thứ tự hiển thị</label>
                <input id="sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="w-full rounded-lg border-slate-300">
            </div>
            <div class="flex items-end">
                <input type="hidden" name="is_active" value="0">
                <label class="flex items-center gap-2 pb-2 text-sm font-medium">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $isEditing ? $category->is_active : true)) class="rounded border-slate-300">
                    Hoạt động
                </label>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('menu.categories.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-white">Hủy</a>
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Lưu danh mục</button>
        </div>
    </form>
@endsection
