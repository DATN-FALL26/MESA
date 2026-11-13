<?php

declare(strict_types=1);

namespace App\Models;

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
 * @property string $checkin_date
 * @property int $user_id
 * @property int|null $purchase_order_id
 * @property string $type
 * @property string|null $note
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Branch $branch
 * @property-read Warehouse $warehouse
 * @property-read User $user
 * @property-read PurchaseOrder|null $purchaseOrder
 * @property-read Collection<int, KitchenDailyCheckinItem> $items
 */
class KitchenDailyCheckin extends Model
{
    protected $table = ConstantHelper::TABLE_KITCHEN_DAILY_CHECKINS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checkin_date' => 'date:Y-m-d',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(KitchenDailyCheckinItem::class, 'checkin_id');
    }
}
