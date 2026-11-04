<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WarehouseType;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $name
 * @property WarehouseType $type
 * @property bool $is_active
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Branch $branch
 * @property-read Collection<int, StockLevel> $stockLevels
 * @property-read Collection<int, StockMovement> $stockMovements
 * @property-read Collection<int, PurchaseOrder> $purchaseOrders
 * @property-read Collection<int, StockAdjustment> $stockAdjustments
 */
class Warehouse extends Model
{
    protected $table = ConstantHelper::TABLE_WAREHOUSES;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => WarehouseType::class,
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class, 'warehouse_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'warehouse_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'warehouse_id');
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class, 'warehouse_id');
    }
}
