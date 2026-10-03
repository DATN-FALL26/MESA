<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    use HasEnumHelper;

    case ISSUED = 'issued';
    case VOID = 'void';

    public function label(): string
    {
        return match ($this) {
            self::ISSUED => 'Đã xuất',
            self::VOID => 'Đã hủy',
        };
    }
}
