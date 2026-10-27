@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 space-y-6">

    <!-- 1. THANH CHUYỂN ĐỔI CHI NHÁNH & TIÊU ĐỀ -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white p-5 rounded-lg shadow-sm border gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">📊 Tổng Quan Dashboard Phở MESA</h1>
            <p class="text-xs text-gray-500 mt-1">Hệ thống giám sát kinh doanh và vận hành theo chi nhánh</p>
        </div>

        <!-- Nút chuyển đổi chi nhánh -->
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs font-semibold text-gray-600">📍 Chi nhánh:</span>
            @foreach($branches ?? [] as $branch)
                <a href="{{ route('admin.dashboard', ['branch_id' => data_get($branch, 'id')]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ ($branchId ?? 1) == data_get($branch, 'id') ? 'bg-red-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    {{ data_get($branch, 'name', 'Chi nhánh #' . data_get($branch, 'id')) }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- 2. 4 Ô KPI CỐT LÕI (Doanh thu, Tổng đơn, Món nổi bật, Món ít order) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Doanh thu -->
        <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-red-600">
            <p class="text-gray-500 text-xs font-semibold uppercase">Doanh thu hôm nay</p>
            <h3 class="text-2xl font-black text-gray-900 mt-1">{{ number_format($doanhThu ?? 0) }} <span class="text-sm font-bold text-gray-500">đ</span></h3>
        </div>

        <!-- Tổng đơn hàng -->
        <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-blue-600">
            <p class="text-gray-500 text-xs font-semibold uppercase">Tổng đơn hàng</p>
            <h3 class="text-2xl font-black text-gray-900 mt-1">{{ $tongDon ?? 0 }} <span class="text-sm font-bold text-gray-500">đơn</span></h3>
        </div>

        <!-- Món ăn nổi bật -->
        <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-emerald-600">
            <p class="text-gray-500 text-xs font-semibold uppercase">🔥 Món nổi bật nhất</p>
            <h3 class="text-lg font-bold text-gray-900 mt-1 truncate">{{ data_get($monBanChay, 'dish.name', 'Chưa có dữ liệu') }}</h3>
            <span class="text-xs text-emerald-600 font-semibold">Đã bán: {{ data_get($monBanChay, 'total_qty', 0) }} tô</span>
        </div>

        <!-- Món ít được order -->
        <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-amber-500">
            <p class="text-gray-500 text-xs font-semibold uppercase">⚠️ Món ít order nhất</p>
            <h3 class="text-lg font-bold text-gray-900 mt-1 truncate">{{ data_get($monItOrder, 'dish.name', 'Chưa có dữ liệu') }}</h3>
            <span class="text-xs text-amber-600 font-semibold">Đã bán: {{ data_get($monItOrder, 'total_qty', 0) }} tô</span>
        </div>
    </div>

    <!-- 3. BIỂU ĐỒ THEO CA & BẢNG THEO DÕI PHỤC VỤ BÀN -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Cột trái: Biểu đồ doanh thu theo ca -->
        <div class="bg-white p-6 rounded-lg shadow-sm border flex flex-col">
            <h3 class="font-bold text-gray-800 text-base mb-4">📊 Biểu đồ doanh thu theo ca (Hôm nay)</h3>
            <div class="flex-1 flex items-center justify-center min-h-[220px]">
                <canvas id="shiftRevenueChart"></canvas>
            </div>
        </div>

        <!-- Cột phải: Bảng theo dõi bàn phục vụ -->
        <div class="bg-white p-6 rounded-lg shadow-sm border flex flex-col">
            <h3 class="font-bold text-gray-800 text-base mb-4">🪑 Bảng Theo Dõi Phục Vụ Bàn Ăn</h3>
            <div class="overflow-x-auto max-h-[250px] overflow-y-auto flex-1">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b text-gray-600 sticky top-0">
                            <th class="p-2.5">Tên bàn</th>
                            <th class="p-2.5 text-center">Khu vực</th>
                            <th class="p-2.5 text-center">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($danhSachBan ?? [] as $ban)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-2.5 font-bold text-gray-900">{{ data_get($ban, 'name') }}</td>
                            <td class="p-2.5 text-center text-gray-600">{{ data_get($ban, 'area', 'Chính') }}</td>
                            <td class="p-2.5 text-center">
                                @php $status = data_get($ban, 'status'); @endphp
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $status == 'occupied' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                    {{ $status == 'occupied' ? '🔴 Đang có khách' : '🟢 Trống' }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="p-6 text-center text-gray-400 italic">Chưa cấu hình bàn ăn cho chi nhánh này.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- 4. BẢNG ĐƠN HÀNG GẦN ĐÂY (Tính năng cũ được giữ lại) -->
    <div class="bg-white p-6 rounded-lg shadow-sm border">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-gray-800 text-base">🕒 Đơn Hàng Mới Nhất</h3>
            <a href="{{ route('admin.reports') }}" class="text-xs font-semibold text-red-600 hover:underline">Xem báo cáo chi tiết →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b text-gray-600">
                        <th class="p-3">Mã đơn</th>
                        <th class="p-3">Bàn</th>
                        <th class="p-3">Tổng tiền</th>
                        <th class="p-3">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($donHangs ?? [] as $don)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3 font-bold">#{{ data_get($don, 'id') }}</td>
                        <td class="p-3">{{ data_get($don, 'table.name', 'Mang về') }}</td>
                        <td class="p-3 font-semibold text-red-600">{{ number_format(data_get($don, 'total_price', 0)) }} đ</td>
                        <td class="p-3">
                            @php $orderStatus = data_get($don, 'status'); @endphp
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $orderStatus == 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ $orderStatus == 'completed' ? 'Đã xong' : 'Đang xử lý' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="p-6 text-center text-gray-400 italic">Chưa có đơn hàng nào hôm nay.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('shiftRevenueChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Ca Sáng (6h-11h)', 'Ca Trưa (11h-17h)', 'Ca Tối (17h-22h)'],
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: @json($chartData ?? [0, 0, 0]),
                    backgroundColor: 'rgba(220, 38, 38, 0.8)',
                    borderColor: 'rgb(220, 38, 38)',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }
</script>
@endsection
