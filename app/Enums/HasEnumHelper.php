<?php

declare(strict_types=1);

namespace App\Enums;

trait HasEnumHelper
{
    /**
     * Lấy danh sách toàn bộ các giá trị của Enum.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Lấy danh sách options dạng key-value [{value, label}].
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            self::cases()
        );
    }
}
