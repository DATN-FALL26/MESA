<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IngredientType;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $sku
 * @property string $name
 * @property IngredientType $ingredient_type
 * @property string $base_uom
 * @property string $min_stock
 * @property bool $is_active
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Collection<int, Modifier> $modifiers
 * @property-read Collection<int, StockLevel> $stockLevels
 * @property-read Collection<int, StockMovement> $stockMovements
 * @property-read Collection<int, RecipeItem> $recipeItems
 * @property-read Collection<int, PurchaseOrderItem> $purchaseOrderItems
 * @property-read Collection<int, StockAdjustmentItem> $stockAdjustmentItems
 * @property-read Collection<int, AiForecast> $aiForecasts
 * @property-read Collection<int, AiAlert> $aiAlerts
 */
class Ingredient extends Model
{
    use SoftDeletes;

    protected $table = ConstantHelper::TABLE_INGREDIENTS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ingredient_type' => IngredientType::class,
            'min_stock' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(Modifier::class, 'ingredient_id');
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class, 'ingredient_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'ingredient_id');
    }

    public function recipeItems(): HasMany
    {
        return $this->hasMany(RecipeItem::class, 'ingredient_id');
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'ingredient_id');
    }

    public function stockAdjustmentItems(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class, 'ingredient_id');
    }

    public function aiForecasts(): HasMany
    {
        return $this->hasMany(AiForecast::class, 'ingredient_id');
    }

    public function aiAlerts(): HasMany
    {
        return $this->hasMany(AiAlert::class, 'ingredient_id');
    }
}
