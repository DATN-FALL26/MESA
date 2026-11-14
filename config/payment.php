<?php

declare(strict_types=1);

/*
 * MOD04 – Thanh toán.
 * Tài khoản nhận chuyển khoản dùng để tạo mã VietQR (khai báo trong .env, không ghi vào code).
 * Bỏ trống số tài khoản thì màn QR quay về chế độ mô phỏng.
 */
return [
    'bank' => [
        // Mã BIN ngân hàng theo NAPAS (MB Bank = 970422).
        'bin' => env('PAYMENT_BANK_BIN', ''),
        'name' => env('PAYMENT_BANK_NAME', ''),
        'account_no' => env('PAYMENT_BANK_ACCOUNT', ''),
        'account_name' => env('PAYMENT_BANK_ACCOUNT_NAME', ''),
    ],
];
