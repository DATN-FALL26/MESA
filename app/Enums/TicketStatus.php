<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketStatus: string
{
    use HasEnumHelper;

    case NEW = 'new';
    case PREPARING = 'preparing';
    case READY = 'ready';
    case DONE = 'done';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Mới',
            self::PREPARING => 'Đang chế biến',
            self::READY => 'Sẵn sàng',
            self::DONE => 'Hoàn thành',
            self::CANCELLED => 'Đã hủy',
        };
    }
}
