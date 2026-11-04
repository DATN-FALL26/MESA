<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\ConstantHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $module
 * @property string $name
 * @property string|null $description
 * @property-read Collection<int, Role> $roles
 * @property-read Collection<int, RolePermission> $rolePermissions
 */
class Permission extends Model
{
    public $timestamps = false;

    protected $table = ConstantHelper::TABLE_PERMISSIONS;

    protected $guarded = [];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            ConstantHelper::TABLE_ROLE_PERMISSIONS,
            'permission_id',
            'role_id'
        );
    }

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RolePermission::class, 'permission_id');
    }
}
