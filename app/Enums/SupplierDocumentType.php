<?php

declare(strict_types=1);

namespace App\Enums;

enum SupplierDocumentType: string
{
    use HasEnumHelper;

    case FOOD_SAFETY_CERT = 'food_safety_cert';
    case BUSINESS_LICENSE = 'business_license';
    case PRODUCT_DECLARATION = 'product_declaration';
    case TEST_REPORT = 'test_report';
    case QUARANTINE_CERT = 'quarantine_cert';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FOOD_SAFETY_CERT => 'Chứng nhận ATVSTP / HACCP / ISO 22000',
            self::BUSINESS_LICENSE => 'Giấy phép đăng ký kinh doanh',
            self::PRODUCT_DECLARATION => 'Bản tự công bố sản phẩm',
            self::TEST_REPORT => 'Phiếu kiểm nghiệm định kỳ (COA)',
            self::QUARANTINE_CERT => 'Giấy kiểm dịch động / thực vật',
            self::OTHER => 'Hồ sơ pháp lý / Cam kết ATTP khác',
        };
    }
}
