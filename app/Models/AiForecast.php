<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $ai_run_id
 * @property int $branch_id
 * @property int|null $menu_item_id
 * @property int|null $ingredient_id
 * @property CarbonInterface $forecast_date
 * @property string $predicted_qty
 * @property string|null $confidence
 * @property string $model_version
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read AiRun $aiRun
 * @property-read Branch $branch
 * @property-read MenuItem|null $menuItem
 * @property-read Ingredient|null $ingredient
 */
class AiForecast extends Model
{
    protected $table = ConstantHelper::TABLE_AI_FORECASTS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'forecast_date' => 'date',
            'predicted_qty' => 'decimal:3',
            'confidence' => 'decimal:4',
        ];
    }

    public function aiRun(): BelongsTo
    {
        return $this->belongsTo(AiRun::class, 'ai_run_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id');
    }
}
