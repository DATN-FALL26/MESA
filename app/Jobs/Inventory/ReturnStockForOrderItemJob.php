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
 * Job bất đồng bộ hoàn kho khi món ăn bị hủy sau khi đã trừ kho (MOD07 Task 6.2).
 */
class ReturnStockForOrderItemJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $orderItemId,
        public ?int $userId = null,
        public ?string $reason = null,
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
            Log::warning('ReturnStockForOrderItemJob: Không tìm thấy order_item để hoàn kho.', [
                'order_item_id' => $this->orderItemId,
            ]);
            return;
        }

        // 1. Chỉ hoàn kho nếu món ĐÃ TỪNG được trừ kho
        if ($orderItem->stock_deducted_at === null) {
            Log::info('ReturnStockForOrderItemJob: Món chưa từng bị trừ kho, không cần hoàn kho.', [
                'order_item_id' => $orderItem->id,
            ]);
            return;
        }

        $branchId = $orderItem->order?->branch_id;
        if ($branchId === null) {
            Log::error('ReturnStockForOrderItemJob: Đơn hàng không xác định được branch_id.', [
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
            Log::warning('ReturnStockForOrderItemJob: Không tìm thấy kho hoạt động cho chi nhánh.', [
                'order_item_id' => $orderItem->id,
                'branch_id' => $branchId,
            ]);
            return;
        }

        // 3. Bóc tách lại nguyên liệu tiêu hao đã trừ
        $consumedIngredients = $explosion->explodeOrderItem($orderItem);

        DB::transaction(function () use ($consumedIngredients, $warehouse, $orderItem, $ledger): void {
            foreach ($consumedIngredients as $ingredient) {
                if ($ingredient->quantity <= 0) {
                    continue;
                }

                $dto = new StockMovementDTO(
                    warehouseId: $warehouse->id,
                    ingredientId: $ingredient->ingredientId,
                    movementType: MovementType::SALE_RETURN,
                    quantity: abs($ingredient->quantity), // Số lượng dương biểu thị nhập hoàn kho
                    unitCost: null,
                    refType: 'order_item',
                    refId: $orderItem->id,
                    idempotencyKey: "return_order_item_{$orderItem->id}_ing_{$ingredient->ingredientId}",
                    note: 'Hoàn kho do hủy món: '.$orderItem->item_name_snapshot.($this->reason ? ' ('.$this->reason.')' : ''),
                    createdBy: $this->userId,
                );

                $ledger->recordMovement($dto);
            }

            // Đặt lại stock_deducted_at về null để chống hoàn kho lần 2
            $orderItem->update([
                'stock_deducted_at' => null,
            ]);
        });

        Log::info('ReturnStockForOrderItemJob: Đã hoàn kho thành công cho order_item bị hủy.', [
            'order_item_id' => $orderItem->id,
            'warehouse_id' => $warehouse->id,
            'ingredients_count' => count($consumedIngredients),
        ]);
    }
}
