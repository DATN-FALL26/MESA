<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MovementType;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $warehouse_id
 * @property int $ingredient_id
 * @property MovementType $movement_type
 * @property string $quantity
 * @property string|null $unit_cost
 * @property string|null $ref_type
 * @property int|null $ref_id
 * @property string|null $idempotency_key
 * @property string|null $note
 * @property int|null $created_by
 * @property CarbonInterface|null $created_at
 * @property-read Warehouse $warehouse
 * @property-read Ingredient $ingredient
 * @property-read User|null $creator
 */
class StockMovement extends Model
{
    public const UPDATED_AT = null;

    protected $table = ConstantHelper::TABLE_STOCK_MOVEMENTS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'movement_type' => MovementType::class,
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
