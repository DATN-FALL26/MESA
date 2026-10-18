<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    use HasEnumHelper;

    case CASH = 'cash';
    case QR = 'qr';
    case CARD = 'card';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Tiền mặt',
            self::QR => 'QR Code',
            self::CARD => 'Thẻ',
        };
    }
}
