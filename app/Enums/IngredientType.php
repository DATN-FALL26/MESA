<?php

declare(strict_types=1);

namespace App\Enums;

enum IngredientType: string
{
    use HasEnumHelper;

    case RAW = 'raw';
    case SEMI_FINISHED = 'semi_finished';

    public function label(): string
    {
        return match ($this) {
            self::RAW => 'Nguyên liệu thô',
            self::SEMI_FINISHED => 'Bán thành phẩm',
        };
    }
}
