<?php

declare(strict_types=1);

namespace App\Enums;

enum AiRunStatus: string
{
    use HasEnumHelper;

    case QUEUED = 'queued';
    case RUNNING = 'running';
    case DONE = 'done';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::QUEUED => 'Chờ xử lý',
            self::RUNNING => 'Đang chạy',
            self::DONE => 'Hoàn thành',
            self::FAILED => 'Lỗi',
        };
    }
}
