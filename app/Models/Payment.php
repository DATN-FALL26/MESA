<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $branch_id
 * @property int $order_id
 * @property PaymentMethod $method
 * @property string $amount
 * @property string|null $tendered_amount
 * @property string|null $change_amount
 * @property PaymentStatus $status
 * @property string|null $gateway_txn_id
 * @property array<string, mixed>|null $gateway_payload
 * @property int|null $confirmed_by
 * @property CarbonInterface|null $paid_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Branch $branch
 * @property-read Order $order
 * @property-read User|null $confirmer
 */
class Payment extends Model
{
    protected $table = ConstantHelper::TABLE_PAYMENTS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'amount' => 'decimal:0',
            'tendered_amount' => 'decimal:0',
            'change_amount' => 'decimal:0',
            'status' => PaymentStatus::class,
            'gateway_payload' => 'array',
            'paid_at' => 'datetime',
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

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
