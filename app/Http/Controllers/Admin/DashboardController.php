<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Table;
use App\Models\Dish;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // Trang Tổng quan Dashboard (Nhiệm vụ 3 & 4)
    public function index(Request $request)
{
    try {
        // Dữ liệu thật từ Database (Nếu đã có bảng)
        $doanhThu = Order::whereDate('created_at', today())->where('status', 'completed')->sum('total_price');
        $tongDon = Order::whereDate('created_at', today())->count();
        $soBanDangAn = Table::where('status', 'occupied')->count();
        $donHangs = Order::with('table')->latest()->take(5)->get();

        $caSang = Order::whereDate('created_at', today())->whereTime('created_at', '>=', '06:00:00')->whereTime('created_at', '<', '11:00:00')->where('status', 'completed')->sum('total_price');
        $caTrua = Order::whereDate('created_at', today())->whereTime('created_at', '>=', '11:00:00')->whereTime('created_at', '<', '17:00:00')->where('status', 'completed')->sum('total_price');
        $caToi  = Order::whereDate('created_at', today())->whereTime('created_at', '>=', '17:00:00')->whereTime('created_at', '<=', '22:00:00')->where('status', 'completed')->sum('total_price');
        $chartData = [$caSang, $caTrua, $caToi];

    } catch (\Exception $e) {
        // Tự động nhảy về dữ liệu mẫu nếu Database chưa có bảng 'orders'
        $doanhThu = 12850000;
        $tongDon = 145;
        $soBanDangAn = 8;
        $donHangs = collect([]); // Mảng rỗng an toàn
        $chartData = [3500000, 5200000, 4150000];
    }

    return view('admin.dashboard', compact('doanhThu', 'tongDon', 'soBanDangAn', 'donHangs', 'chartData'));
}

    // Trang Báo cáo chi tiết & Validate lọc dữ liệu
    // Trang Báo cáo chi tiết theo thời gian
public function reports(Request $request)
{
    $request->validate([
        'from_date' => 'nullable|date',
        'to_date' => 'nullable|date|after_or_equal:from_date',
    ]);

    $fromDate = $request->input('from_date', now()->subDays(7)->toDateString());
    $toDate = $request->input('to_date', now()->toDateString());

    try {
        // Dữ liệu thật từ DB (khi team đã gửi CSDL)
        $tongDoanhThuKy = Order::whereBetween(DB::raw('DATE(created_at)'), [$fromDate, $toDate])
                                ->where('status', 'completed')
                                ->sum('total_price');

        $tongSoTo = OrderDetail::whereHas('order', function ($query) use ($fromDate, $toDate) {
                        $query->whereBetween(DB::raw('DATE(created_at)'), [$fromDate, $toDate])
                              ->where('status', 'completed');
                    })->sum('quantity');

        $topMonAn = OrderDetail::select('dish_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(price * quantity) as total_revenue'))
                    ->whereHas('order', function ($query) use ($fromDate, $toDate) {
                        $query->whereBetween(DB::raw('DATE(created_at)'), [$fromDate, $toDate])
                              ->where('status', 'completed');
                    })
                    ->with('dish')
                    ->groupBy('dish_id')
                    ->orderByDesc('total_qty')
                    ->get();

    } catch (\Exception $e) {
        // Tự động trả về dữ liệu mẫu khi chưa có bảng 'orders' trong MySQL
        $tongDoanhThuKy = 34950000;
        $tongSoTo = 680;
        $topMonAn = collect([]); // Mảng rỗng an toàn
    }

    return view('admin.reports', compact('fromDate', 'toDate', 'tongDoanhThuKy', 'tongSoTo', 'topMonAn'));
}
}
