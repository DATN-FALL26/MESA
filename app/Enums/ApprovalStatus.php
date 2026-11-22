<?php

declare(strict_types=1);

namespace App\Enums;

enum ApprovalStatus: string
{
    use HasEnumHelper;

    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Chờ duyệt',
            self::APPROVED => 'Đã duyệt',
            self::REJECTED => 'Từ chối',
        };
    }
}
