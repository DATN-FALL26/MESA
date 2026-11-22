<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AiRunStatus;
use App\Enums\AiRunType;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $branch_id
 * @property AiRunType $run_type
 * @property array<string, mixed>|null $params
 * @property AiRunStatus $status
 * @property array<string, mixed>|null $result_summary
 * @property int|null $triggered_by
 * @property CarbonInterface|null $started_at
 * @property CarbonInterface|null $finished_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Branch|null $branch
 * @property-read User|null $triggerer
 * @property-read Collection<int, AiForecast> $forecasts
 */
class AiRun extends Model
{
    protected $table = ConstantHelper::TABLE_AI_RUNS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'run_type' => AiRunType::class,
            'params' => 'array',
            'status' => AiRunStatus::class,
            'result_summary' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function triggerer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function forecasts(): HasMany
    {
        return $this->hasMany(AiForecast::class, 'ai_run_id');
    }
}
