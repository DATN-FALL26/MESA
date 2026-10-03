<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\AlertType;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $branch_id
 * @property AlertType $alert_type
 * @property AlertSeverity $severity
 * @property int|null $ingredient_id
 * @property array<string, mixed>|null $payload
 * @property AlertStatus $status
 * @property int|null $acknowledged_by
 * @property CarbonInterface|null $resolved_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Branch $branch
 * @property-read Ingredient|null $ingredient
 * @property-read User|null $acknowledger
 */
class AiAlert extends Model
{
    protected $table = ConstantHelper::TABLE_AI_ALERTS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'alert_type' => AlertType::class,
            'severity' => AlertSeverity::class,
            'payload' => 'array',
            'status' => AlertStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id');
    }

    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }
}
