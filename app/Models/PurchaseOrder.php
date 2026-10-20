<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PurchaseOrderSource;
use App\Enums\PurchaseOrderStatus;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $branch_id
 * @property int $warehouse_id
 * @property int|null $supplier_id
 * @property string $po_no
 * @property PurchaseOrderStatus $status
 * @property PurchaseOrderSource $source
 * @property string|null $note
 * @property int $created_by
 * @property int|null $approved_by
 * @property CarbonInterface|null $approved_at
 * @property int|null $received_by
 * @property CarbonInterface|null $received_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Branch $branch
 * @property-read Warehouse $warehouse
 * @property-read Supplier|null $supplier
 * @property-read User $creator
 * @property-read User|null $approver
 * @property-read User|null $receiver
 * @property-read Collection<int, PurchaseOrderItem> $items
 */
class PurchaseOrder extends Model
{
    protected $table = ConstantHelper::TABLE_PURCHASE_ORDERS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'source' => PurchaseOrderSource::class,
            'approved_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }
}
