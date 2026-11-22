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

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 pt-2">
            {{-- Card 1: Doanh thu --}}
            <div class="rounded-2xl border border-indigo-200/80 bg-indigo-50/70 p-5 shadow-sm dark:border-indigo-800/60 dark:bg-indigo-950/30">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-400">Doanh thu kỳ</span>
                    <span class="rounded-full bg-indigo-200/60 px-2 py-0.5 text-[10px] font-bold text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">{{ $tongSoTo ?? 0 }} món</span>
                </div>
                <h3 class="mt-2 text-2xl font-extrabold text-indigo-950 dark:text-white">{{ number_format((float) ($tongDoanhThuKy ?? 0), 0, ',', '.') }} đ</h3>
                <p class="mt-1 text-[11px] text-indigo-600/80 dark:text-indigo-300/80">Tổng doanh thu bán hàng thực tế</p>
            </div>

            {{-- Card 2: Giá vốn hàng bán (COGS) --}}
            <div class="rounded-2xl border border-amber-200/80 bg-amber-50/70 p-5 shadow-sm dark:border-amber-800/60 dark:bg-amber-950/30">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">Giá vốn xuất bán (COGS)</span>
                    <span class="rounded-full bg-amber-200/60 px-2 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-900 dark:text-amber-200">Kho NVL</span>
                </div>
                <h3 class="mt-2 text-2xl font-extrabold text-amber-950 dark:text-white">{{ number_format((float) ($tongGiaVonKy ?? 0), 0, ',', '.') }} đ</h3>
                <p class="mt-1 text-[11px] text-amber-600/80 dark:text-amber-300/80">Chi phí NVL tiêu hao theo định lượng</p>
            </div>

            {{-- Card 3: Lợi nhuận gộp --}}
            <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/70 p-5 shadow-sm dark:border-emerald-800/60 dark:bg-emerald-950/30">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Lợi nhuận gộp (Lãi)</span>
                    <span class="rounded-full bg-emerald-200/60 px-2 py-0.5 text-[10px] font-bold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">Biên {{ $marginPercentage ?? 0 }}%</span>
                </div>
                <h3 class="mt-2 text-2xl font-extrabold text-emerald-950 dark:text-white">{{ number_format((float) ($tongLoiNhuanKy ?? 0), 0, ',', '.') }} đ</h3>
                <p class="mt-1 text-[11px] text-emerald-600/80 dark:text-emerald-300/80">Doanh thu trừ giá vốn nguyên liệu</p>
            </div>

            {{-- Card 4: Tỷ lệ Food Cost % --}}
            <div class="rounded-2xl border border-slate-200/80 bg-slate-50 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-800/40">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Tỷ lệ Food Cost</span>
                    @php
                        $fc = (float) ($foodCostPercentage ?? 0);
                        $badgeColor = $fc <= 35 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : ($fc <= 45 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300');
                        $badgeText = $fc <= 35 ? 'Lý tưởng' : ($fc <= 45 ? 'Chấp nhận' : 'Cao');
                    @endphp
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $badgeColor }}">{{ $badgeText }}</span>
                </div>
                <h3 class="mt-2 text-2xl font-extrabold text-slate-900 dark:text-white">{{ $foodCostPercentage ?? 0 }}%</h3>
                <p class="mt-1 text-[11px] text-slate-500">Mục tiêu chuẩn F&B: 30% - 35%</p>
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
                    $totalCogs = (float) data_get($item, 'total_cogs', 0);
                    $totalProfit = (float) data_get($item, 'total_profit', 0);
                @endphp
                <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                    <div>
                        <span class="font-bold text-sm text-slate-800 dark:text-white">{{ $dishName }}</span>
                        <div class="text-xs text-slate-500 mt-0.5">Số lượng: {{ $totalQty }} phần</div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-sm text-indigo-600 dark:text-indigo-400">
                            {{ number_format($totalRevenue, 0, ',', '.') }} đ
                        </div>
                        @if($totalCogs > 0)
                            <div class="text-[11px] text-slate-500">
                                Vốn: {{ number_format($totalCogs, 0, ',', '.') }} đ · <span class="text-emerald-600 font-semibold">Lãi: {{ number_format($totalProfit, 0, ',', '.') }} đ</span>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500 py-4 text-center">Chưa có dữ liệu giao dịch món ăn trong kỳ này.</p>
            @endforelse
        </div>
    </div>

    {{-- BẢNG QUẢN LÝ / CẢNH BÁO TỒN KHO NGUYÊN LIỆU & HẠN SỬ DỤNG --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 overflow-hidden space-y-4 p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-5">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span>Kiểm Soát Tồn Kho & Cảnh Báo Hạn Sử Dụng</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">Thời gian thực</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Theo dõi số lượng tồn thực tế theo kho chi nhánh, mức cảnh báo tối thiểu và hạn dùng của các lô hàng.</p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" onclick="triggerStockScan()" id="btn-scan-alerts" class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition-colors border border-slate-200 dark:border-slate-700">
                    <svg class="h-4 w-4 text-emerald-600" id="scan-icon" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                    <span>Quét cảnh báo kho</span>
                </button>
                <a href="{{ url('/inventory/stock-movements') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950 dark:text-indigo-300 dark:hover:bg-indigo-900 transition-colors border border-indigo-200 dark:border-indigo-800">
                    <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h16.5M3.75 3v11.25M20.25 3v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-12 0h12m-12 0v3.75A1.5 1.5 0 0 0 5.25 21.75h13.5a1.5 1.5 0 0 0 1.5-1.5V16.5m-15 0h15" /></svg>
                    <span>Thẻ kho</span>
                </a>
                <a href="{{ url('/inventory/purchase-orders') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-emerald-700 shadow-sm transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    <span>Tạo đơn nhập (PO)</span>
                </a>
            </div>
        </div>

        {{-- THỐNG KÊ NHANH (KPI CARDS) --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                <span class="text-[11px] font-semibold text-slate-500 uppercase">Tổng mặt hàng</span>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="text-xl font-black text-slate-900 dark:text-white font-mono">{{ $alertSummary['total_ingredients'] ?? count($ingredients) }}</span>
                    <span class="text-[11px] text-slate-400">nguyên liệu</span>
                </div>
            </div>

            <div class="rounded-xl border border-rose-200 bg-rose-50/50 p-3.5 dark:border-rose-900/40 dark:bg-rose-950/20">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-semibold text-rose-700 dark:text-rose-400 uppercase">Cần nhập gấp</span>
                    <span class="h-2 w-2 rounded-full bg-rose-500 animate-pulse"></span>
                </div>
                <div class="mt-1 flex items-baseline gap-1.5">
                    <span class="text-xl font-black text-rose-600 dark:text-rose-400 font-mono">{{ ($alertSummary['out_of_stock_count'] ?? 0) + ($alertSummary['low_stock_count'] ?? 0) }}</span>
                    <span class="text-[10px] text-rose-600/80">({{ $alertSummary['out_of_stock_count'] ?? 0 }} hết / {{ $alertSummary['low_stock_count'] ?? 0 }} sắp hết)</span>
                </div>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-3.5 dark:border-amber-900/40 dark:bg-amber-950/20">
                <span class="text-[11px] font-semibold text-amber-700 dark:text-amber-400 uppercase">Lô sắp hết hạn (&le; 3 ngày)</span>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="text-xl font-black text-amber-600 dark:text-amber-400 font-mono">{{ $alertSummary['expiring_soon_batches_count'] ?? 0 }}</span>
                    <span class="text-[11px] text-amber-600/80">lô cần ưu tiên</span>
                </div>
            </div>

            <div class="rounded-xl border border-red-200 bg-red-50/50 p-3.5 dark:border-red-900/40 dark:bg-red-950/20">
                <span class="text-[11px] font-semibold text-red-700 dark:text-red-400 uppercase">Lô đã quá hạn</span>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="text-xl font-black text-red-600 dark:text-red-400 font-mono">{{ $alertSummary['expired_batches_count'] ?? 0 }}</span>
                    <span class="text-[11px] text-red-600/80">yêu cầu hủy</span>
                </div>
            </div>
        </div>

        {{-- BỘ LỌC TÌM KIẾM & TRẠNG THÁI --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
            <div class="flex flex-wrap items-center gap-1.5 w-full sm:w-auto">
                <a href="{{ request()->fullUrlWithQuery(['status_filter' => null]) }}" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors {{ !request('status_filter') ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300' }}">
                    Tất cả ({{ count($ingredients) }})
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status_filter' => 'out_of_stock']) }}" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors {{ request('status_filter') === 'out_of_stock' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300' }}">
                    Đã hết hàng
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status_filter' => 'low_stock']) }}" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors {{ request('status_filter') === 'low_stock' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300' }}">
                    Sắp hết hàng
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status_filter' => 'expiring_soon']) }}" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors {{ request('status_filter') === 'expiring_soon' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300' }}">
                    Sắp hết hạn
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status_filter' => 'expired']) }}" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors {{ request('status_filter') === 'expired' ? 'bg-red-700 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-950/40 dark:text-red-300' }}">
                    Đã hết hạn
                </a>
            </div>

            <form method="GET" action="{{ url()->current() }}" class="flex items-center gap-2 w-full sm:w-auto">
                <input type="hidden" name="branch_id" value="{{ $branchId }}">
                <input type="hidden" name="from_date" value="{{ $fromDate }}">
                <input type="hidden" name="to_date" value="{{ $toDate }}">
                @if(request('status_filter'))
                    <input type="hidden" name="status_filter" value="{{ request('status_filter') }}">
                @endif
                <div class="relative w-full sm:w-64">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm theo tên hoặc SKU..." class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:outline-none focus:border-indigo-500">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                </div>
                <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white hover:bg-slate-900 text-xs font-semibold rounded-lg transition-colors shrink-0 dark:bg-slate-700">Lọc</button>
            </form>
        </div>

        {{-- BẢNG DỮ LIỆU TỒN KHO THỰC TẾ --}}
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:border-slate-800 dark:text-slate-400">
                        <th class="py-3 px-4">Mã SKU</th>
                        <th class="py-3 px-4">Tên Nguyên Vật Liệu</th>
                        <th class="py-3 px-4">Phân Loại</th>
                        <th class="py-3 px-4 text-right">Tồn tối thiểu</th>
                        <th class="py-3 px-4 text-right">Tồn thực tế</th>
                        <th class="py-3 px-4">HSD gần nhất</th>
                        <th class="py-3 px-4 text-center">Trạng Thái</th>
                        <th class="py-3 px-4 text-right">Thao Tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700 dark:divide-slate-800 dark:text-slate-300">
                    @forelse($ingredients ?? [] as $index => $ing)
                        @php
                            $qty = (float) ($ing->stock_quantity ?? 0);
                            $min = (float) ($ing->min_stock ?? 0);
                            $uom = $ing->unit ?? 'g';
                            $status = $ing->quality_status ?? 'Ổn định';
                            $statusClass = $ing->status_class ?? 'success';

                            $typeLabel = ($ing->ingredient_type ?? 'raw') === 'semi_finished' ? 'Sơ chế' : 'Thô';
                            $typeBg = ($ing->ingredient_type ?? 'raw') === 'semi_finished'
                                ? 'bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300'
                                : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
                        @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors {{ $qty <= 0 ? 'bg-rose-50/20' : '' }}">
                            <td class="py-3 px-4 font-mono font-semibold text-slate-900 dark:text-white">{{ $ing->sku }}</td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $ing->name }}</div>
                                @if(($ing->active_batches_count ?? 0) > 0)
                                    <span class="text-[10px] text-slate-400">{{ $ing->active_batches_count }} lô đang lưu kho</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold {{ $typeBg }}">
                                    {{ $typeLabel }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right text-slate-500 font-mono">
                                {{ number_format($min, 2) }} <span class="text-[10px] text-slate-400">{{ $uom }}</span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="font-black font-mono {{ $qty <= 0 ? 'text-rose-600 dark:text-rose-400' : ($qty <= $min ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-700 dark:text-emerald-400') }}">
                                    {{ number_format($qty, 2) }} <span class="text-[10px] font-normal text-slate-400">{{ $uom }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                @if(!empty($ing->expiry_date))
                                    @php
                                        $expDate = \Carbon\Carbon::parse($ing->expiry_date);
                                        $daysLeft = (int) \Carbon\Carbon::today()->diffInDays($expDate, false);
                                    @endphp
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono text-[11px] {{ $daysLeft < 0 ? 'text-red-600 font-bold' : ($daysLeft <= 3 ? 'text-amber-600 font-bold' : 'text-slate-600 dark:text-slate-400') }}">
                                            {{ $ing->expiry_date }}
                                        </span>
                                        @if($daysLeft < 0)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300">Hết hạn</span>
                                        @elseif($daysLeft <= 3)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">{{ $daysLeft }} ngày</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-slate-400 text-[11px] italic">Chưa có lô</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($status === 'Hết hàng' || $status === 'Đã hết hạn')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300 ring-1 ring-rose-500/20">
                                        ⚠️ {{ $status }}
                                    </span>
                                @elseif($status === 'Sắp hết' || $status === 'Sắp hết hạn')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 ring-1 ring-amber-500/20">
                                        ⚡ {{ $status }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 ring-1 ring-emerald-500/20">
                                        ✅ {{ $status }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <a href="{{ url('/inventory/stock-movements') }}?ingredient_id={{ $ing->id }}" class="px-2 py-1 text-[11px] font-semibold text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded transition-colors" title="Tra cứu thẻ kho">
                                        Thẻ kho
                                    </a>
                                    @if($qty <= $min)
                                        <a href="{{ url('/inventory/purchase-orders') }}" class="px-2 py-1 text-[11px] font-bold text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40 rounded transition-colors" title="Nhập hàng ngay">
                                            + Nhập
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-slate-400 italic">
                                Không tìm thấy dữ liệu nguyên liệu phù hợp với bộ lọc.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

@push('scripts')
{{-- FLOATING TOAST NOTIFICATION --}}
<div id="toast-notification" class="fixed top-5 right-5 z-50 transform transition-all duration-300 translate-y-[-20px] opacity-0 pointer-events-none">
    <div class="flex items-center gap-3 bg-slate-900 text-white dark:bg-white dark:text-slate-900 px-4 py-3 rounded-xl shadow-2xl border border-slate-700/30 text-xs">
        <div id="toast-icon" class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
        </div>
        <div>
            <div id="toast-title" class="font-bold text-sm">Quét kho hoàn tất</div>
            <div id="toast-msg" class="text-slate-300 dark:text-slate-600 mt-0.5">Hệ thống đã cập nhật cảnh báo tồn kho và HSD.</div>
        </div>
    </div>
</div>

<script>
    function showToast(title, msg, isError = false) {
        const toast = document.getElementById('toast-notification');
        const titleEl = document.getElementById('toast-title');
        const msgEl = document.getElementById('toast-msg');
        const iconEl = document.getElementById('toast-icon');

        titleEl.innerText = title;
        msgEl.innerText = msg;

        if (isError) {
            iconEl.className = 'w-6 h-6 rounded-full bg-rose-500/20 text-rose-400 flex items-center justify-center shrink-0';
            iconEl.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>';
        } else {
            iconEl.className = 'w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0';
            iconEl.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>';
        }

        toast.classList.remove('translate-y-[-20px]', 'opacity-0', 'pointer-events-none');
        toast.classList.add('translate-y-0', 'opacity-100');

        setTimeout(() => {
            toast.classList.remove('translate-y-0', 'opacity-100');
            toast.classList.add('translate-y-[-20px]', 'opacity-0', 'pointer-events-none');
        }, 3500);
    }

    function triggerStockScan() {
        const btn = document.getElementById('btn-scan-alerts');
        const icon = document.getElementById('scan-icon');
        if (!btn || !icon) return;

        btn.disabled = true;
        icon.classList.add('animate-spin');

        fetch('/api/v1/inventory/alerts/scan', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ branch_id: {{ $branchId }} })
        })
        .then(res => res.json())
        .then(data => {
            const lowCount = (data.data?.low_stock?.created || 0) + (data.data?.low_stock?.updated || 0);
            const expCount = (data.data?.expiry?.created || 0) + (data.data?.expiry?.updated || 0);
            showToast('Quét kho hoàn tất!', `Đã phát hiện & ghi nhận ${lowCount} cảnh báo tồn kho và ${expCount} cảnh báo hạn sử dụng.`);
            setTimeout(() => {
                window.location.reload();
            }, 1200);
        })
        .catch(err => {
            console.error(err);
            showToast('Lỗi khi quét kho', err.message, true);
        })
        .finally(() => {
            btn.disabled = false;
            icon.classList.remove('animate-spin');
        });
    }
</script>
@endpush
@endsection
