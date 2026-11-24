<?php

declare(strict_types=1);

namespace App\Enums;

enum StockBatchStatus: string
{
    use HasEnumHelper;

    case ACTIVE = 'active';       // Lô hàng đang sử dụng và còn tồn
    case DEPLETED = 'depleted';   // Đã sử dụng hết số lượng (tồn = 0)
    case EXPIRED = 'expired';     // Đã quá hạn sử dụng
    case DISPOSED = 'disposed';   // Đã xuất hủy / tiêu hủy do hỏng

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Đang sử dụng',
            self::DEPLETED => 'Đã hết hàng',
            self::EXPIRED => 'Đã hết hạn',
            self::DISPOSED => 'Đã xuất hủy',
        };
    }
}
