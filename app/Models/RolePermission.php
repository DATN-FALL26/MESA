<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\ConstantHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $role_id
 * @property int $permission_id
 * @property-read Role $role
 * @property-read Permission $permission
 */
class RolePermission extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $table = ConstantHelper::TABLE_ROLE_PERMISSIONS;

    protected $guarded = [];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }
}
