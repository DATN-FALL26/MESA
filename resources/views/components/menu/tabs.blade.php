@props(['active'])
@php
    // Chỉ hiện tab của trang đã được cài (route tồn tại), để chia nhỏ commit vẫn chạy ổn.
    $tabs = collect([
        ['items', 'Món ăn', 'menu.items.index'],
        ['categories', 'Danh mục', 'menu.categories.index'],
        ['groups', 'Nhóm topping', 'menu.modifier-groups.index'],
        ['availability', 'Còn hàng & giá chi nhánh', 'menu.availability.index'],
    ])->filter(fn (array $tab): bool => \Illuminate\Support\Facades\Route::has($tab[2]));
@endphp
<nav class="mb-6 flex gap-1 overflow-x-auto border-b border-slate-200" aria-label="Các mục thực đơn">
    @foreach ($tabs as [$key, $label, $route])
        <a href="{{ route($route) }}"
            @class([
                '-mb-px whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-indigo-500',
                'border-indigo-600 text-indigo-700' => $active === $key,
                'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800' => $active !== $key,
            ])
            @if ($active === $key) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
