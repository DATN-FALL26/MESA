<?php

declare(strict_types=1);

namespace App\Enums;

enum PurchaseOrderSource: string
{
    use HasEnumHelper;

    case MANUAL = 'manual';
    case AI_SUGGESTION = 'ai_suggestion';

    public function label(): string
    {
        return match ($this) {
            self::MANUAL => 'Thủ công',
            self::AI_SUGGESTION => 'Đề xuất từ AI',
        };
    }
}
