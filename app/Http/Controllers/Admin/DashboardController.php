<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BranchStatus;
use App\Enums\UserStatus;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\BranchAccessService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __construct(private BranchAccessService $branchAccess) {}

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
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $branchId = $this->resolveBranchId($request);
        if ($branchId === false) {
            return view('branches.select', [
                'branches' => $this->selectableBranches($user),
                'action' => route('admin.dashboard'),
            ]);
        }

        $filter = $request->input('filter', 'week');
        if (!in_array($filter, ['week', 'month', 'year'], true)) {
            $filter = 'week';
        }

        $branches = $this->selectableBranches($user);

        $hasBranchOnOrders = Schema::hasTable('orders') && Schema::hasColumn('orders', 'branch_id');
        $amountCol = Schema::hasTable('orders') ? $this->resolveOrderAmountColumn() : null;

        // 1. Thống kê tổng quan hôm nay
        $doanhThu = 0;
        $tongDon = 0;
        if (Schema::hasTable('orders')) {
            $todayQuery = DB::table('orders')->whereDate('orders.created_at', Carbon::today());
            if ($hasBranchOnOrders) {
                $todayQuery->where('orders.branch_id', $branchId);
            } else {
                $todayQuery->whereRaw('1 = 0');
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
                } else {
                    $itemQuery->whereRaw('1 = 0');
                }
            } elseif (Schema::hasColumn('order_items', 'branch_id')) {
                $itemQuery->where('order_items.branch_id', $branchId);
            } else {
                $itemQuery->whereRaw('1 = 0');
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
                    } else {
                        $q->whereRaw('1 = 0');
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
                    } else {
                        $q->whereRaw('1 = 0');
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
                    } else {
                        $q->whereRaw('1 = 0');
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
            } else {
                $tableQuery->whereRaw('1 = 0');
            }
            $danhSachBan = $tableQuery->get();
        }

        // 5. Đơn hàng gần đây (orders)
        $donHangs = [];
        if (Schema::hasTable('orders')) {
            $orderQuery = DB::table('orders')->orderByDesc('orders.created_at')->limit(5);
            if ($hasBranchOnOrders) {
                $orderQuery->where('orders.branch_id', $branchId);
            } else {
                $orderQuery->whereRaw('1 = 0');
            }
            $donHangs = $orderQuery->get();
        }

        // 6. Cơ cấu phương thức thanh toán (payments / orders)
        $paymentStats = ['cash' => 0, 'transfer' => 0];
        if (Schema::hasTable('payments')) {
            $pq = DB::table('payments');
            if (Schema::hasColumn('payments', 'branch_id')) {
                $pq->where('payments.branch_id', $branchId);
            } elseif (Schema::hasColumn('payments', 'order_id') && $hasBranchOnOrders) {
                $pq->join('orders', 'payments.order_id', '=', 'orders.id')
                    ->where('orders.branch_id', $branchId);
            } else {
                $pq->whereRaw('1 = 0');
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
            } else {
                $oq->whereRaw('1 = 0');
            }
            $this->applyValidOrderScope($oq, 'orders.status');
            $paymentStats['cash'] = (float) ((clone $oq)->where('orders.payment_method', 'cash')->sum('orders.' . $amountCol) ?? 0);
            $paymentStats['transfer'] = (float) ((clone $oq)->whereIn('orders.payment_method', ['transfer', 'qr', 'banking'])->sum('orders.' . $amountCol) ?? 0);
        }

        $currentRoles = $user->activeRoles()->orderBy('name')->get();
        $totalUsers = User::query()
            ->whereHas('userRoles', fn ($query) => $query
                ->where('scope_type', 'BRANCH')
                ->where('scope_id', $branchId)
                ->where('valid_from', '<=', now())
                ->where(fn ($validityQuery) => $validityQuery
                    ->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', now())))
            ->distinct()
            ->count('users.id');
        $activeUsers = User::query()
            ->where('status', UserStatus::ACTIVE->value)
            ->whereHas('userRoles', fn ($query) => $query
                ->where('scope_type', 'BRANCH')
                ->where('scope_id', $branchId)
                ->where('valid_from', '<=', now())
                ->where(fn ($validityQuery) => $validityQuery
                    ->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', now())))
            ->distinct()
            ->count('users.id');
        $totalRoles = Role::query()->count();
        $totalPermissions = Permission::query()->count();
        $recentActivities = AuditLog::query()
            ->where('branch_id', $branchId)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();
        $activityUsers = User::query()
            ->whereIn('id', $recentActivities->pluck('user_id')->filter()->unique())
            ->pluck('full_name', 'id');
        $actionLabels = [
            'create' => 'Đã tạo',
            'update' => 'Đã cập nhật',
            'delete' => 'Đã xóa',
            'status_change' => 'Đã thay đổi trạng thái',
            'login' => 'Đã đăng nhập',
        ];
        $modules = [
            [
                'permission' => ConstantHelper::PERM_USERS_VIEW,
                'route' => 'users.index',
                'title' => 'Quản lý tài khoản',
                'description' => 'Theo dõi tài khoản, thông tin nhân sự và trạng thái hoạt động.',
                'icon' => 'users',
                'color' => 'indigo',
            ],
            [
                'permission' => ConstantHelper::PERM_ROLES_VIEW,
                'route' => 'roles.index',
                'title' => 'Quản lý vai trò',
                'description' => 'Tổ chức vai trò và kiểm soát quyền truy cập theo nhóm.',
                'icon' => 'roles',
                'color' => 'violet',
            ],
            [
                'permission' => ConstantHelper::PERM_PERMISSIONS_VIEW,
                'route' => 'permissions.index',
                'title' => 'Quản lý quyền',
                'description' => 'Tra cứu các quyền chức năng được cấu hình trong hệ thống.',
                'icon' => 'permissions',
                'color' => 'amber',
            ],
        ];
        $title = 'Tổng quan Dashboard Kinh doanh';

        return view('admin.dashboard', compact(
            'user',
            'currentRoles',
            'totalUsers',
            'activeUsers',
            'totalRoles',
            'totalPermissions',
            'recentActivities',
            'activityUsers',
            'actionLabels',
            'modules',
            'title',
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
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $branchId = $this->resolveBranchId($request);
        if ($branchId === false) {
            return view('branches.select', [
                'branches' => $this->selectableBranches($user),
                'action' => route('admin.reports'),
            ]);
        }

        $branches = $this->selectableBranches($user);
        $fromDate = $request->input('from_date', Carbon::today()->subDays(7)->format('Y-m-d'));
        $toDate = $request->input('to_date', Carbon::today()->format('Y-m-d'));

        $tongDoanhThuKy = 0;
        $tongSoTo = 0;
        if (Schema::hasTable('orders')) {
            $q = DB::table('orders')->whereBetween('orders.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
            if (Schema::hasColumn('orders', 'branch_id')) {
                $q->where('orders.branch_id', $branchId);
            } else {
                $q->whereRaw('1 = 0');
            }
            $tongDoanhThuKy = (clone $q)->sum('orders.total_amount') ?? 0;
        }

        if (Schema::hasTable('order_items')) {
            $qi = DB::table('order_items');
            if (Schema::hasTable('orders') && Schema::hasColumn('order_items', 'order_id')) {
                $qi->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->whereBetween('orders.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
                if (Schema::hasColumn('orders', 'branch_id')) {
                    $qi->where('orders.branch_id', $branchId);
                } else {
                    $qi->whereRaw('1 = 0');
                }
            } elseif (Schema::hasColumn('order_items', 'branch_id')) {
                $qi->whereBetween('order_items.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
                    ->where('order_items.branch_id', $branchId);
            } else {
                $qi->whereRaw('1 = 0');
            }
            $tongSoTo = (clone $qi)->sum('order_items.quantity') ?? 0;
        }

        $tongGiaVonKy = 0;
        $tongLoiNhuanKy = 0;
        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'cogs_total')) {
            $qCogs = DB::table('order_items');
            if (Schema::hasTable('orders') && Schema::hasColumn('order_items', 'order_id')) {
                $qCogs->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->whereBetween('orders.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
                if (Schema::hasColumn('orders', 'branch_id')) {
                    $qCogs->where('orders.branch_id', $branchId);
                } else {
                    $qCogs->whereRaw('1 = 0');
                }
            } else {
                $qCogs->whereBetween('order_items.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
                if (Schema::hasColumn('order_items', 'branch_id')) {
                    $qCogs->where('order_items.branch_id', $branchId);
                } else {
                    $qCogs->whereRaw('1 = 0');
                }
            }
            $tongGiaVonKy = (float) ((clone $qCogs)->sum('order_items.cogs_total') ?? 0);
            $tongLoiNhuanKy = (float) ((clone $qCogs)->sum('order_items.gross_profit') ?? 0);
        }

        if ($tongLoiNhuanKy == 0 && $tongDoanhThuKy > 0 && $tongGiaVonKy > 0) {
            $tongLoiNhuanKy = max(0, $tongDoanhThuKy - $tongGiaVonKy);
        }

        $foodCostPercentage = $tongDoanhThuKy > 0 ? round(($tongGiaVonKy / $tongDoanhThuKy) * 100, 1) : 0;
        $marginPercentage = $tongDoanhThuKy > 0 ? round(($tongLoiNhuanKy / $tongDoanhThuKy) * 100, 1) : 0;

        $topMonAn = [];
        if (Schema::hasTable('order_items') && Schema::hasTable('menu_items')) {
            $priceColumn = null;
            foreach (['price', 'cost', 'selling_price', 'unit_price'] as $col) {
                if (Schema::hasColumn('menu_items', $col)) {
                    $priceColumn = $col;
                    break;
                }
            }

            $hasCogs = Schema::hasColumn('order_items', 'cogs_total');
            $cogsSelect = $hasCogs
                ? ', SUM(COALESCE(order_items.cogs_total, 0)) as total_cogs, SUM(COALESCE(order_items.gross_profit, 0)) as total_profit'
                : ', 0 as total_cogs, 0 as total_profit';

            $selectRaw = ($priceColumn
                ? 'SUM(order_items.quantity) as total_qty, SUM(order_items.quantity * menu_items.' . $priceColumn . ') as total_revenue'
                : 'SUM(order_items.quantity) as total_qty, 0 as total_revenue') . $cogsSelect;

            $topItemsQuery = DB::table('order_items')
                ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id');
            if (Schema::hasTable('orders') && Schema::hasColumn('order_items', 'order_id')) {
                $topItemsQuery->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->whereBetween('orders.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
                if (Schema::hasColumn('orders', 'branch_id')) {
                    $topItemsQuery->where('orders.branch_id', $branchId);
                } else {
                    $topItemsQuery->whereRaw('1 = 0');
                }
            } elseif (Schema::hasColumn('order_items', 'branch_id')) {
                $topItemsQuery->whereBetween('order_items.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
                    ->where('order_items.branch_id', $branchId);
            } else {
                $topItemsQuery->whereRaw('1 = 0');
            }

            $topMonAn = $topItemsQuery
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
                        'total_cogs' => (float) ($item->total_cogs ?? 0),
                        'total_profit' => (float) ($item->total_profit ?? 0),
                    ];
                });
        }

        $ingredients = [];
        $alertSummary = [];
        if (Schema::hasTable('ingredients')) {
            $alertService = app(\App\Services\Inventory\StockAlertService::class);
            $stockPaginator = $alertService->getBranchStockOverview((int) $branchId, [
                'search' => $request->query('search'),
                'type' => $request->query('type'),
                'status_filter' => $request->query('status_filter'),
            ], 100);

            $alertSummary = $alertService->getAlertSummary((int) $branchId);

            $todayStr = \Carbon\Carbon::today()->toDateString();
            $threeDaysStr = \Carbon\Carbon::today()->addDays(3)->toDateString();

            $ingredients = collect($stockPaginator->items())->map(function ($item) use ($todayStr, $threeDaysStr) {
                $currentQty = (float) $item->current_quantity;
                $minStock = (float) $item->min_stock;
                $expiry = $item->earliest_expiry;

                if ($currentQty <= 0) {
                    $status = 'Hết hàng';
                    $statusClass = 'danger';
                } elseif ($expiry !== null && $expiry < $todayStr) {
                    $status = 'Đã hết hạn';
                    $statusClass = 'danger';
                } elseif ($expiry !== null && $expiry <= $threeDaysStr) {
                    $status = 'Sắp hết hạn';
                    $statusClass = 'warning';
                } elseif ($currentQty <= $minStock) {
                    $status = 'Sắp hết';
                    $statusClass = 'warning';
                } else {
                    $status = 'Ổn định';
                    $statusClass = 'success';
                }

                return (object) [
                    'id' => $item->ingredient_id,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'ingredient_type' => $item->ingredient_type,
                    'unit' => $item->base_uom,
                    'stock_quantity' => $currentQty,
                    'min_stock' => $minStock,
                    'avg_cost' => (float) $item->avg_cost,
                    'total_value' => (float) $item->total_value,
                    'expiry_date' => $expiry,
                    'active_batches_count' => (int) $item->active_batches_count,
                    'quality_status' => $status,
                    'status_class' => $statusClass,
                ];
            });
        }

        return view('admin.reports', compact(
            'branches',
            'branchId',
            'fromDate',
            'toDate',
            'tongDoanhThuKy',
            'tongSoTo',
            'tongGiaVonKy',
            'tongLoiNhuanKy',
            'foodCostPercentage',
            'marginPercentage',
            'topMonAn',
            'ingredients',
            'alertSummary'
        ));
    }

    /**
     * Xuất báo cáo ra file Excel (CSV Streaming)
     */
    public function exportExcel(Request $request)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $branchId = $this->resolveBranchId($request);
        if ($branchId === false) {
            return view('branches.select', [
                'branches' => $this->selectableBranches($user),
                'action' => route('admin.reports.export'),
            ]);
        }

        $fileName = 'bao-cao-kinh-doanh-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($branchId) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['ID Đơn Hàng', 'Chi Nhánh ID', 'Tổng Tiền (VNĐ)', 'Ngày Tạo']);

            if (Schema::hasTable('orders')) {
                $ordersQuery = DB::table('orders')->select('id', 'branch_id', 'total_amount', 'created_at');
                if (Schema::hasColumn('orders', 'branch_id')) {
                    $ordersQuery->where('branch_id', $branchId);
                } else {
                    $ordersQuery->whereRaw('1 = 0');
                }
                $orders = $ordersQuery->get();
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
        $branchId = $this->resolveBranchId($request);
        abort_if($branchId === false, 422, 'Vui lòng chọn chi nhánh trước khi xem thống kê.');
        $cashTotal = 0;
        $transferTotal = 0;

        if (Schema::hasTable('payments')) {
            $query = DB::table('payments');
            if ($branchId && Schema::hasColumn('payments', 'branch_id')) {
                $query->where('payments.branch_id', $branchId);
            } elseif (
                Schema::hasColumn('payments', 'order_id')
                && Schema::hasTable('orders')
                && Schema::hasColumn('orders', 'branch_id')
            ) {
                $query->join('orders', 'payments.order_id', '=', 'orders.id')
                    ->where('orders.branch_id', $branchId);
            } else {
                $query->whereRaw('1 = 0');
            }
            $cashTotal = (clone $query)->where('payments.method', 'cash')->sum('payments.amount') ?? 0;
            $transferTotal = (clone $query)->whereIn('payments.method', ['transfer', 'qr', 'banking'])->sum('payments.amount') ?? 0;
        } elseif (Schema::hasTable('orders') && Schema::hasColumn('orders', 'payment_method')) {
            $query = DB::table('orders');
            if ($branchId && Schema::hasColumn('orders', 'branch_id')) {
                $query->where('branch_id', $branchId);
            } else {
                $query->whereRaw('1 = 0');
            }
            $cashTotal = (clone $query)->where('payment_method', 'cash')->sum('total_amount') ?? 0;
            $transferTotal = (clone $query)->whereIn('payment_method', ['transfer', 'qr'])->sum('total_amount') ?? 0;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'cash' => (int) $cashTotal,
                'transfer' => (int) $transferTotal,
            ],
        ]);
    }

    /**
     * API lấy báo cáo trạng thái kho nguyên liệu
     */
    public function getFoodStorageReport(Request $request)
    {
        $branchId = $this->resolveBranchId($request);
        abort_if($branchId === false, 422, 'Vui lòng chọn chi nhánh trước khi xem báo cáo.');
        $ingredients = collect();

        if (Schema::hasTable('ingredients')) {
            $alertService = app(\App\Services\Inventory\StockAlertService::class);
            $stockPaginator = $alertService->getBranchStockOverview($branchId, [], 100);

            $todayStr = \Carbon\Carbon::today()->toDateString();
            $threeDaysStr = \Carbon\Carbon::today()->addDays(3)->toDateString();

            $ingredients = collect($stockPaginator->items())->map(function ($item) use ($todayStr, $threeDaysStr) {
                $currentQty = (float) $item->current_quantity;
                $minStock = (float) $item->min_stock;
                $expiry = $item->earliest_expiry;

                if ($currentQty <= 0) {
                    $status = 'Hết hàng';
                } elseif ($expiry !== null && $expiry < $todayStr) {
                    $status = 'Đã hết hạn';
                } elseif ($expiry !== null && $expiry <= $threeDaysStr) {
                    $status = 'Sắp hết hạn';
                } elseif ($currentQty <= $minStock) {
                    $status = 'Sắp hết';
                } else {
                    $status = 'Ổn định';
                }

                return [
                    'id' => $item->ingredient_id,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'stock_quantity' => $currentQty,
                    'unit' => $item->base_uom,
                    'min_stock' => $minStock,
                    'expiry_date' => $expiry,
                    'quality_status' => $status,
                ];
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => $ingredients,
        ]);
    }

    private function resolveBranchId(Request $request): int|false
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $branchIds = $this->branchAccess->accessibleBranchIds($user, [ConstantHelper::PERM_DASHBOARD_VIEW]);
        if (is_array($branchIds) && $branchIds === []) {
            $request->session()->forget('branch_id');
            abort(403, 'Bạn chưa được cấp quyền xem chi nhánh nào.');
        }

        if ($request->filled('branch_id')) {
            $branchId = $request->integer('branch_id');
            $this->branchAccess->authorizeBranch($user, $branchId, [ConstantHelper::PERM_DASHBOARD_VIEW]);
            $request->session()->put('branch_id', $branchId);

            return $branchId;
        }

        $sessionBranchId = $request->session()->get('branch_id');
        if (is_numeric($sessionBranchId)) {
            $sessionBranchId = (int) $sessionBranchId;
            if ($this->branchAccess->canAccessBranch($user, $sessionBranchId, [ConstantHelper::PERM_DASHBOARD_VIEW])) {
                return $sessionBranchId;
            }

            $request->session()->forget('branch_id');
        }

        if (is_array($branchIds)) {
            if (count($branchIds) === 1) {
                $request->session()->put('branch_id', $branchIds[0]);

                return $branchIds[0];
            }

            return false;
        }

        $defaultBranch = $this->selectableBranches($user)->first();
        abort_if($defaultBranch === null, 403, 'Hiện không có chi nhánh đang hoạt động để truy cập.');
        $branchId = (int) $defaultBranch->getKey();
        $request->session()->put('branch_id', $branchId);

        return $branchId;
    }

    private function selectableBranches(User $user): \Illuminate\Database\Eloquent\Collection
    {
        $branchIds = $this->branchAccess->accessibleBranchIds($user, [ConstantHelper::PERM_DASHBOARD_VIEW]);

        return Branch::query()
            ->where('status', BranchStatus::ACTIVE)
            ->when(is_array($branchIds), fn ($query) => $query->whereIn('id', $branchIds))
            ->orderBy('name')
            ->get();
    }
}
