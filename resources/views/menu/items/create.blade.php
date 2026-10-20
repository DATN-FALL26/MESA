@extends('layouts.auth')

@section('title', 'Thêm món | MESA')
@section('heading', 'Thực đơn')

@section('content')
    <a href="{{ route('menu.items.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800">
        <x-menu.icon name="arrow-left" class="size-4" /> Món ăn
    </a>

    <x-menu.page-header title="Thêm món" description="Nhập thông tin món, xem trước bên phải rồi tạo món. Biến thể (size) và topping thêm được ở bước sau." />

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <form id="item-form" method="POST" action="{{ route('menu.items.store') }}" enctype="multipart/form-data" class="min-w-0 space-y-5">
            @csrf

            @include('menu.items._fields')

            <x-menu.section-card title="Áp dụng cho chi nhánh" description="Khách đặt qua QR chỉ thấy món đã áp dụng và đang còn hàng tại chi nhánh. Có thể đổi lại ở tab “Còn hàng & giá chi nhánh”.">
                <x-slot:actions>
                    @if ($branches->count() > 1)
                        <button type="button" id="toggle-branches" class="text-sm font-medium text-indigo-700 hover:underline">Bỏ chọn tất cả</button>
                    @endif
                </x-slot:actions>
                @php($selectedBranches = array_map('intval', (array) old('branch_ids', $branches->pluck('id')->all())))
                <div class="grid gap-2 sm:grid-cols-2">
                    @forelse ($branches as $branch)
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-700 hover:bg-slate-50">
                            <input type="checkbox" name="branch_ids[]" value="{{ $branch->id }}" class="size-4 accent-indigo-600" @checked(in_array($branch->id, $selectedBranches, true))>
                            {{ $branch->name }}
                        </label>
                    @empty
                        <p class="text-sm text-slate-500">Bạn chưa được gán chi nhánh nào nên món sẽ chưa hiện ở chi nhánh nào.</p>
                    @endforelse
                </div>
            </x-menu.section-card>
        </form>

        @include('menu.items._preview', ['submitLabel' => 'Tạo món'])
    </div>

    @include('menu.items._form-script')
@endsection
