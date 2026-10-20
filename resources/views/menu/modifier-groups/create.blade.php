@extends('layouts.auth')

@section('title', 'Thêm nhóm topping | MESA')
@section('heading', 'Thực đơn')

@section('content')
    <a href="{{ route('menu.modifier-groups.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800">
        <x-menu.icon name="arrow-left" class="size-4" /> Nhóm topping
    </a>

    <x-menu.page-header title="Thêm nhóm topping" description="Tạo nhóm trước, bước kế tiếp là thêm các topping vào nhóm." />

    <form method="POST" action="{{ route('menu.modifier-groups.store') }}" class="max-w-2xl space-y-5">
        @csrf
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            @include('menu.modifier-groups._fields')
        </div>
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('menu.modifier-groups.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Hủy</a>
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">Tạo nhóm</button>
        </div>
    </form>
@endsection
