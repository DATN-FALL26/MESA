<?php

declare(strict_types=1);

namespace App\Enums;

enum AdjustmentStatus: string
{
    use HasEnumHelper;

    case DRAFT = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Bản nháp',
            self::PENDING_APPROVAL => 'Chờ duyệt',
            self::APPROVED => 'Đã duyệt',
            self::REJECTED => 'Từ chối',
        };
    }
}
