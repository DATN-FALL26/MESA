<?php

declare(strict_types=1);

namespace App\Enums;

enum MovementType: string
{
    use HasEnumHelper;

    case PURCHASE_IN = 'purchase_in';
    case SALE_OUT = 'sale_out';
    case SALE_RETURN = 'sale_return';
    case ADJUST = 'adjust';
    case WASTE = 'waste';

    public function label(): string
    {
        return match ($this) {
            self::PURCHASE_IN => 'Nhập mua hàng',
            self::SALE_OUT => 'Xuất bán hàng',
            self::SALE_RETURN => 'Trả hàng bán',
            self::ADJUST => 'Điều chỉnh kiểm kê',
            self::WASTE => 'Hao hụt / Hủy',
        };
    }
}
