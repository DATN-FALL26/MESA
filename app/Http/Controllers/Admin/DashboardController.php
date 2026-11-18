<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Màn hình chính Dashboard tổng quan
     */
    public function index(Request $request)
    {
        $branchId = $request->input('branch_id', 1);
        $filter = $request->input('filter', 'week');

        // Lấy danh sách chi nhánh
        $branches = [];
        if (Schema::hasTable('branches')) {
            $branches = DB::table('branches')->get();
        }

        // 1. Thống kê tổng quan hôm nay
        $doanhThu = 0;
        $tongDon = 0;
        if (Schema::hasTable('orders')) {
            $query = DB::table('orders')->whereDate('created_at', Carbon::today());
            if (Schema::hasColumn('orders', 'branch_id')) {
                $query->where('branch_id', $branchId);
            }
            $doanhThu = (clone $query)->sum('total_amount') ?? 0;
            $tongDon = (clone $query)->count() ?? 0;
        }

        // 2. Món ăn bán chạy & Ít được lên món
        $monBanChay = null;
        $monItOrder = null;
        if (Schema::hasTable('order_items') && Schema::hasTable('menu_items')) {
            $itemQuery = DB::table('order_items')
                ->select('menu_item_id', DB::raw('SUM(quantity) as total_qty'))
                ->groupBy('menu_item_id')
                ->orderByDesc('total_qty');

            $topItems = $itemQuery->get();
            if ($topItems->isNotEmpty()) {
                $topId = $topItems->first()->menu_item_id;
                $lowestId = $topItems->last()->menu_item_id;

                $monBanChay = (object)[
                    'dish' => DB::table('menu_items')->where('id', $topId)->first(),
                    'total_qty' => $topItems->first()->total_qty
                ];

                $monItOrder = (object)[
                    'dish' => DB::table('menu_items')->where('id', $lowestId)->first(),
                    'total_qty' => $topItems->last()->total_qty
                ];
            }
        }

        // 3. Dữ liệu biểu đồ tăng trưởng doanh thu
        $chartLabels = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ Nhật'];
        $chartData = [0, 0, 0, 0, 0, 0, 0];
        if (Schema::hasTable('orders')) {
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $dayRevenue = DB::table('orders')
                    ->whereDate('created_at', $date)
                    ->when(Schema::hasColumn('orders', 'branch_id'), fn($q) => $q->where('branch_id', $branchId))
                    ->sum('total_amount') ?? 0;

                $chartData[6 - $i] = (float)$dayRevenue;
            }
        }

        // 4. Sơ đồ trạng thái bàn ăn
        $danhSachBan = [];
        if (Schema::hasTable('dining_tables')) {
            $tableQuery = DB::table('dining_tables');
            if (Schema::hasColumn('dining_tables', 'branch_id')) {
                $tableQuery->where('branch_id', $branchId);
            }
            $danhSachBan = $tableQuery->get();
        }

        // 5. Đơn hàng gần đây
        $donHangs = [];
        if (Schema::hasTable('orders')) {
            $orderQuery = DB::table('orders')->orderByDesc('created_at')->limit(5);
            if (Schema::hasColumn('orders', 'branch_id')) {
                $orderQuery->where('branch_id', $branchId);
            }
            $donHangs = $orderQuery->get();
        }

        return view('admin.dashboard', compact(
            'branches', 'branchId', 'filter', 'doanhThu', 'tongDon',
            'monBanChay', 'monItOrder', 'chartLabels', 'chartData',
            'danhSachBan', 'donHangs'
        ));
    }

    /**
     * Màn hình Báo cáo chi tiết kinh doanh & Tồn kho nguyên liệu
     */
    public function reports(Request $request)
    {
        $branchId = $request->input('branch_id', 1);
        $fromDate = $request->input('from_date', Carbon::today()->subDays(7)->format('Y-m-d'));
        $toDate = $request->input('to_date', Carbon::today()->format('Y-m-d'));

        $branches = [];
        if (Schema::hasTable('branches')) {
            $branches = DB::table('branches')->get();
        }

        // 1. Tổng doanh thu & sản lượng trong kỳ báo cáo
        $tongDoanhThuKy = 0;
        $tongSoTo = 0;
        if (Schema::hasTable('orders')) {
            $q = DB::table('orders')->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
            if (Schema::hasColumn('orders', 'branch_id')) {
                $q->where('branch_id', $branchId);
            }
            $tongDoanhThuKy = (clone $q)->sum('total_amount') ?? 0;
        }

        if (Schema::hasTable('order_items')) {
            $qi = DB::table('order_items')->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
            $tongSoTo = (clone $qi)->sum('quantity') ?? 0;
        }

        // 2. Top món ăn doanh thu cao trong kỳ (Tự động thích ứng tên cột giá trong DB)
        $topMonAn = [];
        if (Schema::hasTable('order_items') && Schema::hasTable('menu_items')) {
            $priceColumn = 'price';
            if (!Schema::hasColumn('menu_items', 'price')) {
                if (Schema::hasColumn('menu_items', 'cost')) {
                    $priceColumn = 'cost';
                } elseif (Schema::hasColumn('menu_items', 'selling_price')) {
                    $priceColumn = 'selling_price';
                } else {
                    $priceColumn = null;
                }
            }

            $selectRaw = $priceColumn
                ? 'SUM(order_items.quantity) as total_qty, SUM(order_items.quantity * menu_items.' . $priceColumn . ') as total_revenue'
                : 'SUM(order_items.quantity) as total_qty, 0 as total_revenue';

            $topMonAn = DB::table('order_items')
                ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
                ->whereBetween('order_items.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
                ->select(
                    'menu_items.id',
                    'menu_items.name',
                    DB::raw($selectRaw)
                )
                ->groupBy('menu_items.id', 'menu_items.name')
                ->orderByDesc('total_qty')
                ->limit(5)
                ->get()
                ->map(function($item) {
                    return (object)[
                        'dish' => (object)['name' => $item->name],
                        'total_qty' => $item->total_qty,
                        'total_revenue' => $item->total_revenue ?? 0
                    ];
                });
        }

        // 3. Danh sách nguyên vật liệu kho phục vụ Bảng Kiểm Soát Tồn Kho
        $ingredients = [];
        if (Schema::hasTable('ingredients')) {
            $ingQuery = DB::table('ingredients');
            if (Schema::hasColumn('ingredients', 'branch_id')) {
                $ingQuery->where('branch_id', $branchId);
            }
            $ingredients = $ingQuery->get();
        }

        return view('admin.reports', compact(
            'branches', 'branchId', 'fromDate', 'toDate',
            'tongDoanhThuKy', 'tongSoTo', 'topMonAn', 'ingredients'
        ));
    }

    /**
     * Xuất báo cáo ra file Excel (CSV Streaming)
     */
    public function exportExcel(Request $request)
    {
        $fileName = 'bao-cao-kinh-doanh-' . date('Y-m-d') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, ['ID Đơn Hàng', 'Chi Nhánh ID', 'Tổng Tiền (VNĐ)', 'Ngày Tạo']);

            if (Schema::hasTable('orders')) {
                $orders = DB::table('orders')->select('id', 'branch_id', 'total_amount', 'created_at')->get();
                foreach ($orders as $order) {
                    fputcsv($file, [
                        $order->id,
                        $order->branch_id ?? 1,
                        $order->total_amount,
                        $order->created_at
                    ]);
                }
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
