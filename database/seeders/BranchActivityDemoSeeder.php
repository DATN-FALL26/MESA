<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\DTOs\Inventory\StockMovementDTO;
use App\Enums\AiRunStatus;
use App\Enums\AiRunType;
use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\AlertType;
use App\Enums\ApprovalStatus;
use App\Enums\ApprovalType;
use App\Enums\BatchStatus;
use App\Enums\InvoiceStatus;
use App\Enums\MovementType;
use App\Enums\OrderChannel;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PrintDocType;
use App\Enums\PrintJobStatus;
use App\Enums\PurchaseOrderSource;
use App\Enums\PurchaseOrderStatus;
use App\Enums\ScopeType;
use App\Enums\ServeMode;
use App\Enums\SessionStatus;
use App\Enums\StockBatchStatus;
use App\Enums\TicketItemStatus;
use App\Enums\TicketStatus;
use App\Enums\UserStatus;
use App\Helpers\ConstantHelper;
use App\Services\Inventory\StockBatchService;
use App\Services\Inventory\StockLedgerService;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Creates a week of local-only branch activity using the existing sample menu and inventory.
 *
 * Run with: php artisan db:seed --class=Database\Seeders\BranchActivityDemoSeeder
 */
class BranchActivityDemoSeeder extends Seeder
{
    private const DEMO_COST_ADJUSTMENT_FACTOR = 0.77;

    private const BRANCH_CODES = ['PHO01', 'PHO02'];

    private const MENU_SKUS = [
        'PHO_TAI',
        'PHO_CHIN',
        'PHO_DAC_BIET',
        'QUAY',
        'TRA_DA',
        'NUOC_NGOT',
    ];

    private const INGREDIENT_SKUS = [
        'NL_BANH_PHO',
        'NL_THIT_BO_TAI',
        'NL_THIT_BO_CHIN',
        'NL_XUONG_BO',
        'NL_HANH_LA',
        'NL_QUAY',
        'NL_NUOC_DUNG',
        'NL_TRA',
        'NL_NUOC_NGOT',
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Branch demo activity can only be seeded in local or testing environments.');
        }

        if (DB::table('orders')->where('order_no', 'like', 'DEMO-PHO%')->exists()) {
            DB::transaction(function (): void {
                $this->normalizeExistingDemoIngredientCosts();
                $this->recalculateExistingDemoOrderCosts();
            });
            $this->command?->info('Đã chuẩn hóa giá nguyên liệu và cập nhật giá vốn dữ liệu mẫu hiện có.');

            return;
        }

        $catalog = $this->loadCatalog();
        $branches = DB::table('branches')
            ->whereIn('code', self::BRANCH_CODES)
            ->where('status', 'active')
            ->orderBy('code')
            ->get()
            ->keyBy('code');

        if ($branches->count() !== count(self::BRANCH_CODES)) {
            throw new RuntimeException('Cần có đủ hai chi nhánh đang hoạt động PHO01 và PHO02.');
        }

        DB::transaction(function () use ($branches, $catalog): void {
            $contexts = $this->buildBranchContexts($branches);

            foreach ($contexts as $context) {
                $this->createOpeningBatches($context);
                $this->createPurchaseOrder($context, $catalog);
            }

            $aggregates = $this->createOrders($contexts, $catalog);
            $this->createSalesReports($aggregates);
            $this->createKitchenCheckins($contexts);
            $this->createAiData($contexts, $catalog, $aggregates);
            $this->createNotifications($contexts, $aggregates);
            $this->createAuditLogs($contexts, $aggregates);
        });

