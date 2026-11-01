<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TableShape;
use App\Enums\TableStatus;
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
 * @property int|null $area_id
 * @property string $code
 * @property int $seats
 * @property TableShape $shape
 * @property int|null $pos_x
 * @property int|null $pos_y
 * @property int $width
 * @property int $height
 * @property int $sort_order
 * @property TableStatus $status
 * @property bool $is_active
 * @property int|null $active_flag Generated column IF(deleted_at IS NULL, 1, NULL)
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Branch $branch
 * @property-read Area|null $area
 * @property-read Collection<int, SessionTable> $sessionTables
 */
class DiningTable extends Model
{
    use SoftDeletes;

    protected $table = ConstantHelper::TABLE_DINING_TABLES;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'seats' => 'integer',
            'shape' => TableShape::class,
            'pos_x' => 'integer',
            'pos_y' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'sort_order' => 'integer',
            'status' => TableStatus::class,
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function sessionTables(): HasMany
    {
        return $this->hasMany(SessionTable::class, 'table_id');
    }
}
