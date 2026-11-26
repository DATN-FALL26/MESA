<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BranchStatus;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Services\Auth\BranchAccessService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __construct(private BranchAccessService $branchAccess) {}

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

    private function resolveOrderAmountColumn(): ?string
    {
        if (!Schema::hasTable('orders')) {
            return null;
        }

        foreach (['total_amount', 'grand_total', 'final_amount', 'amount', 'total'] as $col) {
            if (Schema::hasColumn('orders', $col)) {
                return $col;
            }
        }

        return null;
    }

    private function resolveMenuPriceColumn(): ?string
    {
        if (!Schema::hasTable('menu_items')) {
            return null;
        }

        foreach (['price', 'base_price', 'selling_price', 'unit_price', 'default_price', 'cost', 'amount'] as $col) {
            if (Schema::hasColumn('menu_items', $col)) {
                return $col;
            }
        }

        return null;
    }

    private function resolveOrderItemUnitPriceColumn(): ?string
    {
        if (!Schema::hasTable('order_items')) {
            return null;
        }

        foreach (['unit_price', 'price', 'selling_price', 'item_price', 'amount'] as $col) {
            if (Schema::hasColumn('order_items', $col)) {
                return $col;
            }
        }

        return null;
    }

    private function resolveOrderItemSubtotalColumn(): ?string
    {
        if (!Schema::hasTable('order_items')) {
            return null;
        }

        foreach (['subtotal', 'line_total', 'total_price', 'total_amount'] as $col) {
            if (Schema::hasColumn('order_items', $col)) {
                return $col;
            }
        }

        return null;
    }

    private function resolveRealCogs(int $branchId, string $fromDate, string $toDate): float
    {
        $fromDt = $fromDate . ' 00:00:00';
        $toDt = $toDate . ' 23:59:59';

        if (Schema::hasTable('stock_movements')) {
            $smQuery = DB::table('stock_movements');

            if (Schema::hasColumn('stock_movements', 'created_at')) {
                $smQuery->whereBetween('stock_movements.created_at', [$fromDt, $toDt]);
            }

            if (Schema::hasColumn('stock_movements', 'type')) {
                $smQuery->whereIn('stock_movements.type', ['out', 'export', 'sale', 'order', 'xuat', 'xuat_kho']);
            } elseif (Schema::hasColumn('stock_movements', 'movement_type')) {
                $smQuery->whereIn('stock_movements.movement_type', ['out', 'export', 'sale', 'order', 'xuat', 'xuat_kho']);
            }

            if (Schema::hasColumn('stock_movements', 'branch_id')) {
                $smQuery->where('stock_movements.branch_id', $branchId);
            } elseif (Schema::hasColumn('stock_movements', 'warehouse_id') && Schema::hasTable('warehouses')) {
                $smQuery->join('warehouses', 'stock_movements.warehouse_id', '=', 'warehouses.id');
                if (Schema::hasColumn('warehouses', 'branch_id')) {
                    $smQuery->where('warehouses.branch_id', $branchId);
                }
            }

            $costCol = null;
            foreach (['total_cost', 'cost_amount', 'amount', 'value'] as $col) {
                if (Schema::hasColumn('stock_movements', $col)) {
                    $costCol = 'stock_movements.' . $col;
                    break;
                }
            }

            if ($costCol) {
                $val = (float) ($smQuery->sum($costCol) ?? 0);
                if ($val > 0) {
                    return $val;
                }
            }

            $qtyCol = null;
            foreach (['quantity', 'qty', 'change_quantity'] as $col) {
                if (Schema::hasColumn('stock_movements', $col)) {
                    $qtyCol = 'stock_movements.' . $col;
                    break;
                }
            }

            $unitCostCol = null;
            if (Schema::hasColumn('stock_movements', 'unit_cost')) {
                $unitCostCol = 'stock_movements.unit_cost';
            } elseif (Schema::hasColumn('stock_movements', 'cost_price')) {
                $unitCostCol = 'stock_movements.cost_price';
            } elseif (Schema::hasTable('ingredients') && Schema::hasColumn('stock_movements', 'ingredient_id')) {
                $smQuery->leftJoin('ingredients', 'stock_movements.ingredient_id', '=', 'ingredients.id');
                foreach (['cost_price', 'unit_cost', 'price', 'cost'] as $col) {
                    if (Schema::hasColumn('ingredients', $col)) {
                        $unitCostCol = 'ingredients.' . $col;
                        break;
                    }
                }
            }

            if ($qtyCol && $unitCostCol) {
                $val = (float) ($smQuery->sum(DB::raw("ABS($qtyCol) * COALESCE($unitCostCol, 0)")) ?? 0);
                if ($val > 0) {
                    return $val;
                }
            }
        }

        return 0.0;
    }

    public function index(Request $request)
    {
        $user = $this->currentUser($request);
        $branchId = $this->resolveBranchId($request, $user);
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
        $doanhThu = 0;
        $tongDon = 0;
        $soBatPhoDaBan = 0;
        $monBanChay = null;
        $monItOrder = null;
        $chartLabels = [];
        $chartData = [];
        $donHangs = collect();
        $paymentStats = ['cash' => 0, 'transfer' => 0];

        $hasBranchOnOrders = Schema::hasTable('orders') && Schema::hasColumn('orders', 'branch_id');
        $amountCol = $this->resolveOrderAmountColumn();

        if (Schema::hasTable('orders')) {
            $todayQuery = DB::table('orders')->whereDate('orders.created_at', Carbon::today());
            if ($hasBranchOnOrders) {
                $todayQuery->where('orders.branch_id', $branchId);
            }
            $this->applyValidOrderScope($todayQuery, 'orders.status');

            $tongDon = (int) ((clone $todayQuery)->count() ?? 0);
            if ($amountCol) {
                $doanhThu = (float) ((clone $todayQuery)->sum('orders.' . $amountCol) ?? 0);
            }
        }

        if (Schema::hasTable('order_items') && Schema::hasTable('menu_items')) {
            $qtyCol = Schema::hasColumn('order_items', 'quantity') ? 'order_items.quantity' : '1';
            $hasOrderItemsCreatedAt = Schema::hasColumn('order_items', 'created_at');
            $hasOrdersTable = Schema::hasTable('orders') && Schema::hasColumn('order_items', 'order_id');

            $phoQuery = DB::table('order_items')
                ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
                ->where(function ($q) {
                    $q->whereRaw('LOWER(menu_items.name) LIKE ?', ['%phở%'])
                        ->orWhereRaw('LOWER(menu_items.name) LIKE ?', ['%pho%']);
                });

            if ($hasOrdersTable) {
                $phoQuery->join('orders', 'order_items.order_id', '=', 'orders.id');
                $this->applyValidOrderScope($phoQuery, 'orders.status');
                if ($hasBranchOnOrders) {
                    $phoQuery->where('orders.branch_id', $branchId);
                }
                if ($hasOrderItemsCreatedAt) {
                    $phoQuery->whereDate('order_items.created_at', Carbon::today());
                } elseif (Schema::hasColumn('orders', 'created_at')) {
                    $phoQuery->whereDate('orders.created_at', Carbon::today());
                }
            } else {
                if (Schema::hasColumn('order_items', 'branch_id')) {
                    $phoQuery->where('order_items.branch_id', $branchId);
                }
                if ($hasOrderItemsCreatedAt) {
                    $phoQuery->whereDate('order_items.created_at', Carbon::today());
                }
            }

            $soBatPhoDaBan = (int) ($phoQuery->sum(DB::raw($qtyCol)) ?? 0);
        }

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

        if (Schema::hasTable('orders')) {
            $orderQuery = DB::table('orders')->orderByDesc('orders.created_at')->limit(5);
            if ($hasBranchOnOrders) {
                $orderQuery->where('orders.branch_id', $branchId);
            }
            $donHangs = $orderQuery->get();
        }

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
            'soBatPhoDaBan',
            'monBanChay',
            'monItOrder',
            'chartLabels',
            'chartData',
            'donHangs',
            'paymentStats'
        ));
    }

    public function orderDetail(Request $request, $id)
    {
        $user = $this->currentUser($request);
        $branchId = $this->resolveBranchId($request, $user);
        abort_if($branchId === false, 422, 'Vui lòng chọn chi nhánh trước khi xem đơn hàng.');

        if (!Schema::hasTable('orders')) {
            return response()->json(['message' => 'Không tìm thấy bảng orders'], 404);
        }

        $order = DB::table('orders')->where('id', $id)->first();
        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
        }

        abort_unless(isset($order->branch_id), 404);
        $this->branchAccess->authorizeBranch(
            $user,
            (int) $order->branch_id,
            [ConstantHelper::PERM_DASHBOARD_VIEW]
        );
        abort_unless((int) $order->branch_id === $branchId, 404);

        $branchName = null;
        if (Schema::hasTable('branches') && isset($order->branch_id)) {
            $branch = DB::table('branches')->where('id', $order->branch_id)->first();
            $branchName = $branch->name ?? null;
        }

        $items = [];
        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'order_id')) {
            $q = DB::table('order_items')->where('order_items.order_id', $id);
            $qtyExpr = Schema::hasColumn('order_items', 'quantity') ? 'order_items.quantity' : '1';
            $itemPriceCol = $this->resolveOrderItemUnitPriceColumn();
            $subtotalCol = $this->resolveOrderItemSubtotalColumn();

            $hasMenu = Schema::hasTable('menu_items') && Schema::hasColumn('order_items', 'menu_item_id');
            if ($hasMenu) {
                $q->leftJoin('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id');
                $menuPriceCol = $this->resolveMenuPriceColumn();

                $candidates = [];
                if ($itemPriceCol) {
                    $candidates[] = 'order_items.' . $itemPriceCol;
                }
                if ($subtotalCol) {
                    $candidates[] = '(order_items.' . $subtotalCol . ' / NULLIF(' . $qtyExpr . ', 0))';
                }
                if ($menuPriceCol) {
                    $candidates[] = 'menu_items.' . $menuPriceCol;
                }
                $candidates[] = '0';

                $unitPriceExpr = count($candidates) === 1
                    ? $candidates[0]
                    : 'COALESCE(' . implode(', ', $candidates) . ')';

                $items = $q->select(
                    'order_items.id',
                    DB::raw("COALESCE(menu_items.name, CONCAT('Món #', order_items.menu_item_id)) as name"),
                    DB::raw($qtyExpr . ' as quantity'),
                    DB::raw($unitPriceExpr . ' as unit_price')
                )->get();
            } else {
                $candidates = [];
                if ($itemPriceCol) {
                    $candidates[] = 'order_items.' . $itemPriceCol;
                }
                if ($subtotalCol) {
                    $candidates[] = '(order_items.' . $subtotalCol . ' / NULLIF(' . $qtyExpr . ', 0))';
                }
                $candidates[] = '0';

                $unitPriceExpr = count($candidates) === 1
                    ? $candidates[0]
                    : 'COALESCE(' . implode(', ', $candidates) . ')';

                $items = $q->select(
                    'id',
                    DB::raw("'Món ăn' as name"),
                    DB::raw($qtyExpr . ' as quantity'),
                    DB::raw($unitPriceExpr . ' as unit_price')
                )->get();
            }
        }

        $amountCol = $this->resolveOrderAmountColumn();
        $totalAmount = $amountCol ? (float) ($order->{$amountCol} ?? 0) : 0;

        return response()->json([
            'id' => $order->id,
            'code' => $order->order_code ?? $order->code ?? ('#' . $order->id),
            'status' => $order->status ?? 'Đã ghi nhận',
            'branch_name' => $branchName,
            'payment_method' => $order->payment_method ?? null,
            'created_at' => $order->created_at ?? null,
            'total_amount' => $totalAmount,
            'items' => $items,
        ]);
    }

    public function reports(Request $request)
    {
        $user = $this->currentUser($request);
        $branchId = $this->resolveBranchId($request, $user);
        if ($branchId === false) {
            return view('branches.select', [
                'branches' => $this->selectableBranches($user),
                'action' => route('admin.reports'),
            ]);
        }

        $fromDate = $request->input('from_date', Carbon::today()->subDays(7)->format('Y-m-d'));
        $toDate = $request->input('to_date', Carbon::today()->format('Y-m-d'));

        $branches = $this->selectableBranches($user);

        $tongDoanhThuKy = 0.0;
        $tongSoTo = 0;
        $hasBranchOnOrders = Schema::hasTable('orders') && Schema::hasColumn('orders', 'branch_id');

        if (Schema::hasTable('orders')) {
            $amountCol = $this->resolveOrderAmountColumn();
            $q = DB::table('orders')->whereBetween('orders.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
            if ($hasBranchOnOrders) {
                $q->where('orders.branch_id', $branchId);
            }
            $this->applyValidOrderScope($q, 'orders.status');
            if ($amountCol) {
                $tongDoanhThuKy = (float) ((clone $q)->sum('orders.' . $amountCol) ?? 0);
            }
        }

        if (Schema::hasTable('order_items')) {
            $qi = DB::table('order_items');
            if (Schema::hasColumn('order_items', 'created_at')) {
                $qi->whereBetween('order_items.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
            }
            if (Schema::hasColumn('order_items', 'order_id') && Schema::hasTable('orders')) {
                $qi->join('orders', 'order_items.order_id', '=', 'orders.id');
                $this->applyValidOrderScope($qi, 'orders.status');
                if ($hasBranchOnOrders) {
                    $qi->where('orders.branch_id', $branchId);
                }
                if (!Schema::hasColumn('order_items', 'created_at') && Schema::hasColumn('orders', 'created_at')) {
                    $qi->whereBetween('orders.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
                }
            } elseif (Schema::hasColumn('order_items', 'branch_id')) {
                $qi->where('order_items.branch_id', $branchId);
            }
            $qtyCol = Schema::hasColumn('order_items', 'quantity') ? 'order_items.quantity' : '1';
            $tongSoTo = (int) ((clone $qi)->sum(DB::raw($qtyCol)) ?? 0);
        }

        $topMonAn = collect();
        if (Schema::hasTable('order_items') && Schema::hasTable('menu_items')) {
            $qtyExpr = Schema::hasColumn('order_items', 'quantity') ? 'order_items.quantity' : '1';
            $itemPriceCol = $this->resolveOrderItemUnitPriceColumn();
            $subtotalCol = $this->resolveOrderItemSubtotalColumn();
            $menuPriceCol = $this->resolveMenuPriceColumn();

            $candidates = [];
            if ($itemPriceCol) {
                $candidates[] = 'order_items.' . $itemPriceCol;
            }
            if ($menuPriceCol) {
                $candidates[] = 'menu_items.' . $menuPriceCol;
            }

            if ($subtotalCol) {
                $revExpr = 'SUM(order_items.' . $subtotalCol . ')';
            } elseif (!empty($candidates)) {
                $unitExpr = count($candidates) === 1
                    ? $candidates[0]
                    : 'COALESCE(' . implode(', ', $candidates) . ')';
                $revExpr = 'SUM(' . $qtyExpr . ' * ' . $unitExpr . ')';
            } else {
                $revExpr = '0';
            }

            $selectRaw = 'SUM(' . $qtyExpr . ') as total_qty, ' . $revExpr . ' as total_revenue';

            $topQuery = DB::table('order_items')
                ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id');

            if (Schema::hasColumn('order_items', 'created_at')) {
                $topQuery->whereBetween('order_items.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
            }

            if (Schema::hasColumn('order_items', 'order_id') && Schema::hasTable('orders')) {
                $topQuery->join('orders', 'order_items.order_id', '=', 'orders.id');
                $this->applyValidOrderScope($topQuery, 'orders.status');
                if ($hasBranchOnOrders) {
                    $topQuery->where('orders.branch_id', $branchId);
                }
                if (!Schema::hasColumn('order_items', 'created_at') && Schema::hasColumn('orders', 'created_at')) {
                    $topQuery->whereBetween('orders.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
                }
            } elseif (Schema::hasColumn('order_items', 'branch_id')) {
                $topQuery->where('order_items.branch_id', $branchId);
            }

            $topMonAn = $topQuery
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
                        'total_qty' => (int) $item->total_qty,
                        'total_revenue' => (float) ($item->total_revenue ?? 0),
                    ];
                });
        }

        $cogs = $this->resolveRealCogs($branchId, $fromDate, $toDate);
        if ($cogs <= 0 && $tongDoanhThuKy > 0) {
            $cogs = round($tongDoanhThuKy * 0.32, -3);
        }

        $loiNhuanGop = $tongDoanhThuKy - $cogs;
        $bienLoiNhuan = $tongDoanhThuKy > 0 ? round(($loiNhuanGop / $tongDoanhThuKy) * 100, 1) : 0.0;
        $tyLeFoodCost = $tongDoanhThuKy > 0 ? round(($cogs / $tongDoanhThuKy) * 100, 1) : 0.0;

        $ingredients = collect();
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
            'cogs',
            'loiNhuanGop',
            'bienLoiNhuan',
            'tyLeFoodCost',
            'topMonAn',
            'ingredients'
        ));
    }

    public function exportExcel(Request $request)
    {
        $branchId = $this->resolveBranchId($request, $this->currentUser($request));
        abort_if($branchId === false, 422, 'Vui lòng chọn chi nhánh trước khi xuất báo cáo.');

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $fileName = 'bao-cao-kinh-doanh-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($branchId, $fromDate, $toDate) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['ID Đơn Hàng', 'Chi Nhánh ID', 'Tổng Tiền (VNĐ)', 'Ngày Tạo']);

            if (Schema::hasTable('orders')) {
                $amountCol = $this->resolveOrderAmountColumn() ?? 'total_amount';
                $query = DB::table('orders');

                if ($branchId && Schema::hasColumn('orders', 'branch_id')) {
                    $query->where('branch_id', $branchId);
                }
                if ($fromDate && $toDate) {
                    $query->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
                }
                $this->applyValidOrderScope($query, 'status');

                $orders = $query
                    ->select(
                        'id',
                        Schema::hasColumn('orders', 'branch_id') ? 'branch_id' : DB::raw('1 as branch_id'),
                        DB::raw($amountCol . ' as total_amount'),
                        'created_at'
                    )
                    ->orderByDesc('created_at')
                    ->get();

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

    public function getPaymentMethodStats(Request $request)
    {
        $branchId = $this->resolveBranchId($request, $this->currentUser($request));
        abort_if($branchId === false, 422, 'Vui lòng chọn chi nhánh trước khi xem thống kê.');

        $cashTotal = 0;
        $transferTotal = 0;

        if (Schema::hasTable('payments')) {
            $query = DB::table('payments');
            if ($branchId && Schema::hasColumn('payments', 'branch_id')) {
                $query->where('branch_id', $branchId);
            }
            $cashTotal = (float) ((clone $query)->where('method', 'cash')->sum('amount') ?? 0);
            $transferTotal = (float) ((clone $query)->whereIn('method', ['transfer', 'qr', 'banking'])->sum('amount') ?? 0);
        } elseif (Schema::hasTable('orders') && Schema::hasColumn('orders', 'payment_method')) {
            $amountCol = $this->resolveOrderAmountColumn() ?? 'total_amount';
            $query = DB::table('orders');
            if ($branchId && Schema::hasColumn('orders', 'branch_id')) {
                $query->where('branch_id', $branchId);
            }
            $this->applyValidOrderScope($query, 'status');
            $cashTotal = (float) ((clone $query)->where('payment_method', 'cash')->sum($amountCol) ?? 0);
            $transferTotal = (float) ((clone $query)->whereIn('payment_method', ['transfer', 'qr', 'banking'])->sum($amountCol) ?? 0);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'cash' => $cashTotal,
                'transfer' => $transferTotal,
            ],
        ]);
    }

    public function getFoodStorageReport(Request $request)
    {
        $branchId = $this->resolveBranchId($request, $this->currentUser($request));
        abort_if($branchId === false, 422, 'Vui lòng chọn chi nhánh trước khi xem báo cáo.');

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

    private function currentUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function resolveBranchId(Request $request, User $user): int|false
    {
        $permissions = [ConstantHelper::PERM_DASHBOARD_VIEW];
        $branchIds = $this->branchAccess->accessibleBranchIds($user, $permissions);

        if (is_array($branchIds) && $branchIds === []) {
            abort(403, 'Bạn chưa được cấp quyền xem chi nhánh nào.');
        }

        if ($request->filled('branch_id')) {
            $branchId = $request->integer('branch_id');
            $this->branchAccess->authorizeBranch($user, $branchId, $permissions);
            $request->session()->put('branch_id', $branchId);

            return $branchId;
        }

        $sessionBranchId = $request->session()->get('branch_id');
        if (is_numeric($sessionBranchId)) {
            $sessionBranchId = (int) $sessionBranchId;
            if ($this->branchAccess->canAccessBranch($user, $sessionBranchId, $permissions)) {
                return $sessionBranchId;
            }

            $request->session()->forget('branch_id');
        }

        if (is_array($branchIds) && count($branchIds) > 1) {
            return false;
        }

        $branch = $this->selectableBranches($user)->first();
        abort_if($branch === null, 403, 'Hiện không có chi nhánh đang hoạt động để truy cập.');

        $branchId = (int) $branch->getKey();
        $request->session()->put('branch_id', $branchId);

        return $branchId;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Branch>
     */
    private function selectableBranches(User $user): \Illuminate\Database\Eloquent\Collection
    {
        $branchIds = $this->branchAccess->accessibleBranchIds(
            $user,
            [ConstantHelper::PERM_DASHBOARD_VIEW]
        );

        return Branch::query()
            ->where('status', BranchStatus::ACTIVE)
            ->when(is_array($branchIds), fn ($query) => $query->whereIn('id', $branchIds))
            ->orderBy('name')
            ->get();
    }
}
