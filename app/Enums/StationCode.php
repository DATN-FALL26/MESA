<?php

declare(strict_types=1);

namespace App\Enums;

enum StationCode: string
{
    use HasEnumHelper;

    case PHO = 'PHO';
    case DRINK = 'DRINK';
    case SIDE = 'SIDE';

    public function label(): string
    {
        return match ($this) {
            self::PHO => 'Quầy phở',
            self::DRINK => 'Quầy đồ uống',
            self::SIDE => 'Quầy món phụ',
        };
    }
}
