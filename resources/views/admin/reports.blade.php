@extends('layouts.auth')

@section('heading', $title ?? 'Báo cáo chi tiết kinh doanh')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">Báo Cáo Chi Tiết Kinh Doanh</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Phân tích chuyên sâu doanh thu và kiểm soát số liệu kỳ tới.</p>
        </div>

        <div class="flex items-center gap-3">
            <button onclick="window.print()" type="button" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-sm border border-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.725 14.406v-2.481c0-1.08.872-1.954 1.953-1.954h6.644c1.08 0 1.953.874 1.953 1.954v2.481M6.725 14.406H4.85c-.966 0-1.75-.784-1.75-1.75v-3.5c0-.966.784-1.75 1.75-1.75h14.3c.966 0 1.75.784 1.75 1.75v3.5c0 .966-.784 1.75-1.75 1.75h-1.875M6.725 14.406v3.25c0 .966.784 1.75 1.75 1.75h6.644c.966 0 1.75-.784 1.75-1.75v-3.25M9.75 10.5h4.5" /></svg>
                In báo cáo
            </button>
            <a href="{{ route('admin.reports.export') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12v4.5m4.5 4.5V3" /></svg>
                Xuất Excel
            </a>
        </div>
    </div>

    {{-- Bộ lọc thời gian & chi nhánh --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-6">
        <form method="GET" action="{{ route('admin.reports') }}" class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <span class="text-xs font-semibold text-slate-500">Chi nhánh:</span>
                <select name="branch_id" onchange="this.form.submit()"
                        class="rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 w-full sm:w-64">
                    @foreach($branches ?? [] as $branch)
                        @php
                            $bId = data_get($branch, 'id');
                            $bName = data_get($branch, 'name', 'Chi nhánh');
                        @endphp
                        <option value="{{ $bId }}" {{ (isset($branchId) && $branchId == $bId) ? 'selected' : '' }}>
                            {{ $bName }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800 p-1.5 rounded-xl border border-slate-300 dark:border-slate-700">
                    <span class="text-xs text-slate-400 pl-2">Từ ngày:</span>
                    <input type="date" name="from_date" value="{{ $fromDate ?? '' }}" class="text-xs border-0 bg-transparent text-slate-700 dark:text-slate-200 focus:ring-0">
                    <span class="text-slate-400">Đến ngày:</span>
                    <input type="date" name="to_date" value="{{ $toDate ?? '' }}" class="text-xs border-0 bg-transparent text-slate-700 dark:text-slate-200 focus:ring-0">
                </div>
                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow hover:bg-indigo-500">
                    Lọc dữ liệu
                </button>
            </div>
        </form>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 pt-2">
            <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/70 p-5 shadow-sm dark:border-emerald-800/60 dark:bg-emerald-950/30">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Tổng doanh thu kỳ</span>
                <h3 class="mt-2 text-2xl font-extrabold text-emerald-950 dark:text-white">{{ number_format((float) ($tongDoanhThuKy ?? 0), 0, ',', '.') }} đ</h3>
            </div>

            <div class="rounded-2xl border border-indigo-200/80 bg-indigo-50/70 p-5 shadow-sm dark:border-indigo-800/60 dark:bg-indigo-950/30">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-400">Tổng sản lượng bán ra</span>
                <h3 class="mt-2 text-2xl font-extrabold text-indigo-950 dark:text-white">{{ $tongSoTo ?? 0 }} sản phẩm</h3>
            </div>
        </div>
    </div>

    {{-- Top Món Ăn Doanh Thu Cao --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">Top Món Ăn Doanh Thu Cao Trong Kỳ</h3>
        <div class="space-y-3">
            @forelse($topMonAn ?? [] as $item)
                @php
                    $dishName = data_get($item, 'dish.name', data_get($item, 'name', 'Món ăn'));
                    $totalQty = data_get($item, 'total_qty', 0);
                    $totalRevenue = (float) data_get($item, 'total_revenue', 0);
                @endphp
                <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                    <div>
                        <span class="font-bold text-sm text-slate-800 dark:text-white">{{ $dishName }}</span>
                        <div class="text-xs text-slate-500 mt-0.5">Số lượng: {{ $totalQty }} phần</div>
                    </div>
                    <div class="font-bold text-sm text-indigo-600 dark:text-indigo-400">
                        {{ number_format($totalRevenue, 0, ',', '.') }} đ
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500 py-4 text-center">Chưa có dữ liệu giao dịch món ăn trong kỳ này.</p>
            @endforelse
        </div>
    </div>

    {{-- BẢNG QUẢN LÝ / CẢNH BÁO TỒN KHO NGUYÊN LIỆU --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Bảng Kiểm Soát & Cảnh Báo Tồn Kho Nguyên Liệu</h3>
                <p class="text-xs text-slate-500 mt-0.5">Theo dõi trạng thái tồn kho thực tế tại kho chi nhánh.</p>
            </div>

            {{-- Nút dẫn đến Thẻ kho & Biến động --}}
            <a href="{{ url('/inventory/stock-movements') }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm border border-slate-200 hover:bg-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-700 transition-colors">
                <svg class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h16.5M3.75 3v11.25M20.25 3v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-12 0h12m-12 0v3.75A1.5 1.5 0 0 0 5.25 21.75h13.5a1.5 1.5 0 0 0 1.5-1.5V16.5m-15 0h15" /></svg>
                Xem Thẻ kho & Biến động &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:border-slate-800 dark:text-slate-400">
                        <th class="py-3.5 px-6">Mã SKU</th>
                        <th class="py-3.5 px-6">Tên Nguyên Vật Liệu</th>
                        <th class="py-3.5 px-6">Phân Loại</th>
                        <th class="py-3.5 px-6">Đơn Vị Cơ Sở (UOM)</th>
                        <th class="py-3.5 px-6">Tồn Kho Hiện Tại</th>
                        <th class="py-3.5 px-6">Trạng Thái</th>
                        <th class="py-3.5 px-6 text-right">Thao Tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700 dark:divide-slate-800 dark:text-slate-300">
                    @forelse($ingredients ?? [] as $index => $ing)
                        @php
                            $name = (string) data_get($ing, 'name', 'Nguyên liệu');
                            $sku = (string) (data_get($ing, 'sku') ?: ('NL_' . strtoupper(\Illuminate\Support\Str::slug($name, '_'))));
                            $stock = data_get($ing, 'stock_quantity', 0);
                            $status = (string) data_get($ing, 'quality_status', 'Ổn định');

                            $nameLower = mb_strtolower($name);

                            $category = 'Thô (raw)';
                            $categoryBg = 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';

                            if (str_contains($nameLower, 'nước dùng') || str_contains($nameLower, 'nước lèo') || str_contains($nameLower, 'sốt')) {
                                $category = 'Sơ chế (semi_finished)';
                                $categoryBg = 'bg-sky-500/10 text-sky-600 dark:bg-sky-500/20 dark:text-sky-400';
                            }

                            $unit = (string) (data_get($ing, 'unit') ?: 'Kg');
                            if (str_contains($nameLower, 'nước dùng')) {
                                $unit = 'ml';
                            } elseif (str_contains($nameLower, 'nước ngọt') || str_contains($nameLower, 'lon')) {
                                $unit = 'lon';
                            } elseif (str_contains($nameLower, 'quẩy')) {
                                $unit = 'cái';
                            } elseif (str_contains($nameLower, 'trà')) {
                                $unit = 'g';
                            } elseif (str_contains($nameLower, 'bánh phở') || str_contains($nameLower, 'thịt') || str_contains($nameLower, 'xương') || str_contains($nameLower, 'hành') || str_contains($nameLower, 'rau')) {
                                $unit = 'Kg';
                            }

                            $badgeClass = 'bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400';
                            if ($status === 'Sắp hết' || $status === 'Cần nhập thêm') {
                                $badgeClass = 'bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400';
                            }
                        @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="py-4 px-6 font-semibold text-slate-900 dark:text-white">{{ $sku }}</td>
                            <td class="py-4 px-6 font-bold text-slate-900 dark:text-white">{{ $name }}</td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-semibold {{ $categoryBg }}">
                                    {{ $category }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-slate-500 font-semibold">{{ $unit }}</td>
                            <td class="py-4 px-6 font-bold text-indigo-600 dark:text-indigo-400">{{ $stock }}</td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $badgeClass }}">
                                    {{ $status }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right relative">
                                <div class="inline-block text-left">
                                    <button onclick="toggleActionMenu({{ $index }})" type="button" class="inline-flex items-center gap-1 font-semibold text-indigo-600 dark:text-indigo-400 hover:underline focus:outline-none">
                                        Xem
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                                    </button>

                                    <div id="actionMenu-{{ $index }}" class="hidden absolute right-6 z-20 mt-2 w-48 origin-top-right rounded-xl bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-slate-800 dark:ring-slate-700 py-1 text-left">
                                        <a href="{{ url('/inventory/suppliers') }}" class="block px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700">
                                            📦 Xem nhà cung cấp
                                        </a>
                                        <a href="{{ url('/inventory/purchase-orders') }}" class="block px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700">
                                            📋 Xem đơn nhập hàng
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 italic">Chưa có dữ liệu kho nguyên liệu trong cơ sở dữ liệu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleActionMenu(index) {
        const menu = document.getElementById(`actionMenu-${index}`);
        const allMenus = document.querySelectorAll('[id^="actionMenu-"]');

        allMenus.forEach(m => {
            if (m.id !== `actionMenu-${index}`) {
                m.classList.add('hidden');
            }
        });

        if (menu) {
            menu.classList.toggle('hidden');
        }
    }

    window.addEventListener('click', function(e) {
        if (!e.target.closest('button[onclick^="toggleActionMenu"]')) {
            document.querySelectorAll('[id^="actionMenu-"]').forEach(m => {
                m.classList.add('hidden');
            });
        }
    });
</script>
@endpush
@endsection
