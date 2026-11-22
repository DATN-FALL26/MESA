<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationType;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $branch_id
 * @property int|null $target_user_id
 * @property string|null $target_role_code
 * @property NotificationType $type
 * @property string $title
 * @property string|null $body
 * @property array<string, mixed>|null $payload
 * @property CarbonInterface|null $expires_at
 * @property CarbonInterface|null $created_at
 * @property-read Branch|null $branch
 * @property-read User|null $targetUser
 * @property-read Collection<int, NotificationRead> $reads
 */
class Notification extends Model
{
    public const UPDATED_AT = null;

    protected $table = ConstantHelper::TABLE_NOTIFICATIONS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'payload' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(NotificationRead::class, 'notification_id');
    }
}
