@extends('layouts.auth')

@section('title', $role->exists ? 'Cập nhật vai trò | MESA' : 'Tạo vai trò | MESA')
@section('heading', $role->exists ? 'Cập nhật vai trò' : 'Tạo vai trò')

@section('content')
    @php($isEditing = $role->exists)
    <form method="POST" action="{{ $isEditing ? route('roles.update', $role) : route('roles.store') }}" class="space-y-6">
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif

        <section class="grid gap-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 md:grid-cols-2">
            <div>
                <label for="code" class="mb-1 block text-sm font-medium text-slate-700">Mã vai trò</label>
                <input id="code" name="code" value="{{ old('code', $role->code) }}" required maxlength="50" @readonly($role->is_system) 
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 shadow-sm transition duration-200 {{ $role->is_system ? 'bg-slate-100 cursor-not-allowed text-slate-500' : 'hover:border-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/20' }}">
                @if ($role->is_system)
                    <p class="mt-1 text-xs text-slate-500">Mã vai trò hệ thống không thể thay đổi.</p>
                @endif
            </div>
            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Tên vai trò</label>
                <input id="name" name="name" value="{{ old('name', $role->name) }}" required maxlength="100" 
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 shadow-sm transition duration-200 hover:border-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/20">
            </div>
            <div class="md:col-span-2">
                <label for="description" class="mb-1 block text-sm font-medium text-slate-700">Mô tả</label>
                <textarea id="description" name="description" rows="3" maxlength="255" 
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 shadow-sm transition duration-200 hover:border-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/20">{{ old('description', $role->description) }}</textarea>
            </div>
        </section>

        @if ($canAssignPermissions)
            <section class="space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div>
                    <h2 class="font-semibold text-slate-900">Quyền được gán</h2>
                    <p class="mt-1 text-sm text-slate-500">Chọn các quyền cụ thể mà thành viên có vai trò này sẽ nhận được.</p>
                </div>
                <input type="hidden" name="assign_permissions" value="1">
                @forelse ($permissions as $module => $modulePermissions)
                    <fieldset class="space-y-3">
                        <legend class="text-sm font-semibold text-slate-800">{{ str($module)->replace('_', ' ')->title() }}</legend>
                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($modulePermissions as $permission)
                                <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-3.5 shadow-sm transition duration-150 hover:bg-slate-50/80 hover:border-slate-300 cursor-pointer">
                                    <input type="checkbox" name="permission_ids[]" value="{{ $permission->id }}" @checked(in_array($permission->id, (array) old('permission_ids', $selectedPermissionIds))) 
                                        class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600/20 focus:ring-2">
                                    <span>
                                        <span class="block text-sm font-medium text-slate-900">{{ $permission->name }}</span>
                                        <span class="block text-xs text-slate-500">{{ $permission->code }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @empty
                    <p class="text-sm text-slate-500">Chưa có quyền nào trong hệ thống.</p>
                @endforelse
            </section>
        @endif

        <div class="flex justify-end gap-3">
            <a href="{{ route('roles.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition duration-150 hover:bg-slate-50 active:bg-slate-100">Hủy</a>
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition duration-150 hover:bg-indigo-500 active:bg-indigo-700">Lưu vai trò</button>
        </div>
    </form>
@endsection