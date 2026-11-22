<?php

declare(strict_types=1);

namespace App\Jobs\Inventory;

use App\DTOs\Inventory\StockMovementDTO;
use App\Enums\MovementType;
use App\Enums\WarehouseType;
use App\Models\OrderItem;
use App\Models\Warehouse;
use App\Services\Inventory\RecipeExplosionService;
use App\Services\Inventory\StockAlertService;
use App\Services\Inventory\StockBatchService;
use App\Services\Inventory\StockLedgerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job bất đồng bộ trừ kho tự động khi món ăn hoàn thành từ Bếp / KDS hoặc được phục vụ (MOD07 Task 6.1).
 */
class DeductStockForOrderItemJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $orderItemId,
        public ?int $userId = null,
    ) {}

    public function handle(RecipeExplosionService $explosion, StockLedgerService $ledger, StockBatchService $batchService, StockAlertService $alertService): void
    {
        $orderItem = OrderItem::with([
            'order',
            'menuItem',
            'variant',
            'modifiers.modifier.ingredient',
        ])->find($this->orderItemId);

        if ($orderItem === null) {
            Log::warning('DeductStockForOrderItemJob: Không tìm thấy order_item để trừ kho.', [
                'order_item_id' => $this->orderItemId,
            ]);
            return;
        }

        // 1. Chống trừ lặp ở cấp độ thuộc tính model (Idempotency check)
        if ($orderItem->stock_deducted_at !== null) {
            Log::info('DeductStockForOrderItemJob: Món đã được trừ kho trước đó, bỏ qua.', [
                'order_item_id' => $orderItem->id,
                'stock_deducted_at' => $orderItem->stock_deducted_at,
            ]);
            return;
        }

        $branchId = $orderItem->order?->branch_id;
        if ($branchId === null) {
            Log::error('DeductStockForOrderItemJob: Đơn hàng không xác định được branch_id.', [
                'order_item_id' => $orderItem->id,
                'order_id' => $orderItem->order_id,
            ]);
            return;
        }

        // 2. Tìm kho chi nhánh tương ứng
        $warehouse = Warehouse::query()
            ->where('branch_id', $branchId)
            ->where('type', WarehouseType::BRANCH)
            ->where('is_active', true)
            ->first()
            ?? Warehouse::query()
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->first();

        if ($warehouse === null) {
            Log::warning('DeductStockForOrderItemJob: Không tìm thấy kho hoạt động cho chi nhánh.', [
                'order_item_id' => $orderItem->id,
                'branch_id' => $branchId,
            ]);
            return;
        }

        // 3. Bóc tách định lượng nguyên vật liệu tiêu hao (gồm món chính + variant + topping)
        $consumedIngredients = $explosion->explodeOrderItem($orderItem);

        // Lấy giá vốn bình quân (avg_cost) của các nguyên vật liệu liên quan tại thời điểm trừ kho
        $ingredientIds = array_map(fn ($ing) => (int) $ing->ingredientId, $consumedIngredients);
        $avgCosts = DB::table('stock_levels')
            ->where('warehouse_id', $warehouse->id)
            ->whereIn('ingredient_id', $ingredientIds)
            ->pluck('avg_cost', 'ingredient_id')
            ->map(fn ($cost) => (float) $cost)
            ->toArray();

        DB::transaction(function () use ($consumedIngredients, $warehouse, $orderItem, $ledger, $batchService, $avgCosts): void {
            $totalCogs = 0.0;

            foreach ($consumedIngredients as $ingredient) {
                if ($ingredient->quantity <= 0) {
                    continue;
                }

                $qtyToDeduct = abs($ingredient->quantity);
                $unitCost = (float) ($avgCosts[$ingredient->ingredientId] ?? 0.0);
                $totalCogs += round($qtyToDeduct * $unitCost, 2);

                $dto = new StockMovementDTO(
                    warehouseId: $warehouse->id,
                    ingredientId: $ingredient->ingredientId,
                    movementType: MovementType::SALE_OUT,
                    quantity: -$qtyToDeduct, // Số lượng âm biểu thị xuất kho
                    unitCost: $unitCost > 0 ? $unitCost : null,
                    refType: 'order_item',
                    refId: $orderItem->id,
                    idempotencyKey: "order_item_{$orderItem->id}_ing_{$ingredient->ingredientId}",
                    note: "KDS trừ kho tự động: {$orderItem->item_name_snapshot} (x{$orderItem->quantity})",
                    createdBy: $this->userId,
                );

                $ledger->recordMovement($dto);

                // Trừ theo nguyên tắc FEFO trên các lô đang hoạt động
                $batchService->deductFefo($warehouse->id, $ingredient->ingredientId, $qtyToDeduct);
            }

            $totalCogs = round($totalCogs, 2);
            $itemQty = max(1, (int) $orderItem->quantity);
            $unitCogs = round($totalCogs / $itemQty, 2);
            $grossProfit = round((float) $orderItem->line_total - $totalCogs, 2);

            // Đánh dấu mốc thời gian trừ kho thành công và chốt snapshot giá vốn + lợi nhuận
            $orderItem->update([
                'cost_price_snapshot' => $unitCogs,
                'cogs_total' => $totalCogs,
                'gross_profit' => $grossProfit,
                'stock_deducted_at' => now(),
            ]);
        });

        // 4. Kiểm tra cảnh báo nếu có nguyên liệu rơi xuống dưới mức tồn tối thiểu
        try {
            $alertService->scanLowStockAlerts((int) $branchId);
        } catch (\Throwable $e) {
            Log::error('DeductStockForOrderItemJob: Lỗi khi quét cảnh báo tồn kho: ' . $e->getMessage());
        }

        Log::info('DeductStockForOrderItemJob: Đã trừ kho thành công cho order_item.', [
            'order_item_id' => $orderItem->id,
            'warehouse_id' => $warehouse->id,
            'ingredients_count' => count($consumedIngredients),
        ]);
    }
}
