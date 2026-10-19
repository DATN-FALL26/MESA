<?php

declare(strict_types=1);

namespace App\Enums;

enum ServeMode: string
{
    use HasEnumHelper;

    case TOGETHER = 'together';
    case AS_READY = 'as_ready';

    public function label(): string
    {
        return match ($this) {
            self::TOGETHER => 'Phục vụ cùng lúc',
            self::AS_READY => 'Phục vụ khi sẵn sàng',
        };
    }
}
