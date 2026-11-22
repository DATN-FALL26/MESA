@extends('layouts.auth')

@section('title', 'Danh mục thực đơn - MESA Operations')
@section('heading', 'Danh mục thực đơn')

@section('content')
    @php
        $canManage = auth()->user()->hasPermission('menu.manage');
        $hasFilters = $filters['search'] !== '' || $filters['status'] !== '';
        $createConfig = [
            'action' => route('menu.categories.store'),
            'title' => 'Thêm danh mục mới',
            'submit' => 'Lưu danh mục',
            'values' => ['is_active' => true, 'sort_order' => 0],
        ];
    @endphp

    <div class="space-y-6">
        <x-menu.page-header title="Danh mục thực đơn"
            :description="$stats['all'].' danh mục, '.$stats['active'].' đang hoạt động. Nhóm món theo 2 cấp (cha - con) để thu ngân và khách tìm món nhanh hơn.'">
            <x-slot:actions>
                @if ($canManage)
                    <x-menu.button data-modal-open="category-modal" data-config="{{ json_encode($createConfig, JSON_UNESCAPED_UNICODE) }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span>Thêm danh mục</span>
                    </x-menu.button>
                @endif
            </x-slot:actions>
        </x-menu.page-header>

        <!-- SEARCH & FILTERS -->
        <form method="GET" action="{{ route('menu.categories.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 bg-white p-4 rounded-xl border border-slate-200">
            <div class="md:col-span-2 relative">
                <input type="search" name="q" value="{{ $filters['search'] }}" placeholder="Tìm theo tên danh mục..." autocomplete="off"
                    class="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>
            <div>
                <select name="status" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:border-emerald-600">
                    <option value="">-- Trạng thái --</option>
                    <option value="active" @selected($filters['status'] === 'active')>Đang hoạt động</option>
                    <option value="inactive" @selected($filters['status'] === 'inactive')>Đang tắt</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 text-xs font-semibold bg-slate-800 hover:bg-slate-900 text-white rounded-lg transition-colors shrink-0">Lọc</button>
                @if ($hasFilters)
                    <a href="{{ route('menu.categories.index') }}" class="px-3 py-2 text-xs text-slate-600 hover:text-slate-900 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors shrink-0">Làm mới</a>
                @endif
            </div>
        </form>

        <!-- TABLE CONTAINER -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider font-semibold">
                            <th class="py-3 px-4">Tên danh mục</th>
                            <th class="py-3 px-4">Danh mục cha</th>
                            <th class="py-3 px-4 text-center">Thứ tự</th>
                            <th class="py-3 px-4 text-center">Số món</th>
                            <th class="py-3 px-4 text-center">Trạng thái</th>
                            @if ($canManage)
                                <th class="py-3 px-4 text-right">Thao tác</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($categories as $category)
                            @php
                                $blocked = $category->menu_items_count > 0 || $category->children_count > 0;
                                $blockedReason = $category->children_count > 0
                                    ? 'Danh mục còn danh mục con, hãy xóa danh mục con trước.'
                                    : 'Danh mục còn món, hãy tắt hoạt động thay vì xóa.';
                                $editConfig = [
                                    'action' => route('menu.categories.update', $category),
                                    'method' => 'PUT',
                                    'title' => 'Chỉnh sửa danh mục',
                                    'submit' => 'Lưu thay đổi',
                                    'values' => [
                                        'name' => $category->name,
                                        'parent_id' => $category->parent_id,
                                        'sort_order' => $category->sort_order,
                                        'is_active' => $category->is_active,
                                    ],
                                    'disable' => ['parent_id' => [$category->id]],
                                ];
                                $deleteConfig = [
                                    'action' => route('menu.categories.destroy', $category),
                                    'method' => 'DELETE',
                                    'title' => 'Xóa danh mục',
                                    'subtitle' => 'Chỉ xóa được khi danh mục còn trống.',
                                    'message' => 'Bạn có chắc chắn muốn xóa danh mục "'.$category->name.'"?',
                                    'confirm' => 'Xác nhận xóa',
                                ];
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4">
                                    @if ($category->parent_id)
                                        <span class="text-slate-300 ml-2 mr-1.5" aria-hidden="true">└</span><span class="font-medium text-slate-800">{{ $category->name }}</span>
                                    @else
                                        <span class="font-semibold text-slate-900">{{ $category->name }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-slate-500">{{ $category->parent?->name ?? '—' }}</td>
                                <td class="py-3 px-4 text-center tabular-nums">{{ $category->sort_order }}</td>
                                <td class="py-3 px-4 text-center tabular-nums">
                                    @if ($category->menu_items_count > 0 && \Illuminate\Support\Facades\Route::has('menu.items.index'))
                                        <a href="{{ route('menu.items.index', ['category_id' => $category->id]) }}" class="font-medium text-emerald-700 hover:underline">{{ $category->menu_items_count }}</a>
                                    @else
                                        {{ $category->menu_items_count }}
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <x-menu.badge :tone="$category->is_active ? 'success' : 'neutral'">{{ $category->is_active ? 'Hoạt động' : 'Đang tắt' }}</x-menu.badge>
                                </td>
                                @if ($canManage)
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <button type="button" data-modal-open="category-modal" data-config="{{ json_encode($editConfig, JSON_UNESCAPED_UNICODE) }}"
                                            class="px-2 py-1 text-slate-600 hover:text-emerald-700 font-medium hover:underline">Sửa</button>
                                        @if ($blocked)
                                            <span class="px-2 py-1 text-slate-300 font-medium cursor-not-allowed" title="{{ $blockedReason }}">Xóa</span>
                                        @else
                                            <button type="button" data-confirm="{{ json_encode($deleteConfig, JSON_UNESCAPED_UNICODE) }}"
                                                class="px-2 py-1 text-slate-500 hover:text-rose-600 font-medium hover:underline">Xóa</button>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <x-menu.empty-row :colspan="$canManage ? 6 : 5"
                                :message="$hasFilters ? 'Không tìm thấy danh mục nào phù hợp với bộ lọc.' : 'Chưa có danh mục nào. Hãy tạo danh mục đầu tiên, ví dụ Phở hoặc Đồ uống.'" />
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 text-xs text-slate-600">Hiển thị {{ $categories->count() }} / {{ $stats['all'] }} danh mục</div>
        </div>
    </div>

    @if ($canManage)
        <!-- MODAL THÊM / SỬA DANH MỤC -->
        <x-menu.modal id="category-modal" title="Thêm danh mục mới">
            <x-menu.modal-form modal="category-modal" :action="route('menu.categories.store')" submit="Lưu danh mục">
                <x-menu.field label="Tên danh mục" name="name" modal="category-modal" required>
                    <x-menu.input name="name" modal="category-modal" required maxlength="100" placeholder="VD: Phở bò, Đồ uống" autocomplete="off" />
                </x-menu.field>

                <x-menu.field label="Danh mục cha" name="parent_id" modal="category-modal" hint="Để trống nếu đây là danh mục gốc (chỉ hỗ trợ 2 cấp).">
                    <x-menu.select name="parent_id" modal="category-modal">
                        <option value="">-- Danh mục gốc --</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}" @selected((string) (old('_modal') === 'category-modal' ? old('parent_id') : '') === (string) $parent->id)>{{ $parent->name }}</option>
                        @endforeach
                    </x-menu.select>
                </x-menu.field>

                <x-menu.field label="Thứ tự hiển thị" name="sort_order" modal="category-modal" hint="Số nhỏ hiển thị trước.">
                    <x-menu.input name="sort_order" type="number" min="0" max="9999" step="1" value="0" modal="category-modal" />
                </x-menu.field>

                <x-menu.checkbox name="is_active" label="Đang hoạt động" :checked="true" modal="category-modal"
                    hint="Tắt để ẩn danh mục khỏi thực đơn mà không cần xóa." />
            </x-menu.modal-form>
        </x-menu.modal>

        <x-menu.confirm-modal />
    @endif

    <x-menu.scripts />
@endsection
