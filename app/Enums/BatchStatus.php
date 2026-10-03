<?php

declare(strict_types=1);

namespace App\Enums;

enum BatchStatus: string
{
    use HasEnumHelper;

    case OPEN = 'open';
    case SENT = 'sent';
    case COOKING = 'cooking';
    case PARTIALLY_READY = 'partially_ready';
    case ALL_READY = 'all_ready';
    case SERVED = 'served';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Đang mở (chưa gửi)',
            self::SENT => 'Đã gửi bếp',
            self::COOKING => 'Đang chế biến',
            self::PARTIALLY_READY => 'Một phần sẵn sàng',
            self::ALL_READY => 'Tất cả sẵn sàng',
            self::SERVED => 'Đã phục vụ',
            self::CANCELLED => 'Đã hủy',
        };
    }
}
