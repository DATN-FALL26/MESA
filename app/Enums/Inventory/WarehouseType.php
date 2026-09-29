<?php

namespace App\Enums\Inventory;

enum WarehouseType: string
{
    case CENTRAL = 'central';
    case STORE = 'store';

    public function label(): string
    {
        return match ($this) {
            self::CENTRAL => 'Kho trung tâm',
            self::STORE => 'Kho cửa hàng',
        };
    }
}
