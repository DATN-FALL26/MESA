<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Table;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // Trang Tổng quan Dashboard theo chi nhánh
public function index(Request $request)
    {
        $branchId = $request->input('branch_id', 1);
        $title = 'Tổng quan Dashboard'; // Thêm dòng này

        try {
            $doanhThu = Order::where('branch_id', $branchId)
                             ->whereDate('created_at', today())
                             ->where('status', 'completed')
                             ->sum('total_price');

            $tongDon = Order::where('branch_id', $branchId)
                            ->whereDate('created_at', today())
                            ->count();

            $danhSachBan = Table::where('branch_id', $branchId)->get();
            $donHangs = Order::where('branch_id', $branchId)->latest()->take(5)->get();

            $thongKeMon = OrderDetail::select('dish_id', DB::raw('SUM(quantity) as total_qty'))
                            ->whereHas('order', function($q) use ($branchId) {
                                $q->where('branch_id', $branchId)->where('status', 'completed');
                            })
                            ->with('dish')
                            ->groupBy('dish_id')
                            ->orderByDesc('total_qty')
                            ->get();

            $monBanChay = $thongKeMon->first();
            $monItOrder = $thongKeMon->last();

            $caSang = Order::where('branch_id', $branchId)->whereDate('created_at', today())->whereTime('created_at', '>=', '06:00:00')->whereTime('created_at', '<', '11:00:00')->sum('total_price');
            $caTrua = Order::where('branch_id', $branchId)->whereDate('created_at', today())->whereTime('created_at', '>=', '11:00:00')->whereTime('created_at', '<', '17:00:00')->sum('total_price');
            $caToi  = Order::where('branch_id', $branchId)->whereDate('created_at', today())->whereTime('created_at', '>=', '17:00:00')->whereTime('created_at', '<', '22:00:00')->sum('total_price');

            $chartData = [$caSang, $caTrua, $caToi];
            $branches = Branch::all();

        } catch (\Exception $e) {
            $doanhThu = 0;
            $tongDon = 0;
            $danhSachBan = collect([]);
            $donHangs = collect([]);
            $monBanChay = null;
            $monItOrder = null;
            $chartData = [0, 0, 0];
            $branches = collect([]);
        }

        return view('admin.dashboard', compact(
            'doanhThu',
            'tongDon',
            'danhSachBan',
            'donHangs',
            'monBanChay',
            'monItOrder',
            'chartData',
            'branches',
            'branchId',
            'title' // Thêm 'title' vào đây luôn nhé
        ));
    }

    // Trang Báo cáo chi tiết theo khoảng thời gian
    public function reports(Request $request)
    {
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $fromDate = $request->input('from_date', now()->subDays(7)->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());
        $title = 'Báo cáo chi tiết'; // Thêm dòng này

        try {
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
            $tongDoanhThuKy = 0;
            $tongSoTo = 0;
            $topMonAn = collect([]);
        }

        return view('admin.reports', compact('fromDate', 'toDate', 'tongDoanhThuKy', 'tongSoTo', 'topMonAn', 'title')); // Thêm 'title' vào đây
    }
}
