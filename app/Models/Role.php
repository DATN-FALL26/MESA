<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property bool $is_system
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Collection<int, Permission> $permissions
 * @property-read Collection<int, RolePermission> $rolePermissions
 * @property-read Collection<int, UserRole> $userRoles
 * @property-read Collection<int, User> $users
 */
class Role extends Model
{
    protected $table = ConstantHelper::TABLE_ROLES;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            ConstantHelper::TABLE_ROLE_PERMISSIONS,
            'role_id',
            'permission_id'
        );
    }

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RolePermission::class, 'role_id');
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'role_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            ConstantHelper::TABLE_USER_ROLES,
            'role_id',
            'user_id'
        );
    }
}
