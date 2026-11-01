@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">

    <!-- TIÊU ĐỀ & NÚT QUAY LẠI -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">📈 Báo Cáo Chi Tiết Doanh Thu</h1>
            <p class="text-xs text-gray-500 mt-1">Thống kê doanh thu, số lượng tô phở và các món bán chạy theo khoảng thời gian</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="bg-gray-800 text-white px-4 py-2 rounded-lg font-semibold text-sm hover:bg-gray-900 transition shadow-sm">
            ← Quay lại Dashboard
        </a>
    </div>

    <!-- FORM LỌC KHOẢNG THỜI GIAN -->
    <div class="bg-white p-5 rounded-lg shadow-sm border mb-6">
        <form method="GET" action="{{ route('admin.reports') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Từ ngày:</label>
                <input type="date" name="from_date" value="{{ $fromDate }}" class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Đến ngày:</label>
                <input type="date" name="to_date" value="{{ $toDate }}" class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            <div>
                <button type="submit" class="bg-red-600 text-white px-5 py-2 rounded-lg font-semibold text-sm hover:bg-red-700 transition shadow-sm">
                    🔍 Lọc dữ liệu
                </button>
            </div>
        </form>
    </div>

    <!-- 2 Ô KPI TỔNG QUAN KỲ BÁO CÁO -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-red-600 flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Tổng doanh thu kỳ báo cáo</p>
                <h2 class="text-3xl font-black text-gray-900 mt-1">{{ number_format($tongDoanhThuKy ?? 0) }} <span class="text-lg font-bold text-gray-600">VND</span></h2>
            </div>
            <div class="w-12 h-12 rounded-full bg-red-50 text-red-600 flex items-center justify-center text-2xl font-bold">💰</div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-blue-600 flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Tổng số lượng tô phở đã bán</p>
                <h2 class="text-3xl font-black text-gray-900 mt-1">{{ number_format($tongSoTo ?? 0) }} <span class="text-lg font-bold text-gray-600">tô</span></h2>
            </div>
            <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-2xl font-bold">🍜</div>
        </div>
    </div>

    <!-- BẢNG TOP MÓN ĂN BÁN CHẠY -->
    <div class="bg-white p-6 rounded-lg shadow-sm border">
        <h3 class="font-bold text-gray-800 text-base mb-4 flex items-center gap-2">
            <span>🏆 Top món ăn bán chạy trong kỳ</span>
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b text-gray-600">
                        <th class="p-3 font-semibold">STT</th>
                        <th class="p-3 font-semibold">Tên món ăn</th>
                        <th class="p-3 font-semibold text-center">Số lượng bán</th>
                        <th class="p-3 font-semibold text-right">Tổng doanh thu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topMonAn ?? [] as $index => $item)
                    <tr class="border-b hover:bg-gray-50/80 transition-colors">
                        <td class="p-3 font-bold text-gray-500">#{{ $index + 1 }}</td>
                        <td class="p-3 font-semibold text-gray-900">{{ data_get($item, 'dish.name', 'Món ăn #' . data_get($item, 'dish_id')) }}</td>
                        <td class="p-3 text-center font-bold text-blue-600">{{ data_get($item, 'total_qty', 0) }} tô</td>
                        <td class="p-3 text-right font-bold text-red-600">{{ number_format(data_get($item, 'total_revenue', 0)) }} đ</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="p-8 text-center text-gray-400 italic">
                            Chưa có dữ liệu thống kê món ăn trong khoảng thời gian này.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
