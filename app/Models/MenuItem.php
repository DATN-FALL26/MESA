<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ItemType;
use App\Enums\StationCode;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $category_id
 * @property string $sku
 * @property string $name
 * @property string|null $description
 * @property ItemType $item_type
 * @property StationCode $station_code
 * @property string $base_price
 * @property string $tax_rate
 * @property int $prep_time_seconds
 * @property string|null $image_path
 * @property bool $is_active
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Category $category
 * @property-read Collection<int, ItemVariant> $variants
 * @property-read Collection<int, ModifierGroup> $modifierGroups
 * @property-read Collection<int, MenuItemModifierGroup> $menuItemModifierGroups
 * @property-read Collection<int, BranchMenuItem> $branchMenuItems
 * @property-read Collection<int, OrderItem> $orderItems
 * @property-read Collection<int, Recipe> $recipes
 * @property-read Collection<int, DailyItemSales> $dailyItemSales
 * @property-read Collection<int, AiForecast> $aiForecasts
 */
class MenuItem extends Model
{
    use SoftDeletes;

    protected $table = ConstantHelper::TABLE_MENU_ITEMS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'item_type' => ItemType::class,
            'station_code' => StationCode::class,
            'base_price' => 'decimal:0',
            'tax_rate' => 'decimal:2',
            'prep_time_seconds' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ItemVariant::class, 'menu_item_id');
    }

    public function modifierGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            ModifierGroup::class,
            ConstantHelper::TABLE_MENU_ITEM_MODIFIER_GROUPS,
            'menu_item_id',
            'group_id'
        );
    }

    public function menuItemModifierGroups(): HasMany
    {
        return $this->hasMany(MenuItemModifierGroup::class, 'menu_item_id');
    }

    public function branchMenuItems(): HasMany
    {
        return $this->hasMany(BranchMenuItem::class, 'menu_item_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'menu_item_id');
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class, 'menu_item_id');
    }

    public function dailyItemSales(): HasMany
    {
        return $this->hasMany(DailyItemSales::class, 'menu_item_id');
    }

    public function aiForecasts(): HasMany
    {
        return $this->hasMany(AiForecast::class, 'menu_item_id');
    }
}