        $this->command?->info('Đã tạo dữ liệu mẫu giao dịch cho PHO01 và PHO02.');
    }

    private function normalizeExistingDemoIngredientCosts(): void
    {
        $warehouseIds = DB::table('warehouses')
            ->join('branches', 'branches.id', '=', 'warehouses.branch_id')
            ->whereIn('branches.code', self::BRANCH_CODES)
            ->where('warehouses.is_active', true)
            ->pluck('warehouses.id')
            ->all();
        $ingredientIds = DB::table('ingredients')
            ->whereIn('sku', self::INGREDIENT_SKUS)
            ->pluck('id')
            ->all();

        if (count($warehouseIds) !== count(self::BRANCH_CODES) || count($ingredientIds) !== count(self::INGREDIENT_SKUS)) {
            throw new RuntimeException('Không tìm thấy đủ kho hoặc nguyên liệu mẫu để chuẩn hóa giá vốn.');
        }

        $hasLegacyMassCosts = DB::table('stock_levels')
            ->join('ingredients', 'ingredients.id', '=', 'stock_levels.ingredient_id')
            ->whereIn('stock_levels.warehouse_id', $warehouseIds)
            ->whereIn('ingredients.sku', self::INGREDIENT_SKUS)
            ->whereIn('ingredients.base_uom', ['g', 'ml'])
            ->where('stock_levels.avg_cost', '>', 1000)
            ->exists();

        if (! $hasLegacyMassCosts) {
            return;
        }

        $purchaseOrderIds = DB::table('purchase_orders')
            ->where('po_no', 'like', 'DEMO-PO-PHO%')
            ->pluck('id')
            ->all();
        $orderItemIds = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.order_no', 'like', 'DEMO-PHO%')
            ->pluck('order_items.id')
            ->all();

        if ($purchaseOrderIds === [] || $orderItemIds === []) {
            throw new RuntimeException('Thiếu chứng từ mẫu liên kết; không thể chuẩn hóa an toàn giá vốn.');
        }

        DB::table('stock_levels')
            ->join('ingredients', 'ingredients.id', '=', 'stock_levels.ingredient_id')
            ->whereIn('stock_levels.warehouse_id', $warehouseIds)
            ->whereIn('ingredients.sku', self::INGREDIENT_SKUS)
            ->update([
                'stock_levels.avg_cost' => $this->demoCostAdjustmentExpression('stock_levels.avg_cost'),
                'stock_levels.updated_at' => now(),
            ]);

        DB::table('stock_batches')
            ->join('ingredients', 'ingredients.id', '=', 'stock_batches.ingredient_id')
            ->whereIn('stock_batches.warehouse_id', $warehouseIds)
            ->whereIn('stock_batches.ingredient_id', $ingredientIds)
            ->where(function ($query): void {
                $query->where('stock_batches.batch_code', 'like', 'OPEN-PHO%')
                    ->orWhere('stock_batches.batch_code', 'like', 'DEMO-LOT-PHO%');
            })
            ->update([
                'stock_batches.unit_cost' => $this->demoCostAdjustmentExpression('stock_batches.unit_cost'),
                'stock_batches.updated_at' => now(),
            ]);

        DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->join('ingredients', 'ingredients.id', '=', 'purchase_order_items.ingredient_id')
            ->whereIn('purchase_orders.id', $purchaseOrderIds)
            ->whereIn('purchase_order_items.ingredient_id', $ingredientIds)
            ->update([
                'purchase_order_items.unit_price' => $this->demoCostAdjustmentExpression('purchase_order_items.unit_price'),
                'purchase_order_items.updated_at' => now(),
            ]);

        DB::table('stock_movements')
            ->join('ingredients', 'ingredients.id', '=', 'stock_movements.ingredient_id')
            ->whereIn('stock_movements.warehouse_id', $warehouseIds)
            ->whereIn('stock_movements.ingredient_id', $ingredientIds)
            ->where(function ($query) use ($purchaseOrderIds, $orderItemIds): void {
                $query->where(function ($query) use ($purchaseOrderIds): void {
                    $query->where('stock_movements.ref_type', 'purchase_order')
                        ->whereIn('stock_movements.ref_id', $purchaseOrderIds);
                })->orWhere(function ($query) use ($orderItemIds): void {
                    $query->where('stock_movements.ref_type', 'order_item')
                        ->whereIn('stock_movements.ref_id', $orderItemIds);
                });
            })
            ->update([
                'stock_movements.unit_cost' => $this->demoCostAdjustmentExpression('stock_movements.unit_cost'),
            ]);
    }

    private function recalculateExistingDemoOrderCosts(): void
    {
        $branchIds = DB::table('branches')
            ->whereIn('code', self::BRANCH_CODES)
            ->pluck('id')
            ->all();
        $warehouseIdsByBranch = DB::table('warehouses')
            ->whereIn('branch_id', $branchIds)
            ->where('is_active', true)
            ->pluck('id', 'branch_id')
            ->all();

        if (count($branchIds) !== count(self::BRANCH_CODES) || count($warehouseIdsByBranch) !== count(self::BRANCH_CODES)) {
            throw new RuntimeException('Không tìm thấy đủ chi nhánh hoặc kho mẫu để tính lại giá vốn.');
        }

        $stockLevels = DB::table('stock_levels')
            ->whereIn('warehouse_id', array_values($warehouseIdsByBranch))
            ->get(['warehouse_id', 'ingredient_id', 'avg_cost'])
            ->keyBy(fn ($level): string => $level->warehouse_id.':'.$level->ingredient_id);
        $catalog = $this->loadCatalog();
        $dailyDeltas = [];
        $itemDeltas = [];

        $orderItems = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.order_no', 'like', 'DEMO-PHO%')
            ->get([
                'order_items.id',
                'order_items.menu_item_id',
                'order_items.variant_id',
                'order_items.quantity',
                'order_items.line_total',
                'order_items.cogs_total',
                'order_items.gross_profit',
                'orders.branch_id',
                'orders.created_at as order_created_at',
            ]);

        if ($orderItems->isEmpty()) {
            throw new RuntimeException('Không tìm thấy món mẫu để cập nhật giá vốn.');
        }

        foreach ($orderItems as $orderItem) {
            $branchId = (int) $orderItem->branch_id;
            $warehouseId = $warehouseIdsByBranch[$branchId] ?? null;
            $recipeKey = $orderItem->menu_item_id.':'.($orderItem->variant_id ?? 0);
            $recipe = $catalog['recipes'][$recipeKey] ?? null;

            if ($warehouseId === null || $recipe === null) {
                throw new RuntimeException("Thiếu kho hoặc công thức cho món mẫu {$orderItem->id}.");
            }

            $recipeItems = $catalog['recipeItems'][$recipe->id] ?? collect();
            if ($recipeItems->isEmpty()) {
                throw new RuntimeException("Công thức {$recipe->id} chưa có nguyên liệu.");
            }

            $cost = 0.0;
            foreach ($recipeItems as $recipeItem) {
                $stockLevel = $stockLevels->get($warehouseId.':'.$recipeItem->ingredient_id);
                if ($stockLevel === null) {
                    throw new RuntimeException("Thiếu tồn kho cho nguyên liệu ID {$recipeItem->ingredient_id}.");
                }

                $cost += (float) $recipeItem->quantity * (int) $orderItem->quantity * (float) $stockLevel->avg_cost;
            }

            $newCogs = round($cost, 2);
            $newGrossProfit = round((float) $orderItem->line_total - $newCogs, 2);
            DB::table('order_items')->where('id', $orderItem->id)->update([
                'cost_price_snapshot' => round($newCogs / max(1, (int) $orderItem->quantity), 2),
                'cogs_total' => $newCogs,
                'gross_profit' => $newGrossProfit,
            ]);

            $salesDate = Carbon::parse($orderItem->order_created_at)->toDateString();
            $dailyKey = $branchId.':'.$salesDate;
            $itemKey = $dailyKey.':'.$orderItem->menu_item_id;
            $cogsDelta = $newCogs - (float) $orderItem->cogs_total;
            $profitDelta = $newGrossProfit - (float) $orderItem->gross_profit;

            $dailyDeltas[$dailyKey] ??= [
                'branch_id' => $branchId,
                'sales_date' => $salesDate,
                'total_cogs' => 0.0,
                'gross_profit' => 0.0,
            ];
            $dailyDeltas[$dailyKey]['total_cogs'] += $cogsDelta;
            $dailyDeltas[$dailyKey]['gross_profit'] += $profitDelta;

            $itemDeltas[$itemKey] ??= [
                'branch_id' => $branchId,
                'sales_date' => $salesDate,
                'menu_item_id' => (int) $orderItem->menu_item_id,
                'total_cogs' => 0.0,
                'gross_profit' => 0.0,
            ];
            $itemDeltas[$itemKey]['total_cogs'] += $cogsDelta;
            $itemDeltas[$itemKey]['gross_profit'] += $profitDelta;
        }

        foreach ($dailyDeltas as $delta) {
            $summary = DB::table('daily_sales_summary')
                ->where('branch_id', $delta['branch_id'])
                ->where('sales_date', $delta['sales_date'])
                ->first(['total_cogs', 'gross_profit']);
            if ($summary === null) {
                throw new RuntimeException("Thiếu báo cáo doanh thu ngày {$delta['sales_date']}.");
            }

            DB::table('daily_sales_summary')
                ->where('branch_id', $delta['branch_id'])
                ->where('sales_date', $delta['sales_date'])
                ->update([
                    'total_cogs' => round((float) $summary->total_cogs + $delta['total_cogs'], 2),
                    'gross_profit' => round((float) $summary->gross_profit + $delta['gross_profit'], 2),
                    'updated_at' => now(),
                ]);
        }

        foreach ($itemDeltas as $delta) {
            $dailyItem = DB::table('daily_item_sales')
                ->where('branch_id', $delta['branch_id'])
                ->where('sales_date', $delta['sales_date'])
                ->where('menu_item_id', $delta['menu_item_id'])
                ->first(['total_cogs', 'gross_profit']);
            if ($dailyItem === null) {
                throw new RuntimeException("Thiếu báo cáo món bán ngày {$delta['sales_date']}.");
            }

            DB::table('daily_item_sales')
                ->where('branch_id', $delta['branch_id'])
                ->where('sales_date', $delta['sales_date'])
                ->where('menu_item_id', $delta['menu_item_id'])
                ->update([
                    'total_cogs' => round((float) $dailyItem->total_cogs + $delta['total_cogs'], 2),
                    'gross_profit' => round((float) $dailyItem->gross_profit + $delta['gross_profit'], 2),
                    'updated_at' => now(),
                ]);
        }
    }

    private function demoCostAdjustmentExpression(string $qualifiedCostColumn): Expression
    {
        $factor = self::DEMO_COST_ADJUSTMENT_FACTOR;

        return DB::raw(sprintf(
            "ROUND(%s * CASE WHEN ingredients.base_uom IN ('g', 'ml') THEN %.8F ELSE %.8F END, 2)",
            $qualifiedCostColumn,
            $factor / 1000,
            $factor
        ));
    }

    /**
     * @return array{
     *     menus: Collection<string, object>,
     *     ingredients: Collection<string, object>,
     *     variants: array<string, object>,
     *     recipes: array<string, object>,
     *     recipeItems: array<int, Collection<int, object>>,
     *     supplierId: int
     * }
     */
    private function loadCatalog(): array
    {
        $menus = DB::table('menu_items')
            ->whereIn('sku', self::MENU_SKUS)
            ->where('is_active', true)
            ->get()
            ->keyBy('sku');
        $ingredients = DB::table('ingredients')
            ->whereIn('sku', self::INGREDIENT_SKUS)
            ->where('is_active', true)
            ->get()
            ->keyBy('sku');

        if ($menus->count() !== count(self::MENU_SKUS) || $ingredients->count() !== count(self::INGREDIENT_SKUS)) {
            throw new RuntimeException('Thiếu menu hoặc nguyên liệu mẫu. Không tự chạy lại SampleDataSeeder để tránh ghi đè tồn kho.');
        }

        $variants = [];
        foreach (DB::table('item_variants')->whereIn('menu_item_id', $menus->pluck('id'))->where('is_active', true)->get() as $variant) {
            $variants[$variant->menu_item_id.':'.$variant->name] = $variant;
        }

        $recipes = [];
        $recipeIds = [];
        foreach (DB::table('recipes')
            ->whereIn('menu_item_id', $menus->pluck('id'))
            ->where('is_active', true)
            ->orderByDesc('version')
            ->get() as $recipe) {
            $key = $recipe->menu_item_id.':'.($recipe->variant_id ?? 0);
            if (! isset($recipes[$key])) {
                $recipes[$key] = $recipe;
                $recipeIds[] = $recipe->id;
            }
        }

        $recipeItems = [];
        foreach (DB::table('recipe_items')->whereIn('recipe_id', $recipeIds)->get()->groupBy('recipe_id') as $recipeId => $items) {
            $recipeItems[(int) $recipeId] = $items;
        }

        $supplierId = (int) DB::table('suppliers')->where('is_active', true)->orderBy('id')->value('id');

        if ($supplierId === 0 || count($recipes) < 12) {
            throw new RuntimeException('Thiếu nhà cung cấp hoặc công thức mẫu; bổ sung dữ liệu nền trước khi chạy seeder này.');
        }

        return compact('menus', 'ingredients', 'variants', 'recipes', 'recipeItems', 'supplierId');
    }

    /**
     * @param  Collection<string, object>  $branches
     * @return array<string, array<string, mixed>>
     */
    private function buildBranchContexts(Collection $branches): array
    {
        $contexts = [];
        $staffDefinitions = [
            'manager' => [
                ConstantHelper::ROLE_BRANCH_MANAGER,
                ConstantHelper::DEPT_OFFICE,
                'manager',
                'Quản lý chi nhánh',
            ],
            'cashier' => [
                ConstantHelper::ROLE_CASHIER,
                ConstantHelper::DEPT_CASHIER,
                null,
                'Thu ngân',
            ],
            'waiter' => [
                ConstantHelper::ROLE_WAITER,
                ConstantHelper::DEPT_SERVICE,
                null,
                'Nhân viên phục vụ',
            ],
            'kitchen' => [
                ConstantHelper::ROLE_KITCHEN,
                ConstantHelper::DEPT_KITCHEN,
                null,
                'Nhân viên bếp',
            ],
            'warehouse' => [
                ConstantHelper::ROLE_WAREHOUSE,
                ConstantHelper::DEPT_WAREHOUSE,
                null,
                'Nhân viên kho',
            ],
        ];

        foreach ($branches as $branchCode => $branch) {
            $warehouse = DB::table('warehouses')
                ->where('branch_id', $branch->id)
                ->where('is_active', true)
                ->orderBy('id')
                ->first();
            $tables = DB::table('dining_tables')
                ->where('branch_id', $branch->id)
                ->where('is_active', true)
                ->orderBy('id')
                ->get();
            $stations = DB::table('stations')
                ->where('branch_id', $branch->id)
                ->where('is_active', true)
                ->get()
                ->keyBy('station_code');
            $printers = DB::table('printers')
                ->where('branch_id', $branch->id)
                ->where('is_active', true)
                ->get()
                ->keyBy('type');

            if ($warehouse === null || $tables->isEmpty() || ! $stations->has('PHO') || ! $printers->has('kitchen') || ! $printers->has('receipt')) {
                throw new RuntimeException("Thiếu kho, bàn, trạm bếp hoặc máy in mẫu ở chi nhánh {$branchCode}.");
            }

            $staff = [];
            foreach ($staffDefinitions as $key => [$roleCode, $departmentCode, $baseUsername, $title]) {
                $username = $baseUsername ?? $key.(substr($branchCode, -2));
                $staff[$key] = $this->ensureBranchStaff(
                    (int) $branch->id,
                    $branchCode,
                    $roleCode,
                    $departmentCode,
                    $username,
                    "{$title} mẫu {$branchCode}",
                    "{$username}@mesa.test"
                );
            }

            $contexts[$branchCode] = [
                'code' => $branchCode,
                'branch_id' => (int) $branch->id,
                'warehouse_id' => (int) $warehouse->id,
                'tables' => $tables,
                'stations' => $stations,
                'printers' => $printers,
                'staff' => $staff,
            ];
        }

        return $contexts;
    }

    private function ensureBranchStaff(
        int $branchId,
        string $branchCode,
        string $roleCode,
        string $departmentCode,
        string $username,
        string $fullName,
        string $email
    ): int {
        $now = now();
        $activeUserId = DB::table('user_roles as assignments')
            ->join('roles', 'roles.id', '=', 'assignments.role_id')
            ->where('roles.code', $roleCode)
            ->where('assignments.scope_type', ScopeType::BRANCH->value)
            ->where('assignments.scope_id', $branchId)
            ->where('assignments.valid_from', '<=', $now)
            ->where(fn ($query) => $query
                ->whereNull('assignments.valid_to')
                ->orWhere('assignments.valid_to', '>=', $now))
            ->value('assignments.user_id');

        if ($activeUserId !== null) {
            return (int) $activeUserId;
        }

        $userId = DB::table('users')->where('username', $username)->value('id');
        if ($userId === null) {
            $departmentId = DB::table('departments')->where('code', $departmentCode)->value('id');
            if ($departmentId === null) {
                throw new RuntimeException("Không tìm thấy phòng ban {$departmentCode}.");
            }

            $userId = DB::table('users')->insertGetId([
                'username' => $username,
                'employee_code' => 'DEMO-'.strtoupper($roleCode).'-'.$branchCode,
                'full_name' => $fullName,
                'email' => $email,
                'password' => Hash::make('Demo12345!'),
                'pin_hash' => Hash::make('123456'),
                'department_id' => $departmentId,
                'status' => UserStatus::ACTIVE->value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } elseif (DB::table('users')->where('id', $userId)->where('status', UserStatus::ACTIVE->value)->whereNull('deleted_at')->doesntExist()) {
            throw new RuntimeException("Tài khoản {$username} đã tồn tại nhưng không hoạt động.");
        }

        $roleId = DB::table('roles')->where('code', $roleCode)->value('id');
        if ($roleId === null) {
            throw new RuntimeException("Không tìm thấy vai trò {$roleCode}.");
        }

        $assignment = DB::table('user_roles')
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->where('scope_type', ScopeType::BRANCH->value)
            ->where('scope_id', $branchId)
            ->first();

        if ($assignment === null) {
            DB::table('user_roles')->insert([
                'user_id' => $userId,
                'role_id' => $roleId,
                'scope_type' => ScopeType::BRANCH->value,
                'scope_id' => $branchId,
                'valid_from' => $now,
                'valid_to' => null,
                'granted_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('user_roles')->where('id', $assignment->id)->update([
                'valid_from' => $now,
                'valid_to' => null,
                'updated_at' => $now,
            ]);
        }

        return (int) $userId;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function createOpeningBatches(array $context): void
    {
        $openingDate = Carbon::today()->subDays(30);

        $levels = DB::table('stock_levels')
            ->join('ingredients', 'ingredients.id', '=', 'stock_levels.ingredient_id')
            ->where('stock_levels.warehouse_id', $context['warehouse_id'])
            ->select('stock_levels.*', 'ingredients.sku')
            ->get();

        foreach ($levels as $level) {
            if ((float) $level->quantity <= 0) {
                continue;
            }

            $batchCode = "OPEN-{$context['code']}-{$level->sku}";
            if (DB::table('stock_batches')->where('batch_code', $batchCode)->exists()) {
                continue;
            }

            DB::table('stock_batches')->insert([
                'batch_code' => $batchCode,
                'warehouse_id' => $context['warehouse_id'],
                'ingredient_id' => $level->ingredient_id,
                'purchase_order_id' => null,
                'supplier_id' => null,
                'initial_quantity' => $level->quantity,
                'current_quantity' => $level->quantity,
                'unit_cost' => $level->avg_cost,
                'import_date' => $openingDate->toDateString(),
                'expiry_date' => $openingDate->copy()->addDays(90)->toDateString(),
                'status' => StockBatchStatus::ACTIVE->value,
                'note' => 'Tồn đầu kỳ từ bộ dữ liệu mẫu cũ.',
                'created_by' => $context['staff']['warehouse'],
                'created_at' => $openingDate->copy()->setTime(8, 0)->toDateTimeString(),
                'updated_at' => $openingDate->copy()->setTime(8, 0)->toDateTimeString(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $catalog
     */
    private function createPurchaseOrder(array $context, array $catalog): void
    {
        $receivedAt = Carbon::today()->subDays(8)->setTime(8, 0);
        $approvedAt = $receivedAt->copy()->subMinutes(5);
        $purchaseOrderNo = "DEMO-PO-{$context['code']}-".$receivedAt->format('Ymd');
        $managerId = $context['staff']['manager'];
        $warehouseUserId = $context['staff']['warehouse'];

        $purchaseOrderId = DB::table('purchase_orders')->insertGetId([
            'branch_id' => $context['branch_id'],
            'warehouse_id' => $context['warehouse_id'],
            'supplier_id' => $catalog['supplierId'],
            'po_no' => $purchaseOrderNo,
            'status' => PurchaseOrderStatus::RECEIVED->value,
            'source' => PurchaseOrderSource::MANUAL->value,
            'note' => 'Phiếu nhập mẫu theo giá vốn nền hiện có.',
            'created_by' => $warehouseUserId,
            'approved_by' => $managerId,
            'approved_at' => $approvedAt->toDateTimeString(),
            'received_by' => $warehouseUserId,
            'received_at' => $receivedAt->toDateTimeString(),
            'created_at' => $receivedAt->copy()->subMinutes(20)->toDateTimeString(),
            'updated_at' => $receivedAt->toDateTimeString(),
        ]);

        DB::table('approval_requests')->insert([
            'branch_id' => $context['branch_id'],
            'request_type' => ApprovalType::PURCHASE_ORDER->value,
            'ref_id' => $purchaseOrderId,
            'requested_by' => $warehouseUserId,
            'status' => ApprovalStatus::APPROVED->value,
            'decided_by' => $managerId,
            'decided_at' => $approvedAt->toDateTimeString(),
            'note' => 'Đã duyệt phiếu nhập dữ liệu mẫu.',
            'created_at' => $receivedAt->copy()->subMinutes(10)->toDateTimeString(),
            'updated_at' => $approvedAt->toDateTimeString(),
        ]);

        $quantities = [
            'NL_BANH_PHO' => 7000.000,
            'NL_THIT_BO_TAI' => 5000.000,
            'NL_THIT_BO_CHIN' => 5000.000,
            'NL_XUONG_BO' => 10000.000,
            'NL_HANH_LA' => 2500.000,
            'NL_QUAY' => 250.000,
            'NL_NUOC_DUNG' => 20000.000,
            'NL_TRA' => 500.000,
            'NL_NUOC_NGOT' => 100.000,
        ];

        foreach (self::INGREDIENT_SKUS as $index => $sku) {
            $ingredient = $catalog['ingredients']->get($sku);
            $quantity = $quantities[$sku];
            $stockLevel = DB::table('stock_levels')
                ->where('warehouse_id', $context['warehouse_id'])
                ->where('ingredient_id', $ingredient->id)
                ->first();

            if ($stockLevel === null || (float) $stockLevel->avg_cost <= 0) {
                throw new RuntimeException("Thiếu giá vốn mẫu hợp lệ cho {$sku} tại {$context['code']}.");
            }

            $branchOffset = $context['code'] === 'PHO01' ? 0 : 1;
            $variation = ((($index + $branchOffset) % 5) - 2) / 100;
            $unitPrice = round((float) $stockLevel->avg_cost * (1 + $variation), 2);
            $batchCode = "DEMO-LOT-{$context['code']}-".substr($sku, 3).'-'.$receivedAt->format('ymd');
            $expiryDays = (bool) $ingredient->is_daily_procured ? 14 : 90;
            $expiryDate = $receivedAt->copy()->addDays($expiryDays)->toDateString();

            $purchaseOrderItemId = DB::table('purchase_order_items')->insertGetId([
                'purchase_order_id' => $purchaseOrderId,
                'ingredient_id' => $ingredient->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'received_qty' => $quantity,
                'batch_no' => $batchCode,
                'expiry_date' => $expiryDate,
                'created_at' => $receivedAt->toDateTimeString(),
                'updated_at' => $receivedAt->toDateTimeString(),
            ]);

            $batchId = DB::table('stock_batches')->insertGetId([
                'batch_code' => $batchCode,
                'warehouse_id' => $context['warehouse_id'],
                'ingredient_id' => $ingredient->id,
                'purchase_order_id' => $purchaseOrderId,
                'supplier_id' => $catalog['supplierId'],
                'initial_quantity' => $quantity,
                'current_quantity' => $quantity,
                'unit_cost' => $unitPrice,
                'import_date' => $receivedAt->toDateString(),
                'expiry_date' => $expiryDate,
                'status' => StockBatchStatus::ACTIVE->value,
                'note' => 'Lô hàng mẫu; đơn giá dao động nhẹ quanh giá vốn nền.',
                'created_by' => $warehouseUserId,
                'created_at' => $receivedAt->toDateTimeString(),
                'updated_at' => $receivedAt->toDateTimeString(),
            ]);

            $movement = $this->at($receivedAt, fn () => app(StockLedgerService::class)->recordMovement(
                new StockMovementDTO(
                    warehouseId: $context['warehouse_id'],
                    ingredientId: (int) $ingredient->id,
                    movementType: MovementType::PURCHASE_IN,
                    quantity: (float) $quantity,
                    unitCost: $unitPrice,
                    refType: 'purchase_order',
                    refId: $purchaseOrderId,
                    idempotencyKey: "demo_po_{$purchaseOrderId}_ing_{$ingredient->id}",
                    note: "Nhập hàng mẫu theo phiếu {$purchaseOrderNo}",
                    createdBy: $warehouseUserId
                )
            ));

            DB::table('stock_movements')->where('id', $movement->id)->update(['batch_id' => $batchId]);
            DB::table('purchase_order_items')->where('id', $purchaseOrderItemId)->update([
                'batch_no' => $batchCode,
                'updated_at' => $receivedAt->toDateTimeString(),
            ]);
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $contexts
     * @param  array<string, mixed>  $catalog
     * @return array<string, array<string, array<string, mixed>>>
     */
    private function createOrders(array $contexts, array $catalog): array
    {
        $daily = [];
        $items = [];
        $templates = [
            [
                ['sku' => 'PHO_TAI', 'variant' => 'Nhỏ', 'quantity' => 1],
                ['sku' => 'TRA_DA', 'variant' => null, 'quantity' => 2],
            ],
            [
                ['sku' => 'PHO_CHIN', 'variant' => 'Lớn', 'quantity' => 1],
                ['sku' => 'QUAY', 'variant' => null, 'quantity' => 1],
            ],
            [
                ['sku' => 'PHO_DAC_BIET', 'variant' => 'Lớn', 'quantity' => 1],
                ['sku' => 'NUOC_NGOT', 'variant' => null, 'quantity' => 1],
            ],
            [
                ['sku' => 'PHO_TAI', 'variant' => 'Lớn', 'quantity' => 1],
                ['sku' => 'PHO_CHIN', 'variant' => 'Nhỏ', 'quantity' => 1],
                ['sku' => 'TRA_DA', 'variant' => null, 'quantity' => 1],
            ],
        ];

        for ($daysAgo = 7; $daysAgo >= 1; $daysAgo--) {
            $salesDate = Carbon::today()->subDays($daysAgo);

            foreach ($contexts as $context) {
                foreach ($templates as $index => $template) {
                    $orderData = $this->createOrder(
                        $context,
                        $catalog,
                        $template,
                        $salesDate,
                        $daysAgo,
                        $index + 1
                    );

                    $dateKey = $salesDate->toDateString();
                    $branchCode = $context['code'];
                    $daily[$branchCode][$dateKey] ??= [
                        'order_count' => 0,
                        'guest_count' => 0,
                        'gross_revenue' => 0,
                        'discount_amount' => 0,
                        'net_revenue' => 0,
                        'total_cogs' => 0,
                        'gross_profit' => 0,
                        'payment_breakdown' => ['cash' => 0, 'qr' => 0],
                    ];
                    $daily[$branchCode][$dateKey]['order_count']++;
                    $daily[$branchCode][$dateKey]['guest_count'] += $orderData['guest_count'];
                    $daily[$branchCode][$dateKey]['gross_revenue'] += $orderData['subtotal'];
                    $daily[$branchCode][$dateKey]['net_revenue'] += $orderData['subtotal'];
                    $daily[$branchCode][$dateKey]['total_cogs'] += $orderData['total_cogs'];
                    $daily[$branchCode][$dateKey]['gross_profit'] += $orderData['gross_profit'];
                    $daily[$branchCode][$dateKey]['payment_breakdown'][$orderData['payment_method']] += $orderData['subtotal'];

                    foreach ($orderData['items'] as $itemData) {
                        $itemKey = $branchCode.':'.$dateKey.':'.$itemData['menu_item_id'];
                        $items[$itemKey] ??= [
                            'branch_id' => $context['branch_id'],
                            'sales_date' => $dateKey,
                            'menu_item_id' => $itemData['menu_item_id'],
                            'quantity' => 0,
                            'gross_revenue' => 0,
                            'total_cogs' => 0,
                            'gross_profit' => 0,
                        ];
                        $items[$itemKey]['quantity'] += $itemData['quantity'];
                        $items[$itemKey]['gross_revenue'] += $itemData['line_total'];
                        $items[$itemKey]['total_cogs'] += $itemData['cogs_total'];
                        $items[$itemKey]['gross_profit'] += $itemData['gross_profit'];
                    }
                }
            }
        }

        return ['daily' => $daily, 'items' => $items];
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $catalog
     * @param  array<int, array{sku: string, variant: ?string, quantity: int}>  $template
     * @return array{guest_count: int, subtotal: int, total_cogs: float, gross_profit: float, payment_method: string, items: array<int, array<string, int|float>>}
     */
    private function createOrder(
        array $context,
        array $catalog,
        array $template,
        Carbon $salesDate,
        int $daysAgo,
        int $orderIndex
    ): array {
        $times = ['11:20', '12:05', '18:15', '19:10'];
        $createdAt = Carbon::parse($salesDate->toDateString().' '.$times[$orderIndex - 1]);
        $completedAt = $createdAt->copy()->addMinutes(25);
        $dineIn = $orderIndex !== 2;
        $guestCount = $dineIn ? (($orderIndex % 2) + 1) : 0;
        $creatorId = $dineIn ? $context['staff']['waiter'] : $context['staff']['cashier'];
        $sessionId = null;
        $table = null;

        if ($dineIn) {
            $tableIndex = (($daysAgo * 4) + $orderIndex) % $context['tables']->count();
            $table = $context['tables'][$tableIndex];
            $sessionId = DB::table('dining_sessions')->insertGetId([
                'branch_id' => $context['branch_id'],
                'opened_by' => $context['staff']['waiter'],
                'opened_at' => $createdAt->toDateTimeString(),
                'closed_at' => $completedAt->copy()->addMinutes(10)->toDateTimeString(),
                'guest_count' => max(1, $guestCount),
                'status' => SessionStatus::CLOSED->value,
                'note' => 'Lượt mẫu đã hoàn tất.',
                'created_at' => $createdAt->toDateTimeString(),
                'updated_at' => $completedAt->copy()->addMinutes(10)->toDateTimeString(),
            ]);

            DB::table('session_tables')->insert([
                'session_id' => $sessionId,
                'table_id' => $table->id,
                'joined_at' => $createdAt->toDateTimeString(),
                'left_at' => $completedAt->copy()->addMinutes(10)->toDateTimeString(),
                'created_at' => $createdAt->toDateTimeString(),
                'updated_at' => $completedAt->copy()->addMinutes(10)->toDateTimeString(),
            ]);
        }

        $orderNo = sprintf('DEMO-%s-%s-%02d', $context['code'], $salesDate->format('Ymd'), $orderIndex);
        $orderId = DB::table('orders')->insertGetId([
            'branch_id' => $context['branch_id'],
            'session_id' => $sessionId,
            'order_no' => $orderNo,
            'channel' => $dineIn ? OrderChannel::DINE_IN->value : OrderChannel::TAKEAWAY->value,
            'status' => OrderStatus::COMPLETED->value,
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
            'customer_name' => $orderIndex === 4 ? 'Khách mẫu' : null,
            'customer_phone' => null,
            'note' => $table === null ? 'Đơn mang đi mẫu.' : null,
            'created_by' => $creatorId,
            'payment_requested_by' => $context['staff']['cashier'],
            'payment_requested_at' => $createdAt->copy()->addMinutes(15)->toDateTimeString(),
            'cancelled_by' => null,
            'cancel_reason' => null,
            'cancelled_at' => null,
            'completed_at' => $completedAt->toDateTimeString(),
            'created_at' => $createdAt->toDateTimeString(),
            'updated_at' => $completedAt->toDateTimeString(),
        ]);

        $batchSentAt = $createdAt->copy()->addMinute();
        $batchReadyAt = $createdAt->copy()->addMinutes(9);
        $batchServedAt = $createdAt->copy()->addMinutes(12);
        $batchId = DB::table('order_batches')->insertGetId([
            'order_id' => $orderId,
            'batch_no' => 1,
            'serve_mode' => ServeMode::TOGETHER->value,
            'status' => BatchStatus::SERVED->value,
            'sent_at' => $batchSentAt->toDateTimeString(),
            'target_ready_at' => $batchReadyAt->toDateTimeString(),
            'all_ready_at' => $batchReadyAt->toDateTimeString(),
            'served_at' => $batchServedAt->toDateTimeString(),
            'released_by' => $creatorId,
            'created_at' => $createdAt->toDateTimeString(),
            'updated_at' => $batchServedAt->toDateTimeString(),
        ]);

        $items = [];
        $subtotal = 0;

        foreach ($template as $line) {
            $menuItem = $catalog['menus']->get($line['sku']);
            $variant = $line['variant'] === null
                ? null
                : ($catalog['variants'][$menuItem->id.':'.$line['variant']] ?? null);

            if ($line['variant'] !== null && $variant === null) {
                throw new RuntimeException("Thiếu biến thể {$line['variant']} cho {$line['sku']}.");
            }

            $branchPrice = DB::table('branch_menu_items')
                ->where('branch_id', $context['branch_id'])
                ->where('menu_item_id', $menuItem->id)
                ->value('price');
            $basePrice = $branchPrice === null ? (int) $menuItem->base_price : (int) $branchPrice;
            $unitPrice = $basePrice + (int) ($variant->price_delta ?? 0);
            $lineTotal = $unitPrice * $line['quantity'];
            $orderItemId = DB::table('order_items')->insertGetId([
                'order_id' => $orderId,
                'batch_id' => $batchId,
                'menu_item_id' => $menuItem->id,
                'variant_id' => $variant->id ?? null,
                'item_name_snapshot' => $menuItem->name,
                'variant_name_snapshot' => $variant->name ?? null,
                'unit_price_snapshot' => $unitPrice,
                'quantity' => $line['quantity'],
                'line_total' => $lineTotal,
                'cost_price_snapshot' => null,
                'cogs_total' => null,
                'gross_profit' => null,
                'note' => null,
                'status' => OrderItemStatus::SERVED->value,
                'cancelled_reason' => null,
                'cancelled_by' => null,
                'cancelled_at' => null,
                'stock_deducted_at' => null,
                'created_at' => $createdAt->toDateTimeString(),
                'updated_at' => $createdAt->toDateTimeString(),
            ]);

            $recipeKey = $menuItem->id.':'.($variant->id ?? 0);
            $recipe = $catalog['recipes'][$recipeKey] ?? null;
            if ($recipe === null) {
                throw new RuntimeException("Thiếu công thức bán hàng cho {$line['sku']}.");
            }

            $cost = $this->deductRecipeStock($context, $recipe, $catalog, $orderItemId, $line['quantity'], $createdAt);
            $grossProfit = $lineTotal - $cost;
            DB::table('order_items')->where('id', $orderItemId)->update([
                'cost_price_snapshot' => round($cost / $line['quantity'], 2),
                'cogs_total' => round($cost, 2),
                'gross_profit' => round($grossProfit, 2),
                'stock_deducted_at' => $createdAt->toDateTimeString(),
                'updated_at' => $createdAt->toDateTimeString(),
            ]);

            $items[] = [
                'id' => $orderItemId,
                'menu_item_id' => (int) $menuItem->id,
                'quantity' => (int) $line['quantity'],
                'line_total' => $lineTotal,
                'cogs_total' => round($cost, 2),
                'gross_profit' => round($grossProfit, 2),
                'station_code' => (string) $menuItem->station_code,
            ];
            $subtotal += $lineTotal;
        }

        DB::table('orders')->where('id', $orderId)->update([
            'subtotal' => $subtotal,
            'total_amount' => $subtotal,
            'updated_at' => $completedAt->toDateTimeString(),
        ]);

        $this->createKitchenTickets($context, $orderId, $batchId, $orderNo, $items, $createdAt, $batchReadyAt);
        $paymentMethod = $orderIndex % 2 === 0 ? PaymentMethod::QR : PaymentMethod::CASH;
        $tenderedAmount = $paymentMethod === PaymentMethod::CASH ? (int) (ceil($subtotal / 10000) * 10000) : null;
        DB::table('payments')->insert([
            'branch_id' => $context['branch_id'],
            'order_id' => $orderId,
            'method' => $paymentMethod->value,
            'amount' => $subtotal,
            'tendered_amount' => $tenderedAmount,
            'change_amount' => $tenderedAmount === null ? null : $tenderedAmount - $subtotal,
            'status' => PaymentStatus::SUCCESS->value,
            'gateway_txn_id' => $paymentMethod === PaymentMethod::QR ? "DEMOQR-{$context['code']}-".$salesDate->format('Ymd').'-'.$orderIndex : null,
            'gateway_payload' => $paymentMethod === PaymentMethod::QR ? json_encode(['demo' => true, 'order_no' => $orderNo]) : null,
            'confirmed_by' => $context['staff']['cashier'],
            'paid_at' => $completedAt->copy()->subMinutes(2)->toDateTimeString(),
            'created_at' => $completedAt->copy()->subMinutes(2)->toDateTimeString(),
            'updated_at' => $completedAt->copy()->subMinutes(2)->toDateTimeString(),
        ]);

        if ($orderIndex % 2 === 0) {
            $invoiceNo = sprintf('DEMO-INV-%s-%s-%02d', $context['code'], $salesDate->format('Ymd'), $orderIndex);
            $invoiceId = DB::table('invoices')->insertGetId([
                'branch_id' => $context['branch_id'],
                'order_id' => $orderId,
                'invoice_no' => $invoiceNo,
                'issued_at' => $completedAt->toDateTimeString(),
                'total_amount' => $subtotal,
                'buyer_info' => json_encode(['name' => 'Khách hàng mẫu']),
                'status' => InvoiceStatus::ISSUED->value,
                'print_count' => 1,
                'created_at' => $completedAt->toDateTimeString(),
                'updated_at' => $completedAt->toDateTimeString(),
            ]);

            $this->createPrintJob(
                (int) $context['printers']->get('receipt')->id,
                PrintDocType::INVOICE,
                $invoiceId,
                ['invoice_no' => $invoiceNo, 'order_no' => $orderNo],
                $completedAt
            );
        }

        $history = [
            [null, OrderStatus::DRAFT->value, $creatorId, $createdAt],
            [OrderStatus::DRAFT->value, OrderStatus::SENT->value, $creatorId, $createdAt->copy()->addMinute()],
            [OrderStatus::SENT->value, OrderStatus::IN_PROGRESS->value, $context['staff']['kitchen'], $createdAt->copy()->addMinutes(2)],
            [OrderStatus::IN_PROGRESS->value, OrderStatus::SERVED->value, $context['staff']['kitchen'], $batchReadyAt],
            [OrderStatus::SERVED->value, OrderStatus::PAYMENT_REQUESTED->value, $creatorId, $createdAt->copy()->addMinutes(15)],
            [OrderStatus::PAYMENT_REQUESTED->value, OrderStatus::COMPLETED->value, $context['staff']['cashier'], $completedAt],
        ];

        foreach ($history as [$from, $to, $changedBy, $changedAt]) {
            DB::table('order_status_history')->insert([
                'order_id' => $orderId,
                'order_item_id' => null,
                'batch_id' => null,
                'from_status' => $from,
                'to_status' => $to,
                'changed_by' => $changedBy,
                'note' => 'Lịch sử trạng thái đơn mẫu.',
                'created_at' => $changedAt->toDateTimeString(),
            ]);
        }

        return [
            'guest_count' => $guestCount,
            'subtotal' => $subtotal,
            'total_cogs' => array_sum(array_column($items, 'cogs_total')),
            'gross_profit' => array_sum(array_column($items, 'gross_profit')),
            'payment_method' => $paymentMethod->value,
            'items' => $items,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $catalog
     */
    private function deductRecipeStock(
        array $context,
        object $recipe,
        array $catalog,
        int $orderItemId,
        int $orderItemQuantity,
        Carbon $occurredAt
    ): float {
        $recipeItems = $catalog['recipeItems'][$recipe->id] ?? collect();
        if ($recipeItems->isEmpty()) {
            throw new RuntimeException("Công thức {$recipe->id} chưa có nguyên liệu.");
        }

        $totalCost = 0.0;
        $ledger = app(StockLedgerService::class);
        $batchService = app(StockBatchService::class);

        foreach ($recipeItems as $recipeItem) {
            $quantity = (float) $recipeItem->quantity * $orderItemQuantity;
            $stockLevel = DB::table('stock_levels')
                ->where('warehouse_id', $context['warehouse_id'])
                ->where('ingredient_id', $recipeItem->ingredient_id)
                ->lockForUpdate()
                ->first();

            if ($stockLevel === null || (float) $stockLevel->quantity < $quantity) {
                throw new RuntimeException("Không đủ tồn kho cho nguyên liệu ID {$recipeItem->ingredient_id} tại {$context['code']}.");
            }

            $unitCost = (float) $stockLevel->avg_cost;
            $totalCost += $quantity * $unitCost;
            $movement = $this->at($occurredAt, fn () => $ledger->recordMovement(
                new StockMovementDTO(
                    warehouseId: $context['warehouse_id'],
                    ingredientId: (int) $recipeItem->ingredient_id,
                    movementType: MovementType::SALE_OUT,
                    quantity: -$quantity,
                    unitCost: $unitCost > 0 ? $unitCost : null,
                    refType: 'order_item',
                    refId: $orderItemId,
                    idempotencyKey: "demo_order_item_{$orderItemId}_ing_{$recipeItem->ingredient_id}",
                    note: 'Xuất kho cho đơn bán mẫu.',
                    createdBy: $context['staff']['warehouse']
                )
            ));

            $allocations = $this->at($occurredAt, fn () => $batchService->deductFefo(
                $context['warehouse_id'],
                (int) $recipeItem->ingredient_id,
                $quantity
            ));
            $allocatedQuantity = array_sum(array_column($allocations, 'deducted_qty'));

            if (abs($allocatedQuantity - $quantity) > 0.001) {
                throw new RuntimeException("Không phân bổ đủ lô tồn cho nguyên liệu ID {$recipeItem->ingredient_id}.");
            }

            if (count($allocations) === 1) {
                DB::table('stock_movements')->where('id', $movement->id)->update([
                    'batch_id' => $allocations[0]['batch_id'],
                ]);
            }
        }

        return round($totalCost, 2);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<int, array<string, int|float>>  $items
     */
    private function createKitchenTickets(
        array $context,
        int $orderId,
        int $batchId,
        string $orderNo,
        array $items,
        Carbon $createdAt,
        Carbon $readyAt
    ): void {
        $itemsByStation = collect($items)->groupBy('station_code');

        foreach ($itemsByStation as $stationCode => $stationItems) {
            $station = $context['stations']->get($stationCode);
            if ($station === null) {
                throw new RuntimeException("Thiếu trạm {$stationCode} tại {$context['code']}.");
            }

            $ticketNo = sprintf(
                'DK-%s-%s-%02d-%s',
                $context['code'],
                $createdAt->format('Ymd'),
                $orderId % 100,
                $stationCode
            );
            $ticketId = DB::table('kitchen_tickets')->insertGetId([
                'branch_id' => $context['branch_id'],
                'order_id' => $orderId,
                'batch_id' => $batchId,
                'station_id' => $station->id,
                'ticket_no' => $ticketNo,
                'status' => TicketStatus::DONE->value,
                'fired_at' => $createdAt->copy()->addMinute()->toDateTimeString(),
                'ready_at' => $readyAt->toDateTimeString(),
                'updated_by' => $context['staff']['kitchen'],
                'created_at' => $createdAt->copy()->addMinute()->toDateTimeString(),
                'updated_at' => $readyAt->toDateTimeString(),
            ]);

            foreach ($stationItems as $item) {
                DB::table('kitchen_ticket_items')->insert([
                    'ticket_id' => $ticketId,
                    'order_item_id' => $item['id'],
                    'status' => TicketItemStatus::READY->value,
                    'fire_at' => $createdAt->copy()->addMinute()->toDateTimeString(),
                    'started_at' => $createdAt->copy()->addMinutes(2)->toDateTimeString(),
                    'ready_at' => $readyAt->toDateTimeString(),
                    'created_at' => $createdAt->copy()->addMinute()->toDateTimeString(),
                    'updated_at' => $readyAt->toDateTimeString(),
                ]);
            }

            $this->createPrintJob(
                (int) $context['printers']->get('kitchen')->id,
                PrintDocType::KITCHEN_TICKET,
                $ticketId,
                ['ticket_no' => $ticketNo, 'order_no' => $orderNo, 'station_code' => $stationCode],
                $createdAt->copy()->addMinute()
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createPrintJob(int $printerId, PrintDocType $docType, int $refId, array $payload, Carbon $printedAt): void
    {
        DB::table('print_jobs')->insert([
            'printer_id' => $printerId,
            'doc_type' => $docType->value,
            'ref_id' => $refId,
            'payload' => json_encode($payload),
            'status' => PrintJobStatus::PRINTED->value,
            'retry_count' => 0,
            'printed_at' => $printedAt->toDateTimeString(),
            'created_at' => $printedAt->copy()->subSecond()->toDateTimeString(),
            'updated_at' => $printedAt->toDateTimeString(),
        ]);
    }

    /**
     * @param  array<string, array<string, mixed>>  $aggregates
     */
    private function createSalesReports(array $aggregates): void
    {
        foreach ($aggregates['daily'] as $branchCode => $branchData) {
            $branchId = DB::table('branches')->where('code', $branchCode)->value('id');

            foreach ($branchData as $date => $summary) {
                DB::table('daily_sales_summary')->insert([
                    'branch_id' => $branchId,
                    'sales_date' => $date,
                    'order_count' => $summary['order_count'],
                    'guest_count' => $summary['guest_count'],
                    'gross_revenue' => $summary['gross_revenue'],
                    'discount_amount' => $summary['discount_amount'],
                    'net_revenue' => $summary['net_revenue'],
                    'total_cogs' => round($summary['total_cogs'], 2),
                    'gross_profit' => round($summary['gross_profit'], 2),
                    'payment_breakdown' => json_encode($summary['payment_breakdown']),
                    'created_at' => Carbon::parse($date)->endOfDay()->toDateTimeString(),
                    'updated_at' => Carbon::parse($date)->endOfDay()->toDateTimeString(),
                ]);
            }
        }

        foreach ($aggregates['items'] as $item) {
            DB::table('daily_item_sales')->insert([
                'branch_id' => $item['branch_id'],
                'sales_date' => $item['sales_date'],
                'menu_item_id' => $item['menu_item_id'],
                'quantity' => $item['quantity'],
                'gross_revenue' => $item['gross_revenue'],
                'total_cogs' => round($item['total_cogs'], 2),
                'gross_profit' => round($item['gross_profit'], 2),
                'created_at' => Carbon::parse($item['sales_date'])->endOfDay()->toDateTimeString(),
                'updated_at' => Carbon::parse($item['sales_date'])->endOfDay()->toDateTimeString(),
            ]);
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $contexts
     */
    private function createKitchenCheckins(array $contexts): void
    {
        $ingredients = DB::table('ingredients')
            ->whereIn('sku', self::INGREDIENT_SKUS)
            ->get();

        for ($daysAgo = 7; $daysAgo >= 1; $daysAgo--) {
            $checkinDate = Carbon::today()->subDays($daysAgo);

            foreach ($contexts as $context) {
                $checkinAt = $checkinDate->copy()->setTime(7, 0);
                $checkinId = DB::table('kitchen_daily_checkins')->insertGetId([
                    'branch_id' => $context['branch_id'],
                    'warehouse_id' => $context['warehouse_id'],
                    'checkin_date' => $checkinDate->toDateString(),
                    'user_id' => $context['staff']['kitchen'],
                    'type' => 'start_shift',
                    'note' => 'Xác nhận đầu ca mẫu; dùng lượng tồn có sẵn.',
                    'created_at' => $checkinAt->toDateTimeString(),
                    'updated_at' => $checkinAt->toDateTimeString(),
                ]);

                foreach ($ingredients as $ingredient) {
                    DB::table('kitchen_daily_checkin_items')->insert([
                        'checkin_id' => $checkinId,
                        'ingredient_id' => $ingredient->id,
                        'quantity' => 0,
                        'action' => 'use_existing',
                        'note' => 'Đã kiểm tra tồn đầu ca.',
                        'created_at' => $checkinAt->toDateTimeString(),
                        'updated_at' => $checkinAt->toDateTimeString(),
                    ]);
                }
            }
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $contexts
     * @param  array<string, mixed>  $catalog
     * @param  array<string, array<string, mixed>>  $aggregates
     */
    private function createAiData(array $contexts, array $catalog, array $aggregates): void
    {
        foreach ($contexts as $branchCode => $context) {
            $branchDays = $aggregates['daily'][$branchCode] ?? [];
            $orderCount = array_sum(array_column($branchDays, 'order_count'));
            $revenue = array_sum(array_column($branchDays, 'net_revenue'));
            $now = now();

            $forecastRunId = DB::table('ai_runs')->insertGetId([
                'branch_id' => $context['branch_id'],
                'run_type' => AiRunType::DEMAND_FORECAST->value,
                'params' => json_encode(['period_days' => 7, 'source' => 'branch_demo_seeder']),
                'status' => AiRunStatus::DONE->value,
                'result_summary' => json_encode(['orders_analyzed' => $orderCount, 'revenue_analyzed' => $revenue]),
                'triggered_by' => $context['staff']['manager'],
                'started_at' => $now->copy()->subSeconds(3)->toDateTimeString(),
                'finished_at' => $now->toDateTimeString(),
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);

            foreach ($catalog['menus'] as $menuItem) {
                $soldQuantity = 0;
                foreach ($aggregates['items'] as $item) {
                    if ($item['branch_id'] === $context['branch_id'] && $item['menu_item_id'] === $menuItem->id) {
                        $soldQuantity += $item['quantity'];
                    }
                }

                DB::table('ai_forecasts')->insert([
                    'ai_run_id' => $forecastRunId,
                    'branch_id' => $context['branch_id'],
                    'menu_item_id' => $menuItem->id,
                    'ingredient_id' => null,
                    'forecast_date' => Carbon::tomorrow()->toDateString(),
                    'predicted_qty' => round(($soldQuantity / 7) * 1.05, 3),
                    'confidence' => 0.8200,
                    'model_version' => 'demo-rule-v1',
                    'created_at' => $now->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ]);
            }

            $shortageRunId = DB::table('ai_runs')->insertGetId([
                'branch_id' => $context['branch_id'],
                'run_type' => AiRunType::SHORTAGE_DETECTION->value,
                'params' => json_encode(['source' => 'stock_levels', 'demo' => true]),
                'status' => AiRunStatus::DONE->value,
                'result_summary' => json_encode(['low_stock_skus' => ['NL_BANH_PHO']]),
                'triggered_by' => $context['staff']['manager'],
                'started_at' => $now->copy()->subSeconds(2)->toDateTimeString(),
                'finished_at' => $now->toDateTimeString(),
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);

            $bread = $catalog['ingredients']->get('NL_BANH_PHO');
            $stock = DB::table('stock_levels')
                ->where('warehouse_id', $context['warehouse_id'])
                ->where('ingredient_id', $bread->id)
                ->first();
            if ((float) $stock->quantity < (float) $bread->min_stock) {
                $alertId = DB::table('ai_alerts')->insertGetId([
                    'branch_id' => $context['branch_id'],
                    'alert_type' => AlertType::LOW_STOCK_RISK->value,
                    'severity' => AlertSeverity::WARNING->value,
                    'ingredient_id' => $bread->id,
                    'payload' => json_encode([
                        'sku' => $bread->sku,
                        'quantity' => (float) $stock->quantity,
                        'min_stock' => (float) $bread->min_stock,
                        'source_run_id' => $shortageRunId,
                        'demo' => true,
                    ]),
                    'status' => AlertStatus::NEW->value,
                    'acknowledged_by' => null,
                    'resolved_at' => null,
                    'created_at' => $now->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ]);

                $this->createLowStockNotification($context, $alertId, $bread->name, $now);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function createLowStockNotification(array $context, int $alertId, string $ingredientName, Carbon $now): void
    {
        $notificationId = DB::table('notifications')->insertGetId([
            'branch_id' => $context['branch_id'],
            'target_user_id' => null,
            'target_role_code' => ConstantHelper::ROLE_WAREHOUSE,
            'type' => 'low_stock',
            'title' => 'Tồn kho thấp: '.$ingredientName,
            'body' => 'Dữ liệu mẫu ghi nhận nguyên liệu dưới mức tồn tối thiểu.',
            'payload' => json_encode(['alert_id' => $alertId, 'demo' => true]),
            'expires_at' => $now->copy()->addDays(7)->toDateTimeString(),
            'created_at' => $now->toDateTimeString(),
        ]);

        DB::table('notification_reads')->insert([
            'notification_id' => $notificationId,
            'user_id' => $context['staff']['warehouse'],
            'read_at' => $now->toDateTimeString(),
        ]);
    }

    /**
     * @param  array<string, array<string, mixed>>  $contexts
     * @param  array<string, array<string, mixed>>  $aggregates
     */
    private function createNotifications(array $contexts, array $aggregates): void
    {
        $now = now();

        foreach ($contexts as $branchCode => $context) {
            $notificationId = DB::table('notifications')->insertGetId([
                'branch_id' => $context['branch_id'],
                'target_user_id' => $context['staff']['manager'],
                'target_role_code' => null,
                'type' => 'system',
                'title' => 'Dữ liệu mẫu chi nhánh đã sẵn sàng',
                'body' => 'Đã tạo dữ liệu giao dịch và báo cáo mẫu trong 7 ngày.',
                'payload' => json_encode([
                    'branch_code' => $branchCode,
                    'order_count' => array_sum(array_column($aggregates['daily'][$branchCode], 'order_count')),
                    'demo' => true,
                ]),
                'expires_at' => $now->copy()->addDays(30)->toDateTimeString(),
                'created_at' => $now->toDateTimeString(),
            ]);

            DB::table('notification_reads')->insert([
                'notification_id' => $notificationId,
                'user_id' => $context['staff']['manager'],
                'read_at' => $now->toDateTimeString(),
            ]);
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $contexts
     * @param  array<string, array<string, mixed>>  $aggregates
     */
    private function createAuditLogs(array $contexts, array $aggregates): void
    {
        foreach ($contexts as $branchCode => $context) {
            DB::table('audit_logs')->insert([
                'branch_id' => $context['branch_id'],
                'user_id' => $context['staff']['manager'],
                'action' => 'demo_seed',
                'entity_type' => 'branch_activity',
                'entity_id' => $context['branch_id'],
                'old_value' => null,
                'new_value' => json_encode([
                    'branch_code' => $branchCode,
                    'order_count' => array_sum(array_column($aggregates['daily'][$branchCode], 'order_count')),
                    'days' => 7,
                    'demo' => true,
                ]),
                'ip_address' => null,
                'created_at' => now()->toDateTimeString(),
            ]);
        }
    }

    private function at(Carbon $timestamp, Closure $callback): mixed
    {
        $previous = Carbon::getTestNow();
        Carbon::setTestNow($timestamp);

        try {
            return $callback();
        } finally {
            Carbon::setTestNow($previous);
        }
    }
}
