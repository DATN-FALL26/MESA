<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Inventory;

use App\Enums\WarehouseType;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\MenuItem;
use App\Models\Warehouse;
use App\Services\Inventory\CostCalculationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuCostAnalysisController extends Controller
{
    public function __construct(
        protected CostCalculationService $costCalculator,
    ) {}

    /**
     * Bảng phân tích Giá vốn định lượng (Recipe Cost) và Lợi nhuận dự kiến trên từng món ăn.
     */
    public function index(Request $request): JsonResponse
    {
        $warehouseId = $this->resolveWarehouseId($request);
        if ($warehouseId === null) {
            return response()->json([
                'message' => 'Không tìm thấy kho hoạt động để tra cứu giá vốn nguyên liệu.',
            ], 404);
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 20)));
        $categoryId = $request->query('category_id');
        $search = $request->query('search');

        $query = MenuItem::query()
            ->with(['variants'])
            ->where('is_active', true);

        if (! empty($categoryId)) {
            $query->where('category_id', (int) $categoryId);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate($perPage);

        $menuItemIds = collect($paginator->items())->pluck('id')->toArray();

        // Tính toán chi phí định lượng và lợi nhuận cho toàn bộ danh sách món ăn (Tối ưu hóa: 3 queries O(1))
        $dishCosts = $this->costCalculator->calculateBatchDishCosts($menuItemIds, $warehouseId);

        return response()->json([
            'warehouse_id' => $warehouseId,
            'data' => $dishCosts->map(fn ($item) => $item->toArray())->values(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Chi tiết chi phí nguyên liệu và phân tích lợi nhuận của một món ăn cụ thể (kèm các biến thể).
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $warehouseId = $this->resolveWarehouseId($request);
        if ($warehouseId === null) {
            return response()->json([
                'message' => 'Không tìm thấy kho hoạt động để tra cứu giá vốn nguyên liệu.',
            ], 404);
        }

        $variantId = $request->has('variant_id') && $request->query('variant_id') !== ''
            ? (int) $request->query('variant_id')
            : null;

        $analysis = $this->costCalculator->calculateDishCost($id, $variantId, $warehouseId);

        return response()->json([
            'warehouse_id' => $warehouseId,
            'data' => $analysis->toArray(),
        ]);
    }

    /**
     * Báo cáo Giá vốn thực tế (COGS) và Lợi nhuận bán hàng theo món ăn từ các đơn hàng đã phục vụ.
     */
    public function salesProfitReport(Request $request): JsonResponse
    {
        $branchId = $this->selectedBranchId($request);
        abort_if($branchId === null, 422, 'Vui lòng chọn chi nhánh trước khi xem báo cáo.');
        $fromDate = $request->query('from_date', Carbon::today()->startOfMonth()->toDateString());
        $toDate = $request->query('to_date', Carbon::today()->toDateString());
        $categoryId = $request->query('category_id');

        $query = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->whereNotNull('order_items.stock_deducted_at')
            ->whereBetween('orders.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);

        $query->where('orders.branch_id', $branchId);

        if (! empty($categoryId)) {
            $query->where('menu_items.category_id', (int) $categoryId);
        }

        $items = $query->select([
            'order_items.menu_item_id',
            'order_items.item_name_snapshot as item_name',
            'order_items.variant_name_snapshot as variant_name',
            DB::raw('SUM(order_items.quantity) as total_quantity'),
            DB::raw('SUM(order_items.line_total) as total_revenue'),
            DB::raw('SUM(COALESCE(order_items.cogs_total, 0)) as total_cogs'),
            DB::raw('SUM(COALESCE(order_items.gross_profit, order_items.line_total)) as total_gross_profit'),
        ])
            ->groupBy('order_items.menu_item_id', 'order_items.item_name_snapshot', 'order_items.variant_name_snapshot')
            ->orderByDesc('total_gross_profit')
            ->get();

        $summaryRevenue = 0.0;
        $summaryCogs = 0.0;
        $summaryProfit = 0.0;
        $summaryQuantity = 0;

        $reportData = $items->map(function ($row) use (&$summaryRevenue, &$summaryCogs, &$summaryProfit, &$summaryQuantity) {
            $revenue = (float) $row->total_revenue;
            $cogs = (float) $row->total_cogs;
            $profit = (float) $row->total_gross_profit;
            $qty = (int) $row->total_quantity;

            $summaryRevenue += $revenue;
            $summaryCogs += $cogs;
            $summaryProfit += $profit;
            $summaryQuantity += $qty;

            $foodCostPct = $revenue > 0 ? round(($cogs / $revenue) * 100, 2) : 0.0;
            $marginPct = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0.0;

            return [
                'menu_item_id' => (int) $row->menu_item_id,
                'item_name' => $row->item_name,
                'variant_name' => $row->variant_name,
                'quantity_sold' => $qty,
                'revenue' => $revenue,
                'cogs' => $cogs,
                'gross_profit' => $profit,
                'food_cost_percentage' => $foodCostPct,
                'gross_margin_percentage' => $marginPct,
            ];
        });

        $totalFoodCostPct = $summaryRevenue > 0 ? round(($summaryCogs / $summaryRevenue) * 100, 2) : 0.0;
        $totalMarginPct = $summaryRevenue > 0 ? round(($summaryProfit / $summaryRevenue) * 100, 2) : 0.0;

        return response()->json([
            'filters' => [
                'branch_id' => $branchId ? (int) $branchId : null,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'category_id' => $categoryId ? (int) $categoryId : null,
            ],
            'summary' => [
                'total_dishes_sold' => $summaryQuantity,
                'total_revenue' => round($summaryRevenue, 2),
                'total_cogs' => round($summaryCogs, 2),
                'total_gross_profit' => round($summaryProfit, 2),
                'overall_food_cost_percentage' => $totalFoodCostPct,
                'overall_gross_margin_percentage' => $totalMarginPct,
            ],
            'items' => $reportData,
        ]);
    }

    /**
     * Xác định ID kho hàng mục tiêu từ request (ưu tiên warehouse_id, sau đó tới branch_id, fallback kho đầu tiên).
     */
    protected function resolveWarehouseId(Request $request): ?int
    {
        if ($request->filled('warehouse_id')) {
            return (int) $request->query('warehouse_id');
        }

        $branchId = $this->selectedBranchId($request);
        if ($branchId === null) {
            return null;
        }

        $warehouse = Warehouse::query()
            ->where('branch_id', $branchId)
            ->where('type', WarehouseType::BRANCH)
            ->where('is_active', true)
            ->first()
            ?? Warehouse::query()
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->first();

        return $warehouse ? (int) $warehouse->id : null;
    }

    private function selectedBranchId(Request $request): ?int
    {
        $branchId = $request->attributes->get('selected_branch_id');
        if (is_numeric($branchId)) {
            return (int) $branchId;
        }

        $branchId = $request->query('branch_id');
        if (is_numeric($branchId)) {
            return (int) $branchId;
        }

        $allowedBranchIds = $request->attributes->get('authorized_branch_ids');
        if (is_array($allowedBranchIds) && count($allowedBranchIds) === 1) {
            return (int) $allowedBranchIds[0];
        }

        return null;
    }
}
