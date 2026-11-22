<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketStatus;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $branch_id
 * @property int $order_id
 * @property int|null $batch_id
 * @property int $station_id
 * @property string $ticket_no
 * @property TicketStatus $status
 * @property CarbonInterface $fired_at
 * @property CarbonInterface|null $ready_at
 * @property int|null $updated_by
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Branch $branch
 * @property-read Order $order
 * @property-read OrderBatch|null $batch
 * @property-read Station $station
 * @property-read User|null $updater
 * @property-read Collection<int, KitchenTicketItem> $items
 */
class KitchenTicket extends Model
{
    protected $table = ConstantHelper::TABLE_KITCHEN_TICKETS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'fired_at' => 'datetime',
            'ready_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(OrderBatch::class, 'batch_id');
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'station_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(KitchenTicketItem::class, 'ticket_id');
    }
}
