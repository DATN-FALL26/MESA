<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $adjustment_id
 * @property int $ingredient_id
 * @property string $system_qty
 * @property string $actual_qty
 * @property string|null $note
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read StockAdjustment $adjustment
 * @property-read Ingredient $ingredient
 */
class StockAdjustmentItem extends Model
{
    protected $table = ConstantHelper::TABLE_STOCK_ADJUSTMENT_ITEMS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'system_qty' => 'decimal:3',
            'actual_qty' => 'decimal:3',
        ];
    }

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'adjustment_id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id');
    }
}
