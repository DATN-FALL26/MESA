@extends('layouts.auth')

@section('title', $permission->exists ? 'Cập nhật quyền | MESA' : 'Tạo quyền | MESA')
@section('heading', $permission->exists ? 'Cập nhật quyền' : 'Tạo quyền')

@section('content')
    @php($isEditing = $permission->exists)
    <form method="POST" action="{{ $isEditing ? route('permissions.update', $permission) : route('permissions.store') }}" class="space-y-6">
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif

        <section class="grid gap-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 md:grid-cols-2">
            <div>
                <label for="code" class="mb-1 block text-sm font-medium">Mã quyền</label>
                <input id="code" name="code" value="{{ old('code', $permission->code) }}" required maxlength="80" placeholder="users.view" class="w-full rounded-lg border-slate-300">
                <p class="mt-1 text-xs text-slate-500">Dùng chữ thường và dấu chấm, ví dụ users.view.</p>
            </div>
            <div>
                <label for="module" class="mb-1 block text-sm font-medium">Module</label>
                <input id="module" name="module" value="{{ old('module', $permission->module) }}" required maxlength="50" placeholder="auth_user" class="w-full rounded-lg border-slate-300">
            </div>
            <div>
                <label for="name" class="mb-1 block text-sm font-medium">Tên quyền</label>
                <input id="name" name="name" value="{{ old('name', $permission->name) }}" required maxlength="150" class="w-full rounded-lg border-slate-300">
            </div>
            <div>
                <label for="description" class="mb-1 block text-sm font-medium">Mô tả</label>
                <input id="description" name="description" value="{{ old('description', $permission->description) }}" maxlength="255" class="w-full rounded-lg border-slate-300">
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('permissions.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-white">Hủy</a>
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Lưu quyền</button>
        </div>
    </form>
@endsection
