<?php

declare(strict_types=1);

namespace App\Events\Kitchen;

use App\Enums\TicketItemStatus;
use App\Models\KitchenTicketItem;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện phát ra khi trạng thái một món trên phiếu bếp (KitchenTicketItem) thay đổi.
 */
class KitchenTicketItemStatusChanged
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public KitchenTicketItem $item,
        public TicketItemStatus $previousStatus,
        public ?User $changedBy = null,
    ) {}
}
