<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $menu_item_id
 * @property string $name
 * @property string $price_delta
 * @property bool $is_default
 * @property bool $is_active
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read MenuItem $menuItem
 * @property-read Collection<int, OrderItem> $orderItems
 * @property-read Collection<int, Recipe> $recipes
 */
class ItemVariant extends Model
{
    protected $table = ConstantHelper::TABLE_ITEM_VARIANTS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_delta' => 'decimal:0',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'variant_id');
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class, 'variant_id');
    }
}
