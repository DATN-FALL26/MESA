<?php

declare(strict_types=1);

namespace App\Enums;

enum BranchStatus: string
{
    use HasEnumHelper;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Đang hoạt động',
            self::INACTIVE => 'Ngừng hoạt động',
        };
    }
}
