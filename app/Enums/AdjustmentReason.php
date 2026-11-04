<?php

declare(strict_types=1);

namespace App\Enums;

enum AdjustmentReason: string
{
    use HasEnumHelper;

    case STOCKTAKE = 'stocktake';
    case WASTE = 'waste';
    case DAMAGE = 'damage';
    case CORRECTION = 'correction';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::STOCKTAKE => 'Kiểm kê định kỳ',
            self::WASTE => 'Hao hụt',
            self::DAMAGE => 'Hư hỏng',
            self::CORRECTION => 'Sửa sai',
            self::OTHER => 'Lý do khác',
        };
    }
}
