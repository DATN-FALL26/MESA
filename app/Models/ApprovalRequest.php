<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\ApprovalType;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $branch_id
 * @property ApprovalType $request_type
 * @property int $ref_id Ghi chú đa hình: id của purchase_orders hoặc stock_adjustments tùy theo request_type
 * @property int|null $requested_by
 * @property ApprovalStatus $status
 * @property int|null $decided_by
 * @property CarbonInterface|null $decided_at
 * @property string|null $note
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Branch $branch
 * @property-read User|null $requester
 * @property-read User|null $decider
 */
class ApprovalRequest extends Model
{
    protected $table = ConstantHelper::TABLE_APPROVAL_REQUESTS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_type' => ApprovalType::class,
            'status' => ApprovalStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
