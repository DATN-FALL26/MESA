@extends('layouts.auth')

@section('title', 'Sửa món | MESA')
@section('heading', 'Thực đơn')

@section('content')
    <a href="{{ route('menu.items.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800">
        <x-menu.icon name="arrow-left" class="size-4" /> Món ăn
    </a>

    <x-menu.page-header :title="$menuItem->name" :description="'Mã món '.$menuItem->sku">
    </x-menu.page-header>

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="min-w-0 space-y-5">
            <form id="item-form" method="POST" action="{{ route('menu.items.update', $menuItem) }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                @method('PUT')

                @include('menu.items._fields')
            </form>

            @includeIf('menu.items._variants')
            @includeIf('menu.items._modifier-groups')

            <section class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-rose-200 bg-rose-50/40 px-5 py-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Xóa món</h3>
                    <p class="mt-0.5 text-xs text-slate-600">Món sẽ ẩn khỏi thực đơn. Lịch sử đơn hàng và báo cáo cũ vẫn được giữ nguyên.</p>
                </div>
                <x-menu.confirm :action="route('menu.items.destroy', $menuItem)" title="Xóa món “{{ $menuItem->name }}”?"
                    message="Món sẽ ẩn khỏi thực đơn ở mọi chi nhánh. Mã món vẫn được giữ nên không thể dùng lại cho món khác." confirm="Xóa món"
                    class="rounded-lg border border-rose-300 bg-white px-3.5 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">
                    Xóa món
                </x-menu.confirm>
            </section>
        </div>

        @include('menu.items._preview', ['submitLabel' => 'Lưu thay đổi'])
    </div>

    @include('menu.items._form-script')
@endsection
