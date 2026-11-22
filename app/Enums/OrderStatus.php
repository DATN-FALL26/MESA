<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    use HasEnumHelper;

    case DRAFT = 'draft';
    case SENT = 'sent';
    case IN_PROGRESS = 'in_progress';
    case SERVED = 'served';
    case PAYMENT_REQUESTED = 'payment_requested';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Bản nháp',
            self::SENT => 'Đã gửi bếp',
            self::IN_PROGRESS => 'Đang chế biến',
            self::SERVED => 'Đã phục vụ',
            self::PAYMENT_REQUESTED => 'Yêu cầu thanh toán',
            self::COMPLETED => 'Hoàn thành',
            self::CANCELLED => 'Đã hủy',
        };
    }
}
