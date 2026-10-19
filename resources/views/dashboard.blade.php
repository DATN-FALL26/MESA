@extends('layouts.auth')

@section('title', 'Dashboard | MESA')
@section('heading', 'Dashboard')

@section('content')
    <section class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
        <p class="text-sm text-slate-500">Xin chào</p>
        <h2 class="mt-2 text-3xl font-semibold text-slate-900">{{ auth()->user()->full_name }}</h2>
        <p class="mt-3 max-w-2xl text-slate-600">
            Đây là bảng điều khiển chung. Các chức năng quản lý được hiển thị theo quyền của tài khoản.
        </p>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @if (auth()->user()->hasPermission('users.view'))
            <a href="{{ route('users.index') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 hover:ring-indigo-300">
                <h3 class="font-semibold text-slate-900">Quản lý tài khoản</h3>
                <p class="mt-2 text-sm text-slate-600">Tài khoản và vai trò được gán.</p>
            </a>
        @endif
        @if (auth()->user()->hasPermission('roles.view'))
            <a href="{{ route('roles.index') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 hover:ring-indigo-300">
                <h3 class="font-semibold text-slate-900">Quản lý vai trò</h3>
                <p class="mt-2 text-sm text-slate-600">Nhóm quyền và cấu hình vai trò.</p>
            </a>
        @endif
        @if (auth()->user()->hasPermission('permissions.view'))
            <a href="{{ route('permissions.index') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 hover:ring-indigo-300">
                <h3 class="font-semibold text-slate-900">Quản lý quyền</h3>
                <p class="mt-2 text-sm text-slate-600">Danh mục quyền chức năng.</p>
            </a>
        @endif
    </section>
@endsection
