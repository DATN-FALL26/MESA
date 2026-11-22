<?php

declare(strict_types=1);

namespace App\Enums;

enum PrinterType: string
{
    use HasEnumHelper;

    case KITCHEN = 'kitchen';
    case RECEIPT = 'receipt';

    public function label(): string
    {
        return match ($this) {
            self::KITCHEN => 'Máy in bếp',
            self::RECEIPT => 'Máy in hóa đơn',
        };
    }
}
