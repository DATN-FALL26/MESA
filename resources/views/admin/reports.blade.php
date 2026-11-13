@extends('layouts.auth')

@section('content')
<div class="container-fluid px-6 py-4">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Báo Cáo Chi Tiết Kinh Doanh</h1>
            <p class="text-sm text-gray-500">Phân tích chuyên sâu dòng doanh thu và kiểm soát kho nguyên liệu</p>
        </div>
        <div class="flex items-center gap-3 mt-3 md:mt-0">
            <button onclick="window.print()" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition">In báo cáo</button>
            <a href="#" class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">Xuất Excel</a>
        </div>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form method="GET" action="{{ route('admin.reports') }}" class="flex flex-col lg:flex-row gap-4 items-center justify-between">
            <div class="flex flex-wrap items-center gap-4 w-full lg:w-auto">
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">Từ ngày</label>
                    <input type="date" name="from_date" value="{{ $fromDate ?? now()->subDays(7)->toDateString() }}" class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg p-2.5">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">Đến ngày</label>
                    <input type="date" name="to_date" value="{{ $toDate ?? now()->toDateString() }}" class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg p-2.5">
                </div>
            </div>
            <div class="flex items-center gap-3 w-full lg:w-auto justify-end">
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition w-full lg:w-auto">Lọc dữ liệu</button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 text-white p-6 rounded-2xl shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-200">Tổng doanh thu trong kỳ</p>
                <h3 class="text-3xl font-extrabold mt-1">{{ number_format($tongDoanhThuKy ?? 0, 0, ',', '.') }} đ</h3>
            </div>
            <div class="p-4 bg-white/10 rounded-xl"><i class="fas fa-chart-line text-2xl"></i></div>
        </div>
        <div class="bg-gradient-to-r from-emerald-700 to-emerald-900 text-white p-6 rounded-2xl shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-200">Tổng sản lượng bán ra</p>
                <h3 class="text-3xl font-extrabold mt-1">{{ $tongSoTo ?? 0 }} sản phẩm</h3>
            </div>
            <div class="p-4 bg-white/10 rounded-xl"><i class="fas fa-box-open text-2xl"></i></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-8 bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-base font-bold text-gray-800 mb-4">Top Món Ăn Doanh Thu Cao Trong Kỳ</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b text-xs font-semibold text-gray-400 uppercase">
                            <th class="py-3 px-3">Tên món</th>
                            <th class="py-3 px-3 text-center">Đã bán</th>
                            <th class="py-3 px-3 text-right">Doanh thu</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-100">
                        @forelse($topMonAn as $item)
                        <tr>
                            <td class="py-3.5 px-3 font-medium text-gray-800">{{ $item->dish->name ?? 'N/A' }}</td>
                            <td class="py-3.5 px-3 text-center font-semibold text-gray-600">{{ $item->total_qty ?? 0 }}</td>
                            <td class="py-3.5 px-3 text-right font-bold text-emerald-600">{{ number_format($item->total_revenue ?? 0, 0, ',', '.') }} đ</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center py-4 text-gray-400 text-xs">Chưa có dữ liệu</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="lg:col-span-4 bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-base font-bold text-gray-800 mb-4">Cảnh Báo Hạn Sử Dụng Kho</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b text-xs font-semibold text-gray-400 uppercase">
                            <th class="py-3 px-2">Nguyên liệu</th>
                            <th class="py-3 px-2 text-center">Tồn kho</th>
                            <th class="py-3 px-2 text-right">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-100">
                        @forelse($ingredients as $ing)
                        <tr>
                            <td class="py-3.5 px-2 font-medium text-gray-800">{{ $ing->name }}</td>
                            <td class="py-3.5 px-2 text-center text-gray-600">{{ $ing->stock_quantity }} {{ $ing->unit ?? 'Kg' }}</td>
                            <td class="py-3.5 px-2 text-right">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-rose-50 text-rose-600">{{ $ing->quality_status ?? 'Sắp hết hạn' }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center py-4 text-gray-400 text-xs">Kho an toàn</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
