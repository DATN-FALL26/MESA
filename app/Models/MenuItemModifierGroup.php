<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\ConstantHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $menu_item_id
 * @property int $group_id
 * @property int $sort_order
 * @property-read MenuItem $menuItem
 * @property-read ModifierGroup $modifierGroup
 */
class MenuItemModifierGroup extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $table = ConstantHelper::TABLE_MENU_ITEM_MODIFIER_GROUPS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }

    public function modifierGroup(): BelongsTo
    {
        return $this->belongsTo(ModifierGroup::class, 'group_id');
    }
}
