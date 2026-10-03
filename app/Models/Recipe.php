<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $menu_item_id
 * @property int|null $variant_id
 * @property int $variant_key Generated column IFNULL(variant_id, 0)
 * @property int $version
 * @property bool $is_active
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read MenuItem $menuItem
 * @property-read ItemVariant|null $variant
 * @property-read Collection<int, RecipeItem> $items
 * @property-read Collection<int, Ingredient> $ingredients
 */
class Recipe extends Model
{
    protected $table = ConstantHelper::TABLE_RECIPES;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class, 'variant_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecipeItem::class, 'recipe_id');
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(
            Ingredient::class,
            ConstantHelper::TABLE_RECIPE_ITEMS,
            'recipe_id',
            'ingredient_id'
        );
    }
}
