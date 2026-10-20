<?php

declare(strict_types=1);

namespace App\Enums;

enum SequenceResetPolicy: string
{
    use HasEnumHelper;

    case NEVER = 'never';
    case DAILY = 'daily';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::NEVER => 'Không reset',
            self::DAILY => 'Reset hàng ngày',
            self::MONTHLY => 'Reset hàng tháng',
            self::YEARLY => 'Reset hàng năm',
        };
    }
}
