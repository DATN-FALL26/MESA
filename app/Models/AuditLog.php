<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $branch_id
 * @property int|null $user_id
 * @property string $action
 * @property string $entity_type
 * @property int|null $entity_id
 * @property array<string, mixed>|null $old_value
 * @property array<string, mixed>|null $new_value
 * @property string|null $ip_address
 * @property CarbonInterface|null $created_at
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = ConstantHelper::TABLE_AUDIT_LOGS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_value' => 'array',
            'new_value' => 'array',
        ];
    }
}
