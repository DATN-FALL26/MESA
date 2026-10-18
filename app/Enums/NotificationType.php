<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationType: string
{
    use HasEnumHelper;

    case BATCH_ALL_READY = 'batch_all_ready';
    case PAYMENT_REQUEST = 'payment_request';
    case ORDER_CANCELLED = 'order_cancelled';
    case LOW_STOCK = 'low_stock';
    case APPROVAL_REQUEST = 'approval_request';
    case AI_ALERT = 'ai_alert';
    case SYSTEM = 'system';

    public function label(): string
    {
        return match ($this) {
            self::BATCH_ALL_READY => 'Đợt món đã sẵn sàng',
            self::PAYMENT_REQUEST => 'Yêu cầu thanh toán',
            self::ORDER_CANCELLED => 'Đơn bị hủy',
            self::LOW_STOCK => 'Cảnh báo tồn kho thấp',
            self::APPROVAL_REQUEST => 'Yêu cầu phê duyệt',
            self::AI_ALERT => 'Cảnh báo từ AI',
            self::SYSTEM => 'Thông báo hệ thống',
        };
    }
}
