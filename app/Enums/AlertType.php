<?php

declare(strict_types=1);

namespace App\Enums;

enum AlertType: string
{
    use HasEnumHelper;

    case LOW_STOCK_RISK = 'low_stock_risk';
    case OUT_OF_STOCK = 'out_of_stock';
    case EXPIRING_SOON = 'expiring_soon';
    case EXPIRED = 'expired';
    case SALES_ANOMALY = 'sales_anomaly';

    public function label(): string
    {
        return match ($this) {
            self::LOW_STOCK_RISK => 'Nguy cơ thiếu hàng',
            self::OUT_OF_STOCK => 'Đã hết hàng',
            self::EXPIRING_SOON => 'Sắp hết hạn sử dụng',
            self::EXPIRED => 'Đã quá hạn sử dụng',
            self::SALES_ANOMALY => 'Bất thường doanh số',
        };
    }
}
