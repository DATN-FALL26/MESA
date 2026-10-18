<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $branch_id
 * @property CarbonInterface $sales_date
 * @property int $order_count
 * @property int $guest_count
 * @property string $gross_revenue
 * @property string $discount_amount
 * @property string $net_revenue
 * @property array<string, mixed>|null $payment_breakdown
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Branch $branch
 */
class DailySalesSummary extends Model
{
    protected $table = ConstantHelper::TABLE_DAILY_SALES_SUMMARY;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sales_date' => 'date',
            'order_count' => 'integer',
            'guest_count' => 'integer',
            'gross_revenue' => 'decimal:0',
            'discount_amount' => 'decimal:0',
            'net_revenue' => 'decimal:0',
            'payment_breakdown' => 'array',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
