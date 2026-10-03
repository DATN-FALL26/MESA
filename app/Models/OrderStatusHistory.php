<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int|null $order_item_id
 * @property int|null $batch_id
 * @property string|null $from_status
 * @property string $to_status
 * @property int|null $changed_by
 * @property string|null $note
 * @property CarbonInterface|null $created_at
 * @property-read Order $order
 * @property-read OrderItem|null $orderItem
 * @property-read OrderBatch|null $batch
 * @property-read User|null $changer
 */
class OrderStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = ConstantHelper::TABLE_ORDER_STATUS_HISTORY;

    protected $guarded = [];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(OrderBatch::class, 'batch_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
