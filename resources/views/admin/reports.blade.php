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
            <button onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-sm border border-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.725 14.406v-2.481c0-1.08.872-1.954 1.953-1.954h6.644c1.08 0 1.953.874 1.953 1.954v2.481M6.725 14.406H4.85c-.966 0-1.75-.784-1.75-1.75v-3.5c0-.966.784-1.75 1.75-1.75h14.3c.966 0 1.75.784 1.75 1.75v3.5c0 .966-.784 1.75-1.75 1.75h-1.875M6.725 14.406v3.25c0 .966.784 1.75 1.75 1.75h6.644c.966 0 1.75-.784 1.75-1.75v-3.25M9.75 10.5h4.5" /></svg>
                In báo cáo
            </button>
            {{-- Đã liên kết trực tiếp với chức năng xuất tệp thực tế --}}
            <a href="{{ route('admin.reports.export') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12v4.5m4.5 4.5V3" /></svg>
                Xuất Excel
            </a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-6">
        <form method="GET" action="{{ route('admin.reports') }}" class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <span class="text-xs font-semibold text-slate-500">Chi nhánh:</span>
                <select name="branch_id" onchange="this.form.submit()"
                        class="rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 w-full sm:w-64">
                    @foreach($branches ?? [] as $branch)
                        @php
                            $bId = is_object($branch) ? ($branch->id ?? 1) : 1;
                            $bName = is_object($branch) ? ($branch->name ?? 'Chi nhánh') : 'Chi nhánh';
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
                    <input type="date" name="from_date" value="{{ $fromDate ?? '' }}"
                           class="text-xs border-0 bg-transparent text-slate-700 dark:text-slate-200 focus:ring-0">
                    <span class="text-slate-400">Đến ngày:</span>
                    <input type="date" name="to_date" value="{{ $toDate ?? '' }}"
                           class="text-xs border-0 bg-transparent text-slate-700 dark:text-slate-200 focus:ring-0">
                </div>
                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow hover:bg-indigo-500">
                    Lọc dữ liệu
                </button>
            </div>
        </form>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 pt-2">
            <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/70 p-5 shadow-sm dark:border-emerald-800/60 dark:bg-emerald-950/30">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Tổng doanh thu kỳ</span>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    </span>
                </div>
                <h3 class="mt-2 text-2xl font-extrabold text-emerald-950 dark:text-white">{{ number_format($tongDoanhThuKy ?? 0, 0, ',', '.') }} đ</h3>
            </div>

            <div class="rounded-2xl border border-indigo-200/80 bg-indigo-50/70 p-5 shadow-sm dark:border-indigo-800/60 dark:bg-indigo-950/30">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-400">Tổng sản lượng bán ra</span>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/15 text-indigo-600 dark:text-indigo-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                    </span>
                </div>
                <h3 class="mt-2 text-2xl font-extrabold text-indigo-950 dark:text-white">{{ $tongSoTo ?? 0 }} sản phẩm</h3>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">Top Món Ăn Doanh Thu Cao Trong Kỳ</h3>
        <div class="space-y-3">
            @forelse($topMonAn ?? [] as $item)
                @php
                    $dishName = is_object($item) && isset($item->dish) ? ($item->dish->name ?? 'Món ăn') : 'Món ăn';
                    $totalQty = is_object($item) ? ($item->total_qty ?? 0) : 0;
                    $totalRev = is_object($item) ? ($item->total_revenue ?? 0) : 0;
                @endphp
                <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                    <div>
                        <span class="font-bold text-sm text-slate-800 dark:text-white">{{ $dishName }}</span>
                        <div class="text-xs text-slate-500 mt-0.5">Số lượng: {{ $totalQty }} phần</div>
                    </div>
                    <div class="font-bold text-sm text-indigo-600 dark:text-indigo-400">
                        {{ number_format($totalRev, 0, ',', '.') }} đ
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">Chưa có dữ liệu giao dịch món ăn trong kỳ này.</p>
            @endforelse
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">Cảnh Báo Tồn Kho Nguyên Liệu</h3>
        <div class="space-y-3">
            @forelse($ingredients ?? [] as $ing)
                @php
                    $ingName = is_object($ing) ? ($ing->name ?? 'Nguyên liệu') : 'Nguyên liệu';
                    $ingStock = is_object($ing) ? ($ing->stock_quantity ?? 0) : 0;
                    $ingUnit = is_object($ing) ? ($ing->unit ?? 'Kg') : 'Kg';
                    $ingStatus = is_object($ing) ? ($ing->quality_status ?? 'Ổn định') : 'Ổn định';

                    $badgeColor = 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400';
                    if($ingStatus == 'Sắp hết' || $ingStatus == 'Cần nhập thêm' || $ingStatus == 'Sắp hết hạn') {
                        $badgeColor = 'bg-rose-500/10 text-rose-600 dark:text-rose-400';
                    }
                @endphp
                <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                    <div>
                        <span class="font-bold text-sm text-slate-800 dark:text-white">{{ $ingName }}</span>
                        <div class="text-xs text-slate-500 mt-0.5">Tồn kho hiện tại: {{ $ingStock }} {{ $ingUnit }}</div>
                    </div>
                    <span class="rounded-full {{ $badgeColor }} px-3 py-1 text-xs font-semibold">
                        {{ $ingStatus }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-slate-500">Chưa có dữ liệu kho nguyên liệu.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
