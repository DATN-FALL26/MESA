<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatus: string
{
    use HasEnumHelper;

    case ACTIVE = 'active';
    case LOCKED = 'locked';
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Đang hoạt động',
            self::LOCKED => 'Đã khóa',
            self::INACTIVE => 'Ngừng hoạt động',
        };
    }
}
