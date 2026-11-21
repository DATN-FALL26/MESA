@extends('layouts.auth')

@section('heading', $title ?? 'Báo cáo chi tiết kinh doanh')

@section('content')
@php
    $branchParam = ['branch_id' => $branchId ?? 1];

    $urlIngredients = \Illuminate\Support\Facades\Route::has('inventory.ingredients')
        ? route('inventory.ingredients', $branchParam)
        : url('/inventory/ingredients');

    $urlSuppliers = \Illuminate\Support\Facades\Route::has('inventory.suppliers')
        ? route('inventory.suppliers', $branchParam)
        : url('/inventory/suppliers');

    $urlStockMovements = \Illuminate\Support\Facades\Route::has('inventory.stock-movements')
        ? route('inventory.stock-movements', $branchParam)
        : url('/inventory/stock-movements');

    $urlPurchaseOrders = \Illuminate\Support\Facades\Route::has('inventory.purchase-orders')
        ? route('inventory.purchase-orders', $branchParam)
        : url('/inventory/purchase-orders');

    $urlAdjustments = \Illuminate\Support\Facades\Route::has('inventory.adjustments')
        ? route('inventory.adjustments', $branchParam)
        : url('/inventory/adjustments');
@endphp

<div class="space-y-4">
    {{-- Card Header & Bộ lọc gộp chung --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white">Báo Cáo Chi Tiết Kinh Doanh</h2>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Phân tích chuyên sâu doanh thu và kiểm soát rủi ro tồn kho</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <button type="button" onclick="window.print()"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                    </svg>
                    In báo cáo
                </button>

                <a href="{{ route('admin.reports.export', ['branch_id' => $branchId ?? 1, 'from_date' => $fromDate ?? '', 'to_date' => $toDate ?? '']) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-indigo-500">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Xuất Excel
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.reports') }}" class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 lg:flex-row lg:items-center lg:justify-between dark:border-slate-800">
            <div class="flex items-center gap-2.5">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Chi nhánh:</span>
                <select name="branch_id"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    @foreach($branches ?? [] as $branch)
                        @php
                            $bId = data_get($branch, 'id', 1);
                            $bName = data_get($branch, 'name', 'Chi nhánh');
                        @endphp
                        <option value="{{ $bId }}" {{ (isset($branchId) && $branchId == $bId) ? 'selected' : '' }}>
                            {{ $bName }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-slate-500 dark:text-slate-400">Từ ngày:</span>
                <input type="date" name="from_date" value="{{ $fromDate ?? '' }}"
                       class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">

                <span class="text-xs text-slate-500 dark:text-slate-400">Đến ngày:</span>
                <input type="date" name="to_date" value="{{ $toDate ?? '' }}"
                       class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">

                <button type="submit"
                        class="rounded-lg bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">
                    Lọc dữ liệu
                </button>
            </div>
        </form>

        {{-- 2 Ô tổng kết nằm ngang bên trong card --}}
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-slate-200/80 bg-white px-5 py-4 dark:border-slate-800 dark:bg-slate-900/60">
                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Tổng doanh thu kỳ</div>
                <div class="mt-1.5 text-xl font-extrabold text-slate-900 dark:text-white">
                    {{ number_format((float) ($tongDoanhThuKy ?? 0), 0, ',', '.') }} đ
                </div>
            </div>

            <div class="rounded-xl border border-slate-200/80 bg-white px-5 py-4 dark:border-slate-800 dark:bg-slate-900/60">
                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Tổng số lượng bán ra</div>
                <div class="mt-1.5 text-xl font-extrabold text-slate-900 dark:text-white">
                    {{ $tongSoTo ?? 0 }} sản phẩm
                </div>
            </div>
        </div>
    </div>

    {{-- Tầng 2: Top món ăn doanh thu cao trong kỳ --}}
    <section class="w-full rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Top Món Ăn Doanh Thu Cao Trong Kỳ</h3>
            <p class="mt-0.5 text-xs text-slate-400">Danh sách món ăn trong kỳ (từ {{ $fromDate ?? '' }} đến {{ $toDate ?? '' }})</p>
        </div>

        <div class="mt-3.5 divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($topMonAn ?? [] as $mon)
                <div class="flex items-center justify-between py-3">
                    <div>
                        <div class="text-xs font-bold text-slate-800 dark:text-white">{{ data_get($mon, 'dish.name', 'Món ăn') }}</div>
                        <div class="mt-0.5 text-[11px] text-slate-400">Số lượng: {{ data_get($mon, 'total_qty', 0) }} phần</div>
                    </div>
                    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">
                        {{ number_format((float) data_get($mon, 'total_revenue', 0), 0, ',', '.') }} đ
                    </span>
                </div>
            @empty
                <div class="py-4 text-center text-xs text-slate-400">Chưa có dữ liệu món ăn trong kỳ.</div>
            @endforelse
        </div>
    </section>

    {{-- Tầng 3: Cảnh báo tồn kho nguyên liệu & Liên kết phân hệ Kho --}}
    <section class="w-full rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Cảnh Báo Tồn Kho Nguyên Liệu</h3>
                <p class="mt-0.5 text-xs text-slate-400">Chuyển nhanh sang các chức năng quản lý kho</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                <a href="{{ $urlIngredients }}"
                   class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-slate-700 hover:border-indigo-300 hover:bg-indigo-50/60 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    Nguyên vật liệu
                </a>
                <a href="{{ $urlSuppliers }}"
                   class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-slate-700 hover:border-indigo-300 hover:bg-indigo-50/60 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    Nhà cung cấp
                </a>
                <a href="{{ $urlStockMovements }}"
                   class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-slate-700 hover:border-indigo-300 hover:bg-indigo-50/60 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    Thẻ kho & Biến động
                </a>
                <a href="{{ $urlPurchaseOrders }}"
                   class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-slate-700 hover:border-indigo-300 hover:bg-indigo-50/60 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    Đơn nhập hàng (PO)
                </a>
                <a href="{{ $urlAdjustments }}"
                   class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-slate-700 hover:border-indigo-300 hover:bg-indigo-50/60 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    Kiểm kê & Điều chỉnh
                </a>
            </div>
        </div>

        <div class="mt-3 space-y-2.5">
            @forelse($ingredients ?? [] as $ing)
                @php
                    $ingId = data_get($ing, 'id');
                    $ingName = data_get($ing, 'name', 'Nguyên liệu');
                    $ingQty = data_get($ing, 'stock_quantity') ?? data_get($ing, 'quantity') ?? 0;
                    $ingUnit = data_get($ing, 'unit', '');
                    $ingStatus = data_get($ing, 'quality_status') ?? data_get($ing, 'status') ?? 'Ổn định';
                    $isLow = (float) $ingQty <= 0;

                    $rowUrl = $ingId
                        ? (\Illuminate\Support\Facades\Route::has('inventory.stock-movements')
                            ? route('inventory.stock-movements', ['branch_id' => $branchId ?? 1, 'ingredient_id' => $ingId])
                            : $urlStockMovements)
                        : $urlStockMovements;
                @endphp
                <div class="flex flex-col gap-2 rounded-xl border border-slate-100 bg-slate-50/60 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800 dark:bg-slate-800/40">
                    <a href="{{ $rowUrl }}" class="min-w-0 flex-1 group">
                        <div class="text-xs font-bold text-slate-800 group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-400">
                            {{ $ingName }}
                        </div>
                        <div class="mt-0.5 text-[11px] text-slate-400">
                            Tồn kho hiện tại: {{ $ingQty }}{{ $ingUnit ? ' ' . $ingUnit : '' }}
                        </div>
                    </a>

                    <div class="flex flex-wrap items-center gap-3 text-xs">
                        <span class="font-semibold {{ $isLow ? 'text-amber-600 dark:text-amber-400' : 'text-amber-500 dark:text-amber-400' }}">
                            {{ $ingStatus }}
                        </span>
                        <a href="{{ $urlAdjustments }}" class="font-semibold text-slate-600 hover:underline dark:text-slate-300">
                            Kiểm kê
                        </a>
                    </div>
                </div>
            @empty
                <div class="py-4 text-center text-xs text-slate-400">Chưa có dữ liệu nguyên liệu.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
