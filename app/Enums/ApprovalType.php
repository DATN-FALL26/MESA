<?php

declare(strict_types=1);

namespace App\Enums;

enum ApprovalType: string
{
    use HasEnumHelper;

    case PURCHASE_ORDER = 'purchase_order';
    case STOCK_ADJUSTMENT = 'stock_adjustment';
    case AI_SUGGESTION = 'ai_suggestion';

    public function label(): string
    {
        return match ($this) {
            self::PURCHASE_ORDER => 'Đơn nhập hàng',
            self::STOCK_ADJUSTMENT => 'Phiếu điều chỉnh kho',
            self::AI_SUGGESTION => 'Đề xuất AI',
        };
    }
}
