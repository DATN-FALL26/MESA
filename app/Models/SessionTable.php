<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $session_id
 * @property int $table_id
 * @property CarbonInterface $joined_at
 * @property CarbonInterface|null $left_at
 * @property int|null $open_guard Generated column IF(left_at IS NULL, table_id, NULL)
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read DiningSession $session
 * @property-read DiningTable $diningTable
 */
class SessionTable extends Model
{
    protected $table = ConstantHelper::TABLE_SESSION_TABLES;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(DiningSession::class, 'session_id');
    }

    public function diningTable(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class, 'table_id');
    }
}
