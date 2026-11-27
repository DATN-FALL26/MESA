<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderChannel: string
{
    use HasEnumHelper;

    case DINE_IN = 'dine_in';
    case TAKEAWAY = 'takeaway';

    public function label(): string
    {
        return match ($this) {
            self::DINE_IN => 'Tại bàn',
            self::TAKEAWAY => 'Mang đi',
        };
    }
}
