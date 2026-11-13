<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderItemStatus: string
{
    use HasEnumHelper;

    case PENDING = 'pending';
    case SENT = 'sent';
    case PREPARING = 'preparing';
    case READY = 'ready';
    case SERVED = 'served';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Chờ gửi bếp',
            self::SENT => 'Đã gửi bếp',
            self::PREPARING => 'Đang chế biến',
            self::READY => 'Sẵn sàng phục vụ',
            self::SERVED => 'Đã phục vụ',
            self::CANCELLED => 'Đã hủy',
        };
    }
}
