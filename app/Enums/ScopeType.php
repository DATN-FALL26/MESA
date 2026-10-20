<?php

declare(strict_types=1);

namespace App\Enums;

enum ScopeType: string
{
    use HasEnumHelper;

    case ALL = 'ALL';
    case BRANCH = 'BRANCH';

    public function label(): string
    {
        return match ($this) {
            self::ALL => 'Toàn hệ thống',
            self::BRANCH => 'Chi nhánh',
        };
    }
}
