<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketItemStatus;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $ticket_id
 * @property int $order_item_id
 * @property TicketItemStatus $status
 * @property CarbonInterface|null $fire_at
 * @property CarbonInterface|null $started_at
 * @property CarbonInterface|null $ready_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read KitchenTicket $ticket
 * @property-read OrderItem $orderItem
 */
class KitchenTicketItem extends Model
{
    protected $table = ConstantHelper::TABLE_KITCHEN_TICKET_ITEMS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TicketItemStatus::class,
            'fire_at' => 'datetime',
            'started_at' => 'datetime',
            'ready_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(KitchenTicket::class, 'ticket_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
