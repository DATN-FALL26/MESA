<?php

declare(strict_types=1);

namespace App\Enums;

enum PrintJobStatus: string
{
    use HasEnumHelper;

    case QUEUED = 'queued';
    case PRINTED = 'printed';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::QUEUED => 'Chờ in',
            self::PRINTED => 'Đã in',
            self::FAILED => 'Lỗi',
        };
    }
}
