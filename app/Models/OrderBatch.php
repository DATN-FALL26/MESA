<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BatchStatus;
use App\Enums\ServeMode;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $order_id
 * @property int $batch_no
 * @property ServeMode $serve_mode
 * @property BatchStatus $status
 * @property CarbonInterface|null $sent_at
 * @property CarbonInterface|null $target_ready_at
 * @property CarbonInterface|null $all_ready_at
 * @property CarbonInterface|null $served_at
 * @property int|null $released_by
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Order $order
 * @property-read User|null $releaser
 * @property-read Collection<int, OrderItem> $items
 * @property-read Collection<int, KitchenTicket> $kitchenTickets
 */
class OrderBatch extends Model
{
    protected $table = ConstantHelper::TABLE_ORDER_BATCHES;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'batch_no' => 'integer',
            'serve_mode' => ServeMode::class,
            'status' => BatchStatus::class,
            'sent_at' => 'datetime',
            'target_ready_at' => 'datetime',
            'all_ready_at' => 'datetime',
            'served_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function releaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'batch_id');
    }

    public function kitchenTickets(): HasMany
    {
        return $this->hasMany(KitchenTicket::class, 'batch_id');
    }
}
