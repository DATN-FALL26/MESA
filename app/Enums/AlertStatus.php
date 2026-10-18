<?php

declare(strict_types=1);

namespace App\Enums;

enum AlertStatus: string
{
    use HasEnumHelper;

    case NEW = 'new';
    case ACKNOWLEDGED = 'acknowledged';
    case RESOLVED = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Mới',
            self::ACKNOWLEDGED => 'Đã xác nhận',
            self::RESOLVED => 'Đã xử lý',
        };
    }
}
