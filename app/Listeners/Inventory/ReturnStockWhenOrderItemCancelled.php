<?php

declare(strict_types=1);

namespace App\Listeners\Inventory;

use App\Events\Inventory\OrderItemCancelled;
use App\Jobs\Inventory\ReturnStockForOrderItemJob;

/**
 * Lắng nghe sự kiện món bị hủy và dispatch job hoàn kho nếu món đã từng bị trừ kho.
 */
class ReturnStockWhenOrderItemCancelled
{
    public function handle(OrderItemCancelled $event): void
    {
        if ($event->orderItem->stock_deducted_at !== null) {
            ReturnStockForOrderItemJob::dispatch(
                $event->orderItem->id,
                $event->cancelledBy?->id,
                $event->reason
            );
        }
    }
}
