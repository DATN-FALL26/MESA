<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockBatchStatus;
use App\Helpers\ConstantHelper;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $batch_code
 * @property int $warehouse_id
 * @property int $ingredient_id
 * @property int|null $purchase_order_id
 * @property int|null $supplier_id
 * @property string $initial_quantity
 * @property string $current_quantity
 * @property string $unit_cost
 * @property CarbonInterface $import_date
 * @property CarbonInterface $expiry_date
 * @property StockBatchStatus $status
 * @property string|null $note
 * @property int|null $created_by
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Warehouse $warehouse
 * @property-read Ingredient $ingredient
 * @property-read PurchaseOrder|null $purchaseOrder
 * @property-read Supplier|null $supplier
 * @property-read User|null $creator
 * @property-read int $days_until_expiration
 * @property-read bool $is_expired
 * @property-read bool $is_expiring_soon
 * @property-read string $quality_status
 */
class StockBatch extends Model
{
    protected $table = ConstantHelper::TABLE_STOCK_BATCHES;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'initial_quantity' => 'decimal:3',
            'current_quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'import_date' => 'date',
            'expiry_date' => 'date',
            'status' => StockBatchStatus::class,
        ];
    }

    protected $appends = [
        'days_until_expiration',
        'is_expired',
        'is_expiring_soon',
        'quality_status',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'batch_id');
    }

    // ==========================================
    // SCOPES (Tối ưu truy vấn hiệu năng cao)
    // ==========================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', StockBatchStatus::ACTIVE)
            ->where('current_quantity', '>', 0);
    }

    public function scopeExpiringSoon(Builder $query, int $days = 3): Builder
    {
        $today = Carbon::today()->toDateString();
        $targetDate = Carbon::today()->addDays($days)->toDateString();

        return $query->where('status', StockBatchStatus::ACTIVE)
            ->where('current_quantity', '>', 0)
            ->where('expiry_date', '>=', $today)
            ->where('expiry_date', '<=', $targetDate);
    }

    public function scopeExpired(Builder $query): Builder
    {
        $today = Carbon::today()->toDateString();

        return $query->where(function (Builder $q) use ($today) {
            $q->where('status', StockBatchStatus::EXPIRED)
                ->orWhere(function (Builder $sub) use ($today) {
                    $sub->where('status', StockBatchStatus::ACTIVE)
                        ->where('current_quantity', '>', 0)
                        ->where('expiry_date', '<', $today);
                });
        });
    }

    // ==========================================
    // COMPUTED ATTRIBUTES
    // ==========================================

    public function getDaysUntilExpirationAttribute(): int
    {
        if ($this->expiry_date === null) {
            return 999;
        }

        return (int) Carbon::today()->diffInDays($this->expiry_date, false);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->days_until_expiration < 0 || $this->status === StockBatchStatus::EXPIRED;
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        return ! $this->is_expired && $this->days_until_expiration <= 3;
    }

    public function getQualityStatusAttribute(): string
    {
        if ($this->is_expired) {
            return 'Đã hết hạn';
        }

        if ($this->is_expiring_soon) {
            return 'Sắp hết hạn';
        }

        if ($this->days_until_expiration <= 7) {
            return 'Cần lưu ý';
        }

        return 'Tốt / An toàn';
    }
}
