<?php

declare(strict_types=1);

namespace App\Jobs\Inventory;

use App\DTOs\Inventory\StockMovementDTO;
use App\Enums\MovementType;
use App\Enums\WarehouseType;
use App\Models\OrderItem;
use App\Models\Warehouse;
use App\Services\Inventory\RecipeExplosionService;
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

    public function handle(RecipeExplosionService $explosion, StockLedgerService $ledger): void
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

        DB::transaction(function () use ($consumedIngredients, $warehouse, $orderItem, $ledger): void {
            foreach ($consumedIngredients as $ingredient) {
                if ($ingredient->quantity <= 0) {
                    continue;
                }

                $dto = new StockMovementDTO(
                    warehouseId: $warehouse->id,
                    ingredientId: $ingredient->ingredientId,
                    movementType: MovementType::SALE_OUT,
                    quantity: -abs($ingredient->quantity), // Số lượng âm biểu thị xuất kho
                    unitCost: null,
                    refType: 'order_item',
                    refId: $orderItem->id,
                    idempotencyKey: "order_item_{$orderItem->id}_ing_{$ingredient->ingredientId}",
                    note: "KDS trừ kho tự động: {$orderItem->item_name_snapshot} (x{$orderItem->quantity})",
                    createdBy: $this->userId,
                );

                $ledger->recordMovement($dto);
            }

            // Đánh dấu mốc thời gian trừ kho thành công
            $orderItem->update([
                'stock_deducted_at' => now(),
            ]);
        });

        Log::info('DeductStockForOrderItemJob: Đã trừ kho thành công cho order_item.', [
            'order_item_id' => $orderItem->id,
            'warehouse_id' => $warehouse->id,
            'ingredients_count' => count($consumedIngredients),
        ]);
    }
}
