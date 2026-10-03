<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int $min_select
 * @property int $max_select
 * @property bool $is_active
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Collection<int, Modifier> $modifiers
 * @property-read Collection<int, MenuItem> $menuItems
 * @property-read Collection<int, MenuItemModifierGroup> $menuItemModifierGroups
 */
class ModifierGroup extends Model
{
    protected $table = ConstantHelper::TABLE_MODIFIER_GROUPS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_select' => 'integer',
            'max_select' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(Modifier::class, 'group_id');
    }

    public function menuItems(): BelongsToMany
    {
        return $this->belongsToMany(
            MenuItem::class,
            ConstantHelper::TABLE_MENU_ITEM_MODIFIER_GROUPS,
            'group_id',
            'menu_item_id'
        );
    }

    public function menuItemModifierGroups(): HasMany
    {
        return $this->hasMany(MenuItemModifierGroup::class, 'group_id');
    }
}
