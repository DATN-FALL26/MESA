@extends('layouts.auth')

@section('title', 'Danh mục | MESA')
@section('heading', 'Danh mục thực đơn')

@section('content')
    @php($canManage = auth()->user()->hasPermission('menu.manage'))

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600">Danh mục gồm 2 cấp (cha - con). Danh mục đang có món không thể xóa, hãy tắt hoạt động.</p>
        @if ($canManage)
            <a href="{{ route('menu.categories.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Thêm danh mục</a>
        @endif
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Tên danh mục</th>
                        <th class="px-5 py-3 text-center">Thứ tự</th>
                        <th class="px-5 py-3 text-center">Số món</th>
                        <th class="px-5 py-3">Trạng thái</th>
                        <th class="px-5 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($categories as $category)
                        @foreach ([$category, ...$category->children] as $row)
                            <tr>
                                <td class="px-5 py-3 {{ $row->parent_id ? 'pl-12 text-slate-700' : 'font-semibold text-slate-900' }}">{{ $row->parent_id ? '— ' : '' }}{{ $row->name }}</td>
                                <td class="px-5 py-3 text-center text-slate-600">{{ $row->sort_order }}</td>
                                <td class="px-5 py-3 text-center text-slate-600">{{ $row->menu_items_count }}</td>
                                <td class="px-5 py-3">
                                    @if ($row->is_active)
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Hoạt động</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">Đang tắt</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex justify-end gap-3">
                                        @if ($canManage)
                                            <a href="{{ route('menu.categories.edit', $row) }}" class="font-medium text-indigo-700 hover:underline">Sửa</a>
                                            <form method="POST" action="{{ route('menu.categories.destroy', $row) }}" onsubmit="return confirm('Xóa danh mục này?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-medium text-red-700 hover:underline">Xóa</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Chưa có danh mục.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
