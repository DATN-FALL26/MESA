<?php

declare(strict_types=1);

namespace App\Enums;

enum AlertType: string
{
    use HasEnumHelper;

    case LOW_STOCK_RISK = 'low_stock_risk';
    case SALES_ANOMALY = 'sales_anomaly';

    public function label(): string
    {
        return match ($this) {
            self::LOW_STOCK_RISK => 'Nguy cơ thiếu hàng',
            self::SALES_ANOMALY => 'Bất thường doanh số',
        };
    }
}
