@extends('layouts.auth')

@section('content')
<div class="space-y-6">
    <!-- Header Báo cáo -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Báo Cáo Chi Tiết</h1>
            <p class="text-sm text-gray-500 mt-0.5">Thống kê doanh thu, sản lượng và top món ăn theo khoảng thời gian tùy chọn.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-semibold bg-white text-gray-700 border border-gray-200 shadow-sm hover:bg-gray-50 transition">
                ← Quay lại Dashboard
            </a>
        </div>
    </div>

    <!-- Bộ lọc Khoảng thời gian -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
        <form action="{{ route('admin.reports') }}" method="GET" class="flex flex-col md:flex-row items-end md:items-center justify-between gap-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full md:w-auto flex-1">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Từ ngày</label>
                    <input type="date" name="from_date" value="{{ $fromDate }}"
                           class="bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Đến ngày</label>
                    <input type="date" name="to_date" value="{{ $toDate }}"
                           class="bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 outline-none transition">
                </div>
            </div>
            <div class="flex items-center gap-2 w-full md:w-auto">
                <button type="submit" class="w-full md:w-auto px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-xl shadow-sm shadow-indigo-100 transition">
                    Lọc dữ liệu
                </button>
            </div>
        </form>
    </div>

    <!-- 2 Thẻ Thống Kê Tổng Quan Kỳ -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-indigo-100 relative overflow-hidden transition hover:shadow-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 bg-indigo-50 px-3 py-1 rounded-lg">Tổng Doanh Thu Kỳ</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 text-lg">💰</div>
            </div>
            <div class="text-3xl font-extrabold text-gray-900">{{ number_format($tongDoanhThuKy ?? 0) }} đ</div>
            <p class="text-xs text-gray-400 mt-2">Từ ngày {{ date('d/m/Y', strtotime($fromDate)) }} đến {{ date('d/m/Y', strtotime($toDate)) }}</p>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-emerald-100 relative overflow-hidden transition hover:shadow-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 bg-emerald-50 px-3 py-1 rounded-lg">Tổng Số Lượng Bán Ra</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 text-lg">🍜</div>
            </div>
            <div class="text-3xl font-extrabold text-gray-900">{{ number_format($tongSoTo ?? 0) }} <span class="text-lg font-normal text-gray-500">tô</span></div>
            <p class="text-xs text-gray-400 mt-2">Tổng sản lượng phục vụ trong kỳ</p>
        </div>
    </div>

    <!-- Bảng Chi Tiết Top Món Ăn Trong Kỳ -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="font-bold text-gray-900 text-base">🔥 Top Món Ăn Bán Chạy Trong Kỳ</h3>
                <p class="text-xs text-gray-400">Xếp hạng sản phẩm theo số lượng tiêu thụ và doanh thu đóng góp</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        <th class="pb-3 pl-2">Thứ hạng</th>
                        <th class="pb-3">Tên món ăn</th>
                        <th class="pb-3">Đơn giá niêm yết</th>
                        <th class="pb-3">Tổng số lượng bán</th>
                        <th class="pb-3 text-right pr-2">Tổng doanh thu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-sm">
                    @forelse($topMonAn as $index => $item)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="py-3.5 pl-2 font-bold text-gray-700">
                                @if($index == 0)
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 text-amber-700 text-xs">#1</span>
                                @elseif($index == 1)
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-200 text-gray-700 text-xs">#2</span>
                                @elseif($index == 2)
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-orange-100 text-orange-700 text-xs">#3</span>
                                @else
                                    <span class="text-gray-500 pl-2">#{{ $index + 1 }}</span>
                                @endif
                            </td>
                            <td class="py-3.5 font-semibold text-gray-900">{{ $item->dish->name ?? 'Không rõ' }}</td>
                            <td class="py-3.5 text-gray-600">{{ number_format($item->dish->price ?? 0) }} đ</td>
                            <td class="py-3.5 font-medium text-indigo-600">{{ number_format($item->total_qty) }} tô</td>
                            <td class="py-3.5 text-right pr-2 font-bold text-emerald-600">{{ number_format($item->total_revenue) }} đ</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-gray-400 text-sm">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="text-3xl mb-2">📊</span>
                                    <span>Không có dữ liệu báo cáo trong khoảng thời gian này.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
