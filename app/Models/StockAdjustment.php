<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentStatus;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $warehouse_id
 * @property string $adjustment_no
 * @property AdjustmentReason $reason
 * @property string|null $note
 * @property AdjustmentStatus $status
 * @property int $created_by
 * @property int|null $approved_by
 * @property CarbonInterface|null $approved_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Warehouse $warehouse
 * @property-read User $creator
 * @property-read User|null $approver
 * @property-read Collection<int, StockAdjustmentItem> $items
 */
class StockAdjustment extends Model
{
    protected $table = ConstantHelper::TABLE_STOCK_ADJUSTMENTS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => AdjustmentReason::class,
            'status' => AdjustmentStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class, 'adjustment_id');
    }
}
