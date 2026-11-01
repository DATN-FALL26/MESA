<?php

declare(strict_types=1);

namespace App\Enums;

enum AlertSeverity: string
{
    use HasEnumHelper;

    case INFO = 'info';
    case WARNING = 'warning';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::INFO => 'Thông tin',
            self::WARNING => 'Cảnh báo',
            self::CRITICAL => 'Nghiêm trọng',
        };
    }
}
