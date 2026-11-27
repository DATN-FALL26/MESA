<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\OrderItemStatus;
use App\Jobs\Inventory\DeductStockForOrderItemJob;
use App\Jobs\Inventory\ReturnStockForOrderItemJob;
use App\Models\OrderItem;

/**
 * Theo dõi thay đổi trạng thái của OrderItem để tự động trừ kho hoặc hoàn kho.
 */
class OrderItemObserver
{
    public function updated(OrderItem $orderItem): void
    {
        if (! $orderItem->wasChanged('status')) {
            return;
        }

        // 1. Nếu món bị hủy sau khi đã trừ kho -> Hoàn kho
        if ($orderItem->status === OrderItemStatus::CANCELLED && $orderItem->stock_deducted_at !== null) {
            ReturnStockForOrderItemJob::dispatch(
                $orderItem->id,
                $orderItem->cancelled_by,
                $orderItem->cancelled_reason
            );
            return;
        }

        // 2. Nếu món chuyển sang READY hoặc SERVED mà chưa được trừ kho -> Trừ kho
        if (
            in_array($orderItem->status, [OrderItemStatus::READY, OrderItemStatus::SERVED], true)
            && $orderItem->stock_deducted_at === null
        ) {
            DeductStockForOrderItemJob::dispatch($orderItem->id);
        }
    }
}
