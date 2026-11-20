<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Helper lọc đơn hàng hợp lệ theo schema thực tế (chỉ định rõ bảng để tránh ambiguous column)
     */
    private function applyValidOrderScope($query, string $column = 'orders.status')
    {
        $table = str_contains($column, '.') ? explode('.', $column)[0] : 'orders';
        $col = str_contains($column, '.') ? explode('.', $column)[1] : $column;

        if (Schema::hasTable($table) && Schema::hasColumn($table, $col)) {
            $query->whereNotIn($column, [
                'cancelled', 'canceled', 'void', 'draft', 'unpaid', 'pending_payment',
                'da_huy', 'huy', 'nhap', 'chua_thanh_toan',
            ]);
        }

        return $query;
    }

    /**
     * Helper xác định cột tổng tiền của bảng orders
     */
    private function resolveOrderAmountColumn(): ?string
    {
        foreach (['total_amount', 'grand_total', 'final_amount', 'amount', 'total'] as $col) {
            if (Schema::hasColumn('orders', $col)) {
                return $col;
            }
        }

        return null;
    }

    /**
     * Màn hình chính Dashboard tổng quan
     */
    public function index(Request $request)
    {
        $branchId = $request->input('branch_id');
        $filter = $request->input('filter', 'week');
        if (!in_array($filter, ['week', 'month', 'year'], true)) {
            $filter = 'week';
        }

        $branches = [];
        if (Schema::hasTable('branches')) {
            $branches = DB::table('branches')->get();
            if (!$branchId && count($branches) > 0) {
                $firstBranch = $branches[0];
                $branchId = is_object($firstBranch) ? ($firstBranch->id ?? 1) : ($firstBranch['id'] ?? 1);
            }
        }
        $branchId = $branchId ?: 1;

        $hasBranchOnOrders = Schema::hasTable('orders') && Schema::hasColumn('orders', 'branch_id');
        $amountCol = Schema::hasTable('orders') ? $this->resolveOrderAmountColumn() : null;

        // 1. Thống kê tổng quan hôm nay
        $doanhThu = 0;
        $tongDon = 0;
        if (Schema::hasTable('orders')) {
            $todayQuery = DB::table('orders')->whereDate('orders.created_at', Carbon::today());
            if ($hasBranchOnOrders) {
                $todayQuery->where('orders.branch_id', $branchId);
            }
            $this->applyValidOrderScope($todayQuery, 'orders.status');

            $tongDon = (clone $todayQuery)->count() ?? 0;
            if ($amountCol) {
                $doanhThu = (float) ((clone $todayQuery)->sum('orders.' . $amountCol) ?? 0);
            }
        }

        // 2. Món ăn bán chạy & Ít được lên món (lọc theo chi nhánh nếu có liên kết)
        $monBanChay = null;
        $monItOrder = null;
        if (Schema::hasTable('order_items') && Schema::hasTable('menu_items')) {
            $itemQuery = DB::table('order_items')
                ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id');

            if (Schema::hasColumn('order_items', 'order_id') && Schema::hasTable('orders')) {
                $itemQuery->join('orders', 'order_items.order_id', '=', 'orders.id');
                $this->applyValidOrderScope($itemQuery, 'orders.status');
                if ($hasBranchOnOrders) {
                    $itemQuery->where('orders.branch_id', $branchId);
                }
            } elseif (Schema::hasColumn('order_items', 'branch_id')) {
                $itemQuery->where('order_items.branch_id', $branchId);
            }

            $qtyCol = Schema::hasColumn('order_items', 'quantity') ? 'order_items.quantity' : '1';

            $topItems = $itemQuery
                ->select(
                    'menu_items.id',
                    'menu_items.name',
                    DB::raw("SUM($qtyCol) as total_qty")
                )
                ->groupBy('menu_items.id', 'menu_items.name')
                ->orderByDesc('total_qty')
                ->get();

            if ($topItems->isNotEmpty()) {
                $firstItem = $topItems->first();
                $lastItem = $topItems->last();

                $monBanChay = (object) [
                    'dish' => (object) ['name' => $firstItem->name],
                    'total_qty' => (int) $firstItem->total_qty,
                ];

                $monItOrder = (object) [
                    'dish' => (object) ['name' => $lastItem->name],
                    'total_qty' => (int) $lastItem->total_qty,
                ];
            }
        }

        // 3. Biểu đồ doanh thu:
        // - week: các ngày trong tuần (Thứ 2 -> Chủ Nhật)
        // - month: các tuần trong tháng (Tuần 1 -> Tuần 4/5)
        // - year: các tháng trong năm (Tháng 1 -> Tháng 12)
        $chartLabels = [];
        $chartData = [];

        if ($filter === 'year') {
            $year = Carbon::now()->year;
            for ($m = 1; $m <= 12; $m++) {
                $chartLabels[] = 'Tháng ' . $m;
                $val = 0;
                if (Schema::hasTable('orders') && $amountCol) {
                    $q = DB::table('orders')
                        ->whereYear('orders.created_at', $year)
                        ->whereMonth('orders.created_at', $m);
                    if ($hasBranchOnOrders) {
                        $q->where('orders.branch_id', $branchId);
                    }
                    $this->applyValidOrderScope($q, 'orders.status');
                    $val = (float) ($q->sum('orders.' . $amountCol) ?? 0);
                }
                $chartData[] = $val;
            }
        } elseif ($filter === 'month') {
            $now = Carbon::now();
            $year = $now->year;
            $month = $now->month;
            $daysInMonth = $now->daysInMonth;
            $weekNum = 1;

            for ($startDay = 1; $startDay <= $daysInMonth; $startDay += 7) {
                $endDay = min($startDay + 6, $daysInMonth);
                $chartLabels[] = 'Tuần ' . $weekNum;

                $val = 0;
                if (Schema::hasTable('orders') && $amountCol) {
                    $startAt = Carbon::createFromDate($year, $month, $startDay)->startOfDay();
                    $endAt = Carbon::createFromDate($year, $month, $endDay)->endOfDay();

                    $q = DB::table('orders')
                        ->whereBetween('orders.created_at', [$startAt, $endAt]);
                    if ($hasBranchOnOrders) {
                        $q->where('orders.branch_id', $branchId);
                    }
                    $this->applyValidOrderScope($q, 'orders.status');
                    $val = (float) ($q->sum('orders.' . $amountCol) ?? 0);
                }

                $chartData[] = $val;
                $weekNum++;
            }
        } else {
            $dayNames = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ Nhật'];
            $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);

            for ($i = 0; $i < 7; $i++) {
                $date = (clone $startOfWeek)->addDays($i);
                $chartLabels[] = $dayNames[$i];

                $val = 0;
                if (Schema::hasTable('orders') && $amountCol) {
                    $q = DB::table('orders')->whereDate('orders.created_at', $date);
                    if ($hasBranchOnOrders) {
                        $q->where('orders.branch_id', $branchId);
                    }
                    $this->applyValidOrderScope($q, 'orders.status');
                    $val = (float) ($q->sum('orders.' . $amountCol) ?? 0);
                }

                $chartData[] = $val;
            }
        }

        // 4. Sơ đồ trạng thái bàn ăn (dining_tables)
        $danhSachBan = [];
        if (Schema::hasTable('dining_tables')) {
            $tableQuery = DB::table('dining_tables');
            if (Schema::hasColumn('dining_tables', 'branch_id')) {
                $tableQuery->where('branch_id', $branchId);
            }
            $danhSachBan = $tableQuery->get();
        }

        // 5. Đơn hàng gần đây (orders)
        $donHangs = [];
        if (Schema::hasTable('orders')) {
            $orderQuery = DB::table('orders')->orderByDesc('orders.created_at')->limit(5);
            if ($hasBranchOnOrders) {
                $orderQuery->where('orders.branch_id', $branchId);
            }
            $donHangs = $orderQuery->get();
        }

        // 6. Cơ cấu phương thức thanh toán (payments / orders)
        $paymentStats = ['cash' => 0, 'transfer' => 0];
        if (Schema::hasTable('payments')) {
            $pq = DB::table('payments');
            if (Schema::hasColumn('payments', 'branch_id')) {
                $pq->where('payments.branch_id', $branchId);
            }
            $pAmountCol = Schema::hasColumn('payments', 'amount')
                ? 'amount'
                : (Schema::hasColumn('payments', 'total_amount') ? 'total_amount' : null);

            if ($pAmountCol && Schema::hasColumn('payments', 'method')) {
                $paymentStats['cash'] = (float) ((clone $pq)->where('payments.method', 'cash')->sum('payments.' . $pAmountCol) ?? 0);
                $paymentStats['transfer'] = (float) ((clone $pq)->whereIn('payments.method', ['transfer', 'qr', 'banking'])->sum('payments.' . $pAmountCol) ?? 0);
            }
        } elseif (Schema::hasTable('orders') && Schema::hasColumn('orders', 'payment_method') && $amountCol) {
            $oq = DB::table('orders');
            if ($hasBranchOnOrders) {
                $oq->where('orders.branch_id', $branchId);
            }
            $this->applyValidOrderScope($oq, 'orders.status');
            $paymentStats['cash'] = (float) ((clone $oq)->where('orders.payment_method', 'cash')->sum('orders.' . $amountCol) ?? 0);
            $paymentStats['transfer'] = (float) ((clone $oq)->whereIn('orders.payment_method', ['transfer', 'qr', 'banking'])->sum('orders.' . $amountCol) ?? 0);
        }

        return view('admin.dashboard', compact(
            'branches',
            'branchId',
            'filter',
            'doanhThu',
            'tongDon',
            'monBanChay',
            'monItOrder',
            'chartLabels',
            'chartData',
            'danhSachBan',
            'donHangs',
            'paymentStats'
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

        $tongDoanhThuKy = 0;
        $tongSoTo = 0;
        if (Schema::hasTable('orders')) {
            $q = DB::table('orders')->whereBetween('orders.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
            if (Schema::hasColumn('orders', 'branch_id')) {
                $q->where('orders.branch_id', $branchId);
            }
            $tongDoanhThuKy = (clone $q)->sum('orders.total_amount') ?? 0;
        }

        if (Schema::hasTable('order_items')) {
            $qi = DB::table('order_items')->whereBetween('order_items.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
            $tongSoTo = (clone $qi)->sum('order_items.quantity') ?? 0;
        }

        $topMonAn = [];
        if (Schema::hasTable('order_items') && Schema::hasTable('menu_items')) {
            $priceColumn = null;
            foreach (['price', 'cost', 'selling_price', 'unit_price'] as $col) {
                if (Schema::hasColumn('menu_items', $col)) {
                    $priceColumn = $col;
                    break;
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
                ->map(function ($item) {
                    return (object) [
                        'dish' => (object) ['name' => $item->name],
                        'total_qty' => $item->total_qty,
                        'total_revenue' => $item->total_revenue ?? 0,
                    ];
                });
        }

        $ingredients = [];
        if (Schema::hasTable('ingredients')) {
            $ingQuery = DB::table('ingredients');
            if (Schema::hasColumn('ingredients', 'branch_id')) {
                $ingQuery->where('branch_id', $branchId);
            }
            $ingredients = $ingQuery->get();
        }

        return view('admin.reports', compact(
            'branches',
            'branchId',
            'fromDate',
            'toDate',
            'tongDoanhThuKy',
            'tongSoTo',
            'topMonAn',
            'ingredients'
        ));
    }

    /**
     * Xuất báo cáo ra file Excel (CSV Streaming)
     */
    public function exportExcel(Request $request)
    {
        $fileName = 'bao-cao-kinh-doanh-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['ID Đơn Hàng', 'Chi Nhánh ID', 'Tổng Tiền (VNĐ)', 'Ngày Tạo']);

            if (Schema::hasTable('orders')) {
                $orders = DB::table('orders')->select('id', 'branch_id', 'total_amount', 'created_at')->get();
                foreach ($orders as $order) {
                    fputcsv($file, [
                        $order->id,
                        $order->branch_id ?? 1,
                        $order->total_amount,
                        $order->created_at,
                    ]);
                }
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * API thống kê phương thức thanh toán cho Dashboard
     */
    public function getPaymentMethodStats(Request $request)
    {
        $branchId = session('current_branch_id', $request->input('branch_id', 1));
        $cashTotal = 0;
        $transferTotal = 0;

        if (Schema::hasTable('payments')) {
            $query = DB::table('payments');
            if ($branchId && Schema::hasColumn('payments', 'branch_id')) {
                $query->where('branch_id', $branchId);
            }
            $cashTotal = (clone $query)->where('method', 'cash')->sum('amount') ?? 0;
            $transferTotal = (clone $query)->whereIn('method', ['transfer', 'qr', 'banking'])->sum('amount') ?? 0;
        } elseif (Schema::hasTable('orders') && Schema::hasColumn('orders', 'payment_method')) {
            $query = DB::table('orders');
            if ($branchId && Schema::hasColumn('orders', 'branch_id')) {
                $query->where('branch_id', $branchId);
            }
            $cashTotal = (clone $query)->where('payment_method', 'cash')->sum('total_amount') ?? 0;
            $transferTotal = (clone $query)->whereIn('payment_method', ['transfer', 'qr'])->sum('total_amount') ?? 0;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'cash' => $cashTotal,
                'transfer' => $transferTotal,
            ],
        ]);
    }

    /**
     * API lấy báo cáo trạng thái kho nguyên liệu
     */
    public function getFoodStorageReport(Request $request)
    {
        $branchId = session('current_branch_id', $request->input('branch_id', 1));
        $ingredients = collect();

        if (Schema::hasTable('ingredients')) {
            $query = DB::table('ingredients');
            if ($branchId && Schema::hasColumn('ingredients', 'branch_id')) {
                $query->where('branch_id', $branchId);
            }
            $ingredients = $query->select('name', 'stock_quantity', 'unit', 'quality_status')->get();
        }

        return response()->json([
            'status' => 'success',
            'data' => $ingredients,
        ]);
    }
}
