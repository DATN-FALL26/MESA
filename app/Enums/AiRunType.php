<?php

declare(strict_types=1);

namespace App\Enums;

enum AiRunType: string
{
    use HasEnumHelper;

    case SALES_ANALYSIS = 'sales_analysis';
    case DEMAND_FORECAST = 'demand_forecast';
    case SHORTAGE_DETECTION = 'shortage_detection';

    public function label(): string
    {
        return match ($this) {
            self::SALES_ANALYSIS => 'Phân tích doanh số',
            self::DEMAND_FORECAST => 'Dự báo nhu cầu',
            self::SHORTAGE_DETECTION => 'Phát hiện thiếu hàng',
        };
    }
}
