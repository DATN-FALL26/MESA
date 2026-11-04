<?php

declare(strict_types=1);

namespace App\Enums;

enum TableShape: string
{
    use HasEnumHelper;

    case SQUARE = 'square';
    case ROUND = 'round';
    case RECT = 'rect';

    public function label(): string
    {
        return match ($this) {
            self::SQUARE => 'Vuông',
            self::ROUND => 'Tròn',
            self::RECT => 'Chữ nhật',
        };
    }
}
