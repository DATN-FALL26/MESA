<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $branch_id
 * @property int|null $session_id
 * @property string $order_no
 * @property OrderChannel $channel
 * @property OrderStatus $status
 * @property string $subtotal
 * @property string $discount_amount
 * @property string $tax_amount
 * @property string $total_amount
 * @property string|null $customer_name
 * @property string|null $customer_phone
 * @property string|null $note
 * @property int $created_by
 * @property int|null $payment_requested_by
 * @property CarbonInterface|null $payment_requested_at
 * @property int|null $cancelled_by
 * @property string|null $cancel_reason
 * @property CarbonInterface|null $cancelled_at
 * @property CarbonInterface|null $completed_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Branch $branch
 * @property-read DiningSession|null $session
 * @property-read User $creator
 * @property-read User|null $paymentRequester
 * @property-read User|null $canceller
 * @property-read Collection<int, OrderBatch> $batches
 * @property-read Collection<int, OrderItem> $items
 * @property-read Collection<int, OrderStatusHistory> $statusHistories
 * @property-read Collection<int, KitchenTicket> $kitchenTickets
 * @property-read Collection<int, Payment> $payments
 * @property-read Collection<int, Invoice> $invoices
 */
class Order extends Model
{
    protected $table = ConstantHelper::TABLE_ORDERS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => OrderChannel::class,
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:0',
            'discount_amount' => 'decimal:0',
            'tax_amount' => 'decimal:0',
            'total_amount' => 'decimal:0',
            'payment_requested_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(DiningSession::class, 'session_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paymentRequester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_requested_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(OrderBatch::class, 'order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id');
    }

    public function kitchenTickets(): HasMany
    {
        return $this->hasMany(KitchenTicket::class, 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'order_id');
    }
}
