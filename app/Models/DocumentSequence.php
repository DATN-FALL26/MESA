<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SequenceResetPolicy;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $doc_type
 * @property string $period_key
 * @property string $prefix
 * @property int $current_no
 * @property int $padding
 * @property SequenceResetPolicy $reset_policy
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Branch $branch
 */
class DocumentSequence extends Model
{
    protected $table = ConstantHelper::TABLE_DOCUMENT_SEQUENCES;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_no' => 'integer',
            'padding' => 'integer',
            'reset_policy' => SequenceResetPolicy::class,
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
