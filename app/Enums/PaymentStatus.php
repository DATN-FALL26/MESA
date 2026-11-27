<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    use HasEnumHelper;

    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Chờ thanh toán',
            self::SUCCESS => 'Thành công',
            self::FAILED => 'Thất bại',
            self::REFUNDED => 'Đã hoàn tiền',
        };
    }
}
