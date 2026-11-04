<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $purchase_order_id
 * @property int $ingredient_id
 * @property string $quantity
 * @property string $unit_price
 * @property string $received_qty
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read PurchaseOrder $purchaseOrder
 * @property-read Ingredient $ingredient
 */
class PurchaseOrderItem extends Model
{
    protected $table = ConstantHelper::TABLE_PURCHASE_ORDER_ITEMS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'received_qty' => 'decimal:3',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id');
    }
}
