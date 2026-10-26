@extends('layouts.auth')

@section('title', 'Hồ sơ cá nhân | MESA')
@section('heading', 'Hồ sơ cá nhân')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
        <aside class="rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-slate-200">
            @if ($user->avatar_path)
                <img src="{{ Storage::url($user->avatar_path) }}" alt="Ảnh đại diện" class="mx-auto h-28 w-28 rounded-full object-cover ring-4 ring-slate-100">
            @else
                <div class="mx-auto flex h-28 w-28 items-center justify-center rounded-full bg-indigo-100 text-3xl font-bold text-indigo-700 ring-4 ring-slate-100">
                    {{ strtoupper(substr($user->full_name ?: $user->username, 0, 1)) }}
                </div>
            @endif
            <h2 class="mt-4 text-xl font-semibold text-slate-900">{{ $user->full_name }}</h2>
            <p class="text-sm text-slate-500">{{ $user->username }}</p>
            <div class="mt-5">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Vai trò đang hoạt động</p>
                <div class="mt-3 flex flex-wrap justify-center gap-2">
                    @forelse ($user->activeRoles->unique('id') as $role)
                        <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-700">{{ $role->name }}</span>
                    @empty
                        <span class="text-xs text-amber-700">Chưa có vai trò</span>
                    @endforelse
                </div>
            </div>
        </aside>

        <div class="space-y-6">
            @if ($user->hasPermission('profile.update'))
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="mb-5 font-semibold text-slate-900">Thông tin cá nhân</h2>
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-5">
                        @csrf
                        @method('PUT')
                        <div class="grid gap-5 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label for="avatar" class="mb-1 block text-sm font-medium text-slate-700">Ảnh đại diện</label>
                                <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" 
                                    class="block w-full cursor-pointer rounded-lg border border-slate-300 bg-white text-sm text-slate-500 shadow-sm transition duration-200 file:mr-4 file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:border-slate-400 hover:file:bg-slate-200 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/20">
                            </div>
                            <div>
                                <label for="username" class="mb-1 block text-sm font-medium text-slate-700">Tên đăng nhập</label>
                                <input id="username" value="{{ $user->username }}" disabled 
                                    class="w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-100 px-3.5 py-2 text-sm text-slate-500 shadow-sm">
                            </div>
                            <div>
                                <label for="full_name" class="mb-1 block text-sm font-medium text-slate-700">Họ và tên</label>
                                <input id="full_name" name="full_name" value="{{ old('full_name', $user->full_name) }}" required maxlength="150" 
                                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 shadow-sm transition duration-200 hover:border-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/20">
                            </div>
                            <div>
                                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="150" 
                                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 shadow-sm transition duration-200 hover:border-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/20">
                            </div>
                            <div>
                                <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">Số điện thoại</label>
                                <input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="20" 
                                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 shadow-sm transition duration-200 hover:border-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/20">
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-150 hover:bg-indigo-500 active:bg-indigo-700">Lưu hồ sơ</button>
                        </div>
                    </form>
                </section>

                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="mb-5 font-semibold text-slate-900">Đổi mật khẩu</h2>
                    <form method="POST" action="{{ route('profile.password.update') }}" class="space-y-5">
                        @csrf
                        @method('PUT')
                        <div>
                            <label for="current_password" class="mb-1 block text-sm font-medium text-slate-700">Mật khẩu hiện tại</label>
                            <input id="current_password" name="current_password" type="password" required autocomplete="current-password" 
                                class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 shadow-sm transition duration-200 hover:border-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/20">
                        </div>
                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Mật khẩu mới</label>
                                <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" 
                                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 shadow-sm transition duration-200 hover:border-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/20">
                            </div>
                            <div>
                                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Xác nhận mật khẩu mới</label>
                                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" 
                                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 shadow-sm transition duration-200 hover:border-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/20">
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition duration-150 hover:bg-slate-50 active:bg-slate-100">Đổi mật khẩu</button>
                        </div>
                    </form>
                </section>
            @endif
        </div>
    </div>
@endsection