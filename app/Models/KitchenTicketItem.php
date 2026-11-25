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
 * @property int|null $notified_quantity
 * @property int|null $sent_quantity
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

    /**
     * MOD04: số lượng đã gửi bếp (Gửi bếp / Cập nhật bếp). Phục vụ bấm +/− mà chưa "Cập nhật bếp" thì bếp vẫn thấy số này.
     */
    public function sentQuantity(): int
    {
        return (int) ($this->sent_quantity ?? $this->orderItem->quantity);
    }
}
