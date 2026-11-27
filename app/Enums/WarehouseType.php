<?php

declare(strict_types=1);

namespace App\Enums;

enum WarehouseType: string
{
    use HasEnumHelper;

    case BRANCH = 'branch';
    case CENTRAL = 'central';

    public function label(): string
    {
        return match ($this) {
            self::BRANCH => 'Kho chi nhánh',
            self::CENTRAL => 'Kho trung tâm',
        };
    }
}
