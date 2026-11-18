<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Tổng quan Dashboard Kinh doanh';
        $today = today();

        // 1. Lấy danh sách chi nhánh từ Database
        $branches = Schema::hasTable('branches') ? DB::table('branches')->get() : collect();
        $defaultBranchId = $branches->first()->id ?? 1;
        $branchId = $request->input('branch_id', session('current_branch_id', $defaultBranchId));
        session(['current_branch_id' => $branchId]);

        // 2. Thống kê Doanh thu và Tổng đơn hàng trong ngày từ bảng 'orders'
        $orderQuery = DB::table('orders')->whereDate('created_at', $today);
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'branch_id')) {
            $orderQuery->where('branch_id', $branchId);
        }

        $orderStats = (clone $orderQuery)->select([
            DB::raw('COALESCE(SUM(total_amount), 0) as total_revenue'),
            DB::raw('COUNT(id) as total_orders'),
        ])->first();

        $doanhThu = $orderStats->total_revenue ?? 0;
        $tongDon = $orderStats->total_orders ?? 0;

        // 3. Xử lý Biểu đồ doanh thu theo bộ lọc (Tuần / Tháng / Năm)
        $chartLabels = [];
        $chartData = [];
        $filter = $request->input('filter', 'week');

        if ($filter == 'month') {
            $chartLabels = ['Tuần 1', 'Tuần 2', 'Tuần 3', 'Tuần 4'];
            $chartData = [0, 0, 0, 0];
        } elseif ($filter == 'year') {
            $chartLabels = ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];
            for ($m = 1; $m <= 12; $m++) {
                $mQuery = DB::table('orders')->whereYear('created_at', now()->year)->whereMonth('created_at', $m);
                if (Schema::hasColumn('orders', 'branch_id')) {
                    $mQuery->where('branch_id', $branchId);
                }
                $chartData[] = $mQuery->sum('total_amount') ?? 0;
            }
        } else {
            $chartLabels = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ Nhật'];
            $chartData = array_fill(0, 7, 0);
            $weeklyOrders = DB::table('orders')
                ->where('created_at', '>=', now()->subDays(6)->startOfDay())
                ->when(Schema::hasColumn('orders', 'branch_id'), fn($q) => $q->where('branch_id', $branchId))
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as total'))
                ->groupBy('date')
                ->pluck('total', 'date');

            for ($i = 6; $i >= 0; $i--) {
                $dStr = now()->subDays($i)->toDateString();
                $dayOfWeek = now()->subDays($i)->dayOfWeekIso;
                $chartData[$dayOfWeek - 1] = $weeklyOrders[$dStr] ?? 0;
            }
        }

        // 4. Lấy danh sách bàn ăn từ bảng 'dining_tables'
        $danhSachBan = collect();
        if (Schema::hasTable('dining_tables')) {
            $tableQuery = DB::table('dining_tables');
            if (Schema::hasColumn('dining_tables', 'branch_id')) {
                $tableQuery->where('branch_id', $branchId);
            }
            $danhSachBan = $tableQuery->get()->map(function($ban) {
                $ban->name = $ban->name ?? $ban->table_name ?? $ban->ten_ban ?? ('Bàn ' . ($ban->id ?? ''));
                $ban->status = $ban->status ?? 'empty';
                return $ban;
            });
        }

        // 5. Lấy danh sách đơn hàng gần đây từ bảng 'orders'
        $donHangs = collect();
        if (Schema::hasTable('orders')) {
            $orderListQuery = DB::table('orders');
            if (Schema::hasColumn('orders', 'branch_id')) {
                $orderListQuery->where('branch_id', $branchId);
            }
            $donHangs = $orderListQuery->latest()->take(5)->get();
        }

        // 6. Truy vấn Món ăn bán chạy và ít bán từ Database
        $monBanChay = null;
        $monItOrder = null;

        if (Schema::hasTable('order_items') && Schema::hasTable('menu_items') && Schema::hasTable('orders')) {
            $itemBaseQuery = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id');

            if (Schema::hasColumn('orders', 'branch_id')) {
                $itemBaseQuery->where('orders.branch_id', $branchId);
            }

            $bestSeller = (clone $itemBaseQuery)
                ->select('menu_items.id', 'menu_items.name', DB::raw((Schema::hasColumn('menu_items', 'price') ? 'menu_items.price' : (Schema::hasColumn('menu_items', 'selling_price') ? 'menu_items.selling_price' : '0')) . ' as price'), DB::raw('SUM(order_items.quantity) as total_qty'))
                ->groupBy('menu_items.id', 'menu_items.name', 'price')
                ->orderByDesc('total_qty')
                ->first();

            if ($bestSeller) {
                $monBanChay = (object)[
                    'total_qty' => $bestSeller->total_qty,
                    'dish' => (object)['name' => $bestSeller->name, 'price' => $bestSeller->price ?? 0]
                ];
            }

            $leastSeller = (clone $itemBaseQuery)
                ->select('menu_items.id', 'menu_items.name', DB::raw((Schema::hasColumn('menu_items', 'price') ? 'menu_items.price' : (Schema::hasColumn('menu_items', 'selling_price') ? 'menu_items.selling_price' : '0')) . ' as price'), DB::raw('SUM(order_items.quantity) as total_qty'))
                ->groupBy('menu_items.id', 'menu_items.name', 'price')
                ->orderBy('total_qty', 'asc')
                ->first();

            if ($leastSeller) {
                $monItOrder = (object)[
                    'total_qty' => $leastSeller->total_qty,
                    'dish' => (object)['name' => $leastSeller->name, 'price' => $leastSeller->price ?? 0]
                ];
            }
        }

        if (!$monBanChay) {
            $monBanChay = (object)['total_qty' => 0, 'dish' => (object)['name' => 'Chưa có dữ liệu', 'price' => 0]];
        }
        if (!$monItOrder) {
            $monItOrder = (object)['total_qty' => 0, 'dish' => (object)['name' => 'Chưa có dữ liệu', 'price' => 0]];
        }

        return view('admin.dashboard', compact(
            'doanhThu', 'tongDon', 'danhSachBan', 'donHangs',
            'monBanChay', 'monItOrder', 'chartLabels', 'chartData',
            'branches', 'branchId', 'filter', 'title'
        ));
    }

    public function reports(Request $request)
    {
        $fromDate = $request->input('from_date', now()->subDays(7)->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());

        $branches = Schema::hasTable('branches') ? DB::table('branches')->get() : collect();
        $branchId = session('current_branch_id', $request->input('branch_id', $branches->first()->id ?? 1));
        session(['current_branch_id' => $branchId]);

        $title = 'Báo cáo chi tiết kinh doanh';

        // 1. Tổng doanh thu trong kỳ báo cáo
        $orderReportQuery = DB::table('orders')->whereBetween(DB::raw('DATE(created_at)'), [$fromDate, $toDate]);
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'branch_id')) {
            $orderReportQuery->where('branch_id', $branchId);
        }

        $reportStats = $orderReportQuery->select([
            DB::raw('COALESCE(SUM(total_amount), 0) as total_revenue'),
            DB::raw('COUNT(id) as total_orders')
        ])->first();

        $tongDoanhThuKy = $reportStats->total_revenue ?? 0;

        // 2. Tổng sản lượng bán ra trong kỳ
        $tongSoTo = 0;
        if (Schema::hasTable('order_items') && Schema::hasTable('orders')) {
            $itemQuery = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereBetween(DB::raw('DATE(orders.created_at)'), [$fromDate, $toDate]);

            if (Schema::hasColumn('orders', 'branch_id')) {
                $itemQuery->where('orders.branch_id', $branchId);
            }

            $tongSoTo = (int) $itemQuery->sum('order_items.quantity');
        }

        // 3. Top món ăn doanh thu cao trong kỳ
        $topMonAn = collect();
        if (Schema::hasTable('order_items') && Schema::hasTable('menu_items') && Schema::hasTable('orders')) {
            $topQuery = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
                ->whereBetween(DB::raw('DATE(orders.created_at)'), [$fromDate, $toDate]);

            if (Schema::hasColumn('orders', 'branch_id')) {
                $topQuery->where('orders.branch_id', $branchId);
            }

            $priceColStr = Schema::hasColumn('menu_items', 'price') ? 'menu_items.price' : (Schema::hasColumn('menu_items', 'selling_price') ? 'menu_items.selling_price' : '0');

            $topMonAn = $topQuery->select(
                    'menu_items.name as dish_name',
                    DB::raw($priceColStr . ' as dish_price'),
                    DB::raw('SUM(order_items.quantity) as total_qty'),
                    DB::raw('SUM(order_items.quantity * ' . $priceColStr . ') as total_revenue')
                )
                ->groupBy('menu_items.id', 'menu_items.name', 'dish_price')
                ->orderByDesc('total_revenue')
                ->take(5)
                ->get()
                ->map(function($item) {
                    return (object)[
                        'total_qty' => $item->total_qty,
                        'total_revenue' => $item->total_revenue,
                        'dish' => (object)['name' => $item->dish_name, 'price' => $item->dish_price ?? 0]
                    ];
                });
        }

        // 4. Lấy trạng thái nguyên liệu từ bảng 'ingredients'
        $ingredients = collect();
        if (Schema::hasTable('ingredients')) {
            $ingQuery = DB::table('ingredients');
            if (Schema::hasColumn('ingredients', 'branch_id')) {
                $ingQuery->where('branch_id', $branchId);
            }
            $ingredients = $ingQuery->get();
        }

        return view('admin.reports', compact('fromDate', 'toDate', 'tongDoanhThuKy', 'tongSoTo', 'topMonAn', 'ingredients', 'title', 'branches', 'branchId'));
    }

    // Bổ sung chức năng xuất tệp báo cáo CSV/Excel thực tế khi người dùng click
    public function exportExcel(Request $request)
    {
        $fileName = 'bao-cao-doanh-thu-' . date('Y-m-d') . '.csv';
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM cho Excel tiếng Việt
            fputcsv($file, ['ID Đơn hàng', 'Mã chi nhánh', 'Tổng tiền (VNĐ)', 'Thời gian tạo']);

            $orders = DB::table('orders')->latest()->take(100)->get();
            foreach ($orders as $order) {
                fputcsv($file, [$order->id, $order->branch_id ?? 1, $order->total_amount ?? 0, $order->created_at ?? '']);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function getPaymentMethodStats(Request $request)
    {
        return response()->json(['status' => 'success', 'data' => ['cash' => 0, 'transfer' => 0]]);
    }

    public function getFoodStorageReport(Request $request)
    {
        return response()->json(['status' => 'success']);
    }
}
