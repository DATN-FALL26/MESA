<?php

declare(strict_types=1);

namespace App\Listeners\Inventory;

use App\Enums\TicketItemStatus;
use App\Events\Kitchen\KitchenTicketItemStatusChanged;
use App\Jobs\Inventory\DeductStockForOrderItemJob;

/**
 * Lắng nghe sự kiện món bếp hoàn thành và dispatch job trừ kho tự động.
 */
class DeductStockWhenKitchenItemReady
{
    public function handle(KitchenTicketItemStatusChanged $event): void
    {
        // Khi món đạt trạng thái READY, tiến hành trừ kho nguyên vật liệu
        if ($event->item->status === TicketItemStatus::READY) {
            DeductStockForOrderItemJob::dispatch(
                $event->item->order_item_id,
                $event->changedBy?->id
            );
        }
    }
}
