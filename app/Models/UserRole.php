<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ScopeType;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $role_id
 * @property ScopeType $scope_type
 * @property int|null $scope_id Ghi chú đa hình: NULL khi ALL, = branches.id khi BRANCH
 * @property int $scope_key Generated column IFNULL(scope_id, 0)
 * @property CarbonInterface $valid_from
 * @property CarbonInterface|null $valid_to
 * @property int|null $granted_by
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read User $user
 * @property-read Role $role
 * @property-read User|null $granter
 */
class UserRole extends Model
{
    protected $table = ConstantHelper::TABLE_USER_ROLES;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope_type' => ScopeType::class,
            'valid_from' => 'datetime',
            'valid_to' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function granter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
