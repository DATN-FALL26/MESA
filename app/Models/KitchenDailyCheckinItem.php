<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $checkin_id
 * @property int $ingredient_id
 * @property string $quantity
 * @property string $unit_price
 * @property string $action
 * @property string|null $note
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read KitchenDailyCheckin $checkin
 * @property-read Ingredient $ingredient
 */
class KitchenDailyCheckinItem extends Model
{
    protected $table = ConstantHelper::TABLE_KITCHEN_DAILY_CHECKIN_ITEMS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
        ];
    }

    public function checkin(): BelongsTo
    {
        return $this->belongsTo(KitchenDailyCheckin::class, 'checkin_id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id');
    }
}
