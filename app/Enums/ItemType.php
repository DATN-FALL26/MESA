<?php

declare(strict_types=1);

namespace App\Enums;

enum ItemType: string
{
    use HasEnumHelper;

    case DISH = 'dish';
    case DRINK = 'drink';
    case SIDE = 'side';
    case RETAIL = 'retail';

    public function label(): string
    {
        return match ($this) {
            self::DISH => 'Món ăn',
            self::DRINK => 'Đồ uống',
            self::SIDE => 'Món phụ',
            self::RETAIL => 'Bán lẻ',
        };
    }
}
