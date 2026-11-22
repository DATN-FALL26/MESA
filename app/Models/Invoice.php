<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $branch_id
 * @property int $order_id
 * @property string $invoice_no
 * @property CarbonInterface $issued_at
 * @property string $total_amount
 * @property array<string, mixed>|null $buyer_info
 * @property InvoiceStatus $status
 * @property int $print_count
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Branch $branch
 * @property-read Order $order
 */
class Invoice extends Model
{
    protected $table = ConstantHelper::TABLE_INVOICES;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'total_amount' => 'decimal:0',
            'buyer_info' => 'array',
            'status' => InvoiceStatus::class,
            'print_count' => 'integer',
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
}
