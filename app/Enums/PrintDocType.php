<?php

declare(strict_types=1);

namespace App\Enums;

enum PrintDocType: string
{
    use HasEnumHelper;

    case KITCHEN_TICKET = 'kitchen_ticket';
    case INVOICE = 'invoice';
    case PAYMENT_REQUEST = 'payment_request';

    public function label(): string
    {
        return match ($this) {
            self::KITCHEN_TICKET => 'Phiếu bếp',
            self::INVOICE => 'Hóa đơn',
            self::PAYMENT_REQUEST => 'Yêu cầu thanh toán',
        };
    }
}
