<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $notification_id
 * @property int $user_id
 * @property CarbonInterface $read_at
 * @property-read Notification $notification
 * @property-read User $user
 */
class NotificationRead extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $table = ConstantHelper::TABLE_NOTIFICATION_READS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class, 'notification_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
