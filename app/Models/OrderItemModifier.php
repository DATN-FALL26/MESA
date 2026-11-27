<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_item_id
 * @property int $modifier_id
 * @property string $name_snapshot
 * @property string $price_snapshot
 * @property int $quantity
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read OrderItem $orderItem
 * @property-read Modifier $modifier
 */
class OrderItemModifier extends Model
{
    protected $table = ConstantHelper::TABLE_ORDER_ITEM_MODIFIERS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_snapshot' => 'decimal:0',
            'quantity' => 'integer',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function modifier(): BelongsTo
    {
        return $this->belongsTo(Modifier::class, 'modifier_id');
    }
}
