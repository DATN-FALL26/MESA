@extends('layouts.auth')

@section('title', $user->exists ? 'Cập nhật tài khoản | MESA' : 'Tạo tài khoản | MESA')
@section('heading', $user->exists ? 'Cập nhật tài khoản' : 'Tạo tài khoản')

@section('content')
    @php($isEditing = $user->exists)
    <form method="POST" action="{{ $isEditing ? route('users.update', $user) : route('users.store') }}" class="space-y-6">
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif

        <section class="grid gap-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 md:grid-cols-2">
            <div>
                <label for="username" class="mb-1 block text-sm font-medium">Tên đăng nhập</label>
                <input id="username" name="username" value="{{ old('username', $user->username) }}" required maxlength="50" class="w-full rounded-lg border-slate-300">
            </div>
            <div>
                <label for="employee_code" class="mb-1 block text-sm font-medium">Mã nhân viên</label>
                <input id="employee_code" name="employee_code" value="{{ old('employee_code', $user->employee_code) }}" maxlength="30" class="w-full rounded-lg border-slate-300">
            </div>
            <div>
                <label for="full_name" class="mb-1 block text-sm font-medium">Họ và tên</label>
                <input id="full_name" name="full_name" value="{{ old('full_name', $user->full_name) }}" required maxlength="150" class="w-full rounded-lg border-slate-300">
            </div>
            <div>
                <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="150" class="w-full rounded-lg border-slate-300">
            </div>
            <div>
                <label for="phone" class="mb-1 block text-sm font-medium">Số điện thoại</label>
                <input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="20" class="w-full rounded-lg border-slate-300">
            </div>
            <div>
                <label for="department_id" class="mb-1 block text-sm font-medium">Phòng ban</label>
                <select id="department_id" name="department_id" class="w-full rounded-lg border-slate-300">
                    <option value="">Không chọn</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) old('department_id', $user->department_id) === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="mb-1 block text-sm font-medium">Trạng thái</label>
                <select id="status" name="status" required class="w-full rounded-lg border-slate-300">
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $user->status?->value ?? 'active') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-medium">Mật khẩu {{ $isEditing ? '(để trống nếu không đổi)' : '' }}</label>
                <input id="password" name="password" type="password" {{ $isEditing ? '' : 'required' }} minlength="8" autocomplete="new-password" class="w-full rounded-lg border-slate-300">
            </div>
            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium">Xác nhận mật khẩu</label>
                <input id="password_confirmation" name="password_confirmation" type="password" {{ $isEditing ? '' : 'required' }} minlength="8" autocomplete="new-password" class="w-full rounded-lg border-slate-300">
            </div>
        </section>

        @if ($canAssignRoles)
            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="mb-4">
                    <h2 class="font-semibold text-slate-900">Vai trò</h2>
                    <p class="mt-1 text-sm text-slate-500">Vai trò mới được cấp trong phạm vi toàn hệ thống.</p>
                </div>
                <input type="hidden" name="assign_roles" value="1">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($roles as $role)
                        <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-4">
                            <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" @checked(in_array($role->id, (array) old('role_ids', $selectedRoleIds))) class="mt-1 rounded border-slate-300 text-indigo-600">
                            <span>
                                <span class="block font-medium text-slate-900">{{ $role->name }}</span>
                                <span class="block text-xs text-slate-500">{{ $role->code }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="flex justify-end gap-3">
            <a href="{{ route('users.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-white">Hủy</a>
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Lưu tài khoản</button>
        </div>
    </form>
@endsection
