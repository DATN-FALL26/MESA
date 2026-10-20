<?php

declare(strict_types=1);

namespace App\Enums;

enum TableStatus: string
{
    use HasEnumHelper;

    case AVAILABLE = 'available';
    case OCCUPIED = 'occupied';
    case RESERVED = 'reserved';
    case CLEANING = 'cleaning';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Trống',
            self::OCCUPIED => 'Đang dùng',
            self::RESERVED => 'Đã đặt trước',
            self::CLEANING => 'Đang dọn',
        };
    }
}
