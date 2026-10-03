<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $code
 * @property string $name
 * @property int $default_seats
 * @property array<string, mixed>|null $layout_config
 * @property int $sort_order
 * @property bool $is_active
 * @property int|null $active_flag Generated column IF(deleted_at IS NULL, 1, NULL)
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Branch $branch
 * @property-read Collection<int, DiningTable> $diningTables
 */
class Area extends Model
{
    use SoftDeletes;

    protected $table = ConstantHelper::TABLE_AREAS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_seats' => 'integer',
            'layout_config' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function diningTables(): HasMany
    {
        return $this->hasMany(DiningTable::class, 'area_id');
    }
}
