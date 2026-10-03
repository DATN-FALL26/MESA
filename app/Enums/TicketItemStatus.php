<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketItemStatus: string
{
    use HasEnumHelper;

    case NEW = 'new';
    case SCHEDULED = 'scheduled';
    case PREPARING = 'preparing';
    case READY = 'ready';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Mới',
            self::SCHEDULED => 'Đã lên lịch nấu',
            self::PREPARING => 'Đang chế biến',
            self::READY => 'Sẵn sàng',
            self::CANCELLED => 'Đã hủy',
        };
    }
}
