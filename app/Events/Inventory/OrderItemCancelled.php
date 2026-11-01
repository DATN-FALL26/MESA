<?php

declare(strict_types=1);

namespace App\Events\Inventory;

use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện phát ra khi một món trong đơn hàng bị hủy.
 */
class OrderItemCancelled
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public OrderItem $orderItem,
        public ?User $cancelledBy = null,
        public ?string $reason = null,
    ) {}
}
