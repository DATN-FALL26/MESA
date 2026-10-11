<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderItemStatus;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $order_id
 * @property int|null $batch_id
 * @property int $menu_item_id
 * @property int|null $variant_id
 * @property string $item_name_snapshot
 * @property string|null $variant_name_snapshot
 * @property string $unit_price_snapshot
 * @property int $quantity
 * @property string $line_total
 * @property string|null $note
 * @property OrderItemStatus $status
 * @property string|null $cancelled_reason
 * @property int|null $cancelled_by
 * @property CarbonInterface|null $cancelled_at
 * @property CarbonInterface|null $stock_deducted_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Order $order
 * @property-read OrderBatch|null $batch
 * @property-read MenuItem $menuItem
 * @property-read ItemVariant|null $variant
 * @property-read User|null $canceller
 * @property-read Collection<int, OrderItemModifier> $modifiers
 * @property-read Collection<int, KitchenTicketItem> $kitchenTicketItems
 * @property-read Collection<int, OrderStatusHistory> $statusHistories
 */
class OrderItem extends Model
{
    protected $table = ConstantHelper::TABLE_ORDER_ITEMS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price_snapshot' => 'decimal:0',
            'quantity' => 'integer',
            'line_total' => 'decimal:0',
            'status' => OrderItemStatus::class,
            'cancelled_at' => 'datetime',
            'stock_deducted_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(OrderBatch::class, 'batch_id');
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class, 'variant_id');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(OrderItemModifier::class, 'order_item_id');
    }

    public function kitchenTicketItems(): HasMany
    {
        return $this->hasMany(KitchenTicketItem::class, 'order_item_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_item_id');
    }
}
