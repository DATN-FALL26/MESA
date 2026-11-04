<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SessionStatus;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $branch_id
 * @property int $opened_by
 * @property CarbonInterface $opened_at
 * @property CarbonInterface|null $closed_at
 * @property int $guest_count
 * @property SessionStatus $status
 * @property string|null $note
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Branch $branch
 * @property-read User $openedByUser
 * @property-read Collection<int, SessionTable> $sessionTables
 * @property-read Collection<int, Order> $orders
 */
class DiningSession extends Model
{
    protected $table = ConstantHelper::TABLE_DINING_SESSIONS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'guest_count' => 'integer',
            'status' => SessionStatus::class,
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function openedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function sessionTables(): HasMany
    {
        return $this->hasMany(SessionTable::class, 'session_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'session_id');
    }
}
