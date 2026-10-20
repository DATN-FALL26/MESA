@extends('layouts.auth')

@section('title', 'Danh mục | MESA')
@section('heading', 'Thực đơn')

@section('content')
    @php($canManage = auth()->user()->hasPermission('menu.manage'))

    <x-menu.tabs active="categories" />

    <x-menu.page-header title="Danh mục" description="Gom món theo nhóm để nhân viên và khách tìm nhanh hơn. Hỗ trợ 2 cấp: danh mục cha và danh mục con.">
        <x-slot:actions>
            @if ($canManage)
                <a href="{{ route('menu.categories.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <x-menu.icon name="plus" class="size-4" /> Thêm danh mục
                </a>
            @endif
        </x-slot:actions>
    </x-menu.page-header>

    @if ($categories->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white">
            <x-menu.empty title="Chưa có danh mục nào" text="Tạo danh mục đầu tiên, ví dụ Phở hoặc Đồ uống, rồi thêm món vào." icon="squares">
                @if ($canManage)
                    <a href="{{ route('menu.categories.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        <x-menu.icon name="plus" class="size-4" /> Thêm danh mục
                    </a>
                @endif
            </x-menu.empty>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($categories as $category)
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    @include('menu.categories._row', ['row' => $category, 'isChild' => false, 'canManage' => $canManage])
                    @foreach ($category->children as $child)
                        @include('menu.categories._row', ['row' => $child, 'isChild' => true, 'canManage' => $canManage])
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif
@endsection
