<?php

declare(strict_types=1);

namespace App\Enums;

enum SessionStatus: string
{
    use HasEnumHelper;

    case OPEN = 'open';
    case CLOSED = 'closed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Đang mở',
            self::CLOSED => 'Đã đóng',
            self::CANCELLED => 'Đã hủy',
        };
    }
}
