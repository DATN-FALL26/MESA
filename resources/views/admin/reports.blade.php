@extends('layouts.auth')

@section('content')
<div class="container-fluid px-8 py-8 bg-slate-50 min-h-screen">

    <!-- HEADER CHÍNH -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8 pb-6 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 shadow-sm">
                    <i class="fas fa-chart-line text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl lg:text-3xl font-black tracking-tight text-slate-800">Báo Cáo Chi Tiết Kinh Doanh</h1>
                    <p class="text-xs lg:text-sm text-slate-500 mt-1">Phân tích chuyên sâu dòng doanh thu, hiệu suất sản phẩm và kiểm soát rủi ro kho thực phẩm</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 w-full lg:w-auto">
            <button onclick="window.print()" class="flex items-center justify-center gap-2 bg-white hover:bg-slate-50 text-slate-700 font-semibold px-4 py-2.5 rounded-xl text-sm transition border border-slate-200 shadow-sm">
                <i class="fas fa-print text-indigo-600"></i> In báo cáo
            </button>
            <button onclick="alert('Tính năng xuất file Excel báo cáo đang được tích hợp!')" class="flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2.5 rounded-xl text-sm transition shadow-sm shadow-indigo-100">
                <i class="fas fa-file-excel"></i> Xuất Excel
            </button>
        </div>
    </div>

    <!-- KHU VỰC BỘ LỌC THỜI GIAN -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm mb-8">
        <form method="GET" action="{{ route('admin.reports') }}" id="reportFilterForm" class="flex flex-col xl:flex-row justify-between items-stretch xl:items-center gap-6">

            <!-- Bộ lọc ngày tháng -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full xl:w-auto">
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center gap-2">
                        <i class="fas fa-calendar text-indigo-600"></i> Từ ngày
                    </label>
                    <input type="date" id="fromDate" name="from_date" value="{{ $fromDate ?? now()->toDateString() }}"
                           class="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 px-4 py-3 font-medium transition">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center gap-2">
                        <i class="fas fa-calendar-check text-indigo-600"></i> Đến ngày
                    </label>
                    <input type="date" id="toDate" name="to_date" value="{{ $toDate ?? now()->toDateString() }}"
                           class="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 px-4 py-3 font-medium transition">
                </div>
            </div>

            <!-- 3 Nút lọc nhanh có class định danh để chuyển đổi trạng thái (Active) -->
            <div class="flex flex-col sm:flex-row items-center justify-between xl:justify-end gap-4 w-full xl:w-auto">
                <div class="flex items-center gap-1 bg-slate-100 p-1.5 rounded-2xl border border-slate-200 w-full sm:w-auto justify-center">
                    <button type="button" id="btn-today" onclick="setDateRange('today')" class="px-4 py-2 rounded-xl text-slate-600 font-bold text-xs transition">Hôm nay</button>
                    <button type="button" id="btn-7days" onclick="setDateRange('7days')" class="px-4 py-2 rounded-xl text-slate-600 font-bold text-xs transition">7 ngày qua</button>
                    <button type="button" id="btn-month" onclick="setDateRange('month')" class="px-4 py-2 rounded-xl text-slate-600 font-bold text-xs transition">Tháng này</button>
                </div>

                <button type="submit" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-7 py-3.5 rounded-2xl text-sm transition shadow-md shadow-indigo-100 flex items-center justify-center gap-2">
                    <i class="fas fa-filter"></i> Lọc dữ liệu
                </button>
            </div>
        </form>
    </div>

    <!-- TẦNG THẺ KPI TỔNG QUAN -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-gradient-to-tr from-indigo-900 via-indigo-800 to-indigo-600 text-white p-7 rounded-3xl shadow-lg relative overflow-hidden flex justify-between items-center">
            <div class="relative z-10">
                <p class="text-indigo-200 font-semibold text-xs uppercase tracking-widest mb-1">Tổng Doanh Thu Trong Kỳ</p>
                <h3 class="text-3xl lg:text-4xl font-black tracking-tight mt-1">{{ number_format($tongDoanhThuKy ?? 0, 0, ',', '.') }} <span class="text-xl font-bold text-indigo-200">đ</span></h3>
                <div class="flex items-center gap-2 mt-3 text-xs text-indigo-200 bg-white/10 w-max px-3 py-1.5 rounded-xl backdrop-blur-md">
                    <i class="fas fa-clock text-indigo-300"></i> Kỳ báo cáo: {{ $fromDate ?? 'N/A' }} đến {{ $toDate ?? 'N/A' }}
                </div>
            </div>
            <div class="p-4 bg-white/10 rounded-2xl backdrop-blur-md text-white text-3xl shadow-inner">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

        <div class="bg-gradient-to-tr from-slate-900 via-slate-800 to-emerald-900 text-white p-7 rounded-3xl shadow-lg relative overflow-hidden flex justify-between items-center">
            <div class="relative z-10">
                <p class="text-emerald-200 font-semibold text-xs uppercase tracking-widest mb-1">Tổng Sản Lượng Bán Ra</p>
                <h3 class="text-3xl lg:text-4xl font-black tracking-tight mt-1">{{ number_format($tongSoTo ?? 0, 0, ',', '.') }} <span class="text-lg font-bold text-emerald-200">tô / sản phẩm</span></h3>
                <div class="flex items-center gap-2 mt-3 text-xs text-emerald-200 bg-white/10 w-max px-3 py-1.5 rounded-xl backdrop-blur-md">
                    <i class="fas fa-fire text-amber-400"></i> Tổng sản lượng phục vụ khách hàng
                </div>
            </div>
            <div class="p-4 bg-white/10 rounded-2xl backdrop-blur-md text-emerald-100 text-3xl shadow-inner">
                <i class="fas fa-bowl-food"></i>
            </div>
        </div>
    </div>

    <!-- TẦNG TOP MÓN ĂN BÁN CHẠY -->
    <div class="bg-white p-7 rounded-3xl border border-slate-200/80 shadow-sm mb-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h3 class="text-lg font-black text-slate-800">Top Món Ăn Bán Chạy Trong Kỳ</h3>
                <p class="text-xs text-slate-400 mt-0.5">Thống kê sản phẩm chủ lực đóng góp lớn vào doanh thu và sản lượng</p>
            </div>
            <span class="px-3.5 py-1.5 bg-amber-50 text-amber-700 text-xs font-bold rounded-xl border border-amber-200 flex items-center gap-1.5">
                <i class="fas fa-trophy text-amber-500"></i> Top Hiệu Suất
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50 border-b border-slate-200">
                        <th class="py-3.5 px-5 rounded-l-2xl">Thứ hạng</th>
                        <th class="py-3.5 px-5">Tên món ăn</th>
                        <th class="py-3.5 px-5">Đơn giá niêm yết</th>
                        <th class="py-3.5 px-5">Tổng số lượng bán</th>
                        <th class="py-3.5 px-5 text-right rounded-r-2xl">Tổng doanh thu</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-slate-100">
                    @forelse($topMonAn ?? [] as $index => $item)
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        <td class="py-4 px-5 font-bold">
                            @if($index == 0)
                                <span class="w-8 h-8 rounded-xl bg-amber-500 text-white font-black flex items-center justify-center text-xs shadow-md shadow-amber-500/20">#1</span>
                            @elseif($index == 1)
                                <span class="w-8 h-8 rounded-xl bg-slate-300 text-slate-800 font-black flex items-center justify-center text-xs">#2</span>
                            @else
                                <span class="w-8 h-8 rounded-xl bg-slate-100 text-slate-600 font-bold flex items-center justify-center text-xs">#{{ $index + 1 }}</span>
                            @endif
                        </td>
                        <td class="py-4 px-5 font-bold text-slate-800">{{ $item->dish->name ?? 'N/A' }}</td>
                        <td class="py-4 px-5 font-semibold text-slate-600">{{ number_format($item->dish->price ?? 0, 0, ',', '.') }} đ</td>
                        <td class="py-4 px-5">
                            <span class="px-3 py-1 bg-indigo-50 text-indigo-700 font-bold text-xs rounded-lg border border-indigo-100">
                                {{ number_format($item->total_qty ?? 0, 0, ',', '.') }} tô
                            </span>
                        </td>
                        <td class="py-4 px-5 text-right font-extrabold text-emerald-600 text-base">
                            {{ number_format($item->total_revenue ?? 0, 0, ',', '.') }} đ
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-8 text-slate-400 text-sm">Chưa có dữ liệu món ăn trong kỳ này</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TẦNG BÁO CÁO KHO & HẠN SỬ DỤNG -->
    <div class="bg-white p-7 rounded-3xl border border-slate-200/80 shadow-sm">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h3 class="text-lg font-black text-slate-800">Báo Cáo Tình Trạng Bảo Quản & Hạn Sử Dụng</h3>
                <p class="text-xs text-slate-400 mt-0.5">Giám sát vòng đời nguyên vật liệu, tránh thất thoát và hư hỏng trong kho</p>
            </div>
            <span class="px-3.5 py-1.5 bg-rose-50 text-rose-700 text-xs font-bold rounded-xl border border-rose-200 flex items-center gap-1.5">
                <i class="fas fa-triangle-exclamation text-rose-500 animate-bounce"></i> Cần xử lý gấp
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50 border-b border-slate-200">
                        <th class="py-3.5 px-5 rounded-l-2xl">Tên nguyên liệu</th>
                        <th class="py-3.5 px-5">Tồn kho hiện tại</th>
                        <th class="py-3.5 px-5">Thời gian nhập kho</th>
                        <th class="py-3.5 px-5">Hạn sử dụng</th>
                        <th class="py-3.5 px-5 text-right rounded-r-2xl">Trạng thái chất lượng</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-slate-100">
                    @forelse($ingredients ?? [] as $ing)
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        <td class="py-4 px-5 font-bold text-slate-800">{{ $ing->name }}</td>
                        <td class="py-4 px-5 font-bold text-slate-700">
                            {{ $ing->stock_quantity }} <span class="text-xs font-normal text-slate-400">{{ $ing->unit }}</span>
                        </td>
                        <td class="py-4 px-5 text-slate-500 font-medium text-xs">{{ $ing->import_date }}</td>
                        <td class="py-4 px-5 text-slate-700 font-semibold text-xs">{{ $ing->expiry_date }}</td>
                        <td class="py-4 px-5 text-right">
                            @if($ing->quality_status == 'Sắp hết hạn')
                                <span class="px-3.5 py-1.5 bg-rose-100 text-rose-700 font-black text-xs rounded-xl border border-rose-200 inline-flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-rose-600 animate-ping"></span> Sắp hết hạn
                                </span>
                            @elseif($ing->quality_status == 'Cần lưu ý')
                                <span class="px-3.5 py-1.5 bg-amber-100 text-amber-700 font-black text-xs rounded-xl border border-amber-200 inline-flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-amber-600"></span> Cần lưu ý
                                </span>
                            @else
                                <span class="px-3.5 py-1.5 bg-emerald-100 text-emerald-700 font-black text-xs rounded-xl border border-emerald-200 inline-flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span> Tốt
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-8 text-slate-400 text-sm">Kho nguyên liệu hiện tại trống</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- JavaScript xử lý tương tác động đổi màu nút và gán ngày chính xác -->
<script>
    function setDateRange(type) {
        const today = new Date();
        let fromDate = document.getElementById('fromDate');
        let toDate = document.getElementById('toDate');

        let formatDate = (date) => {
            let d = new Date(date),
                month = '' + (d.getMonth() + 1),
                day = '' + d.getDate(),
                year = d.getFullYear();
            if (month.length < 2) month = '0' + month;
            if (day.length < 2) day = '0' + day;
            return [year, month, day].join('-');
        };

        let todayStr = formatDate(today);
        toDate.value = todayStr;

        // Xóa hiệu ứng active của tất cả các nút
        ['btn-today', 'btn-7days', 'btn-month'].forEach(id => {
            let btn = document.getElementById(id);
            btn.classList.remove('bg-white', 'text-indigo-600', 'shadow-sm');
            btn.classList.add('text-slate-600');
        });

        // Kích hoạt style active cho nút được bấm
        let activeBtn = document.getElementById('btn-' + type);
        activeBtn.classList.remove('text-slate-600');
        activeBtn.classList.add('bg-white', 'text-indigo-600', 'shadow-sm');

        if (type === 'today') {
            fromDate.value = todayStr;
        } else if (type === '7days') {
            let pastDate = new Date();
            pastDate.setDate(today.getDate() - 7);
            fromDate.value = formatDate(pastDate);
        } else if (type === 'month') {
            let firstDayMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            fromDate.value = formatDate(firstDayMonth);
        }

        // Tự động submit form lọc dữ liệu
        document.getElementById('reportFilterForm').submit();
    }
</script>
@endsection
