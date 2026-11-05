<?php

declare(strict_types=1);

namespace App\Events\Kitchen;

use App\Enums\TicketItemStatus;
use App\Models\KitchenTicketItem;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KitchenTicketItemStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public KitchenTicketItem $item,
        public TicketItemStatus $previousStatus,
        public ?User $changedBy = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('orders.'.$this->item->orderItem->order_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'kitchen.item.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'order_item_id' => $this->item->order_item_id,
            'status' => $this->item->status->value,
            'previous_status' => $this->previousStatus->value,
        ];
    }
}