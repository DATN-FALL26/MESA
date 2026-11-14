<?php

declare(strict_types=1);

namespace App\Services\Payment;

use Illuminate\Support\Str;

/**
 * MOD04 – Tạo chuỗi VietQR (chuẩn EMVCo / NAPAS 247) có sẵn số tiền và nội dung chuyển khoản.
 * Khách quét bằng app ngân hàng sẽ tự điền tài khoản nhận, số tiền và nội dung.
 */
class VietQrPayload
{
    private const NAPAS_GUID = 'A000000727';

    private const SERVICE_TRANSFER_TO_ACCOUNT = 'QRIBFTTA';

    public static function isConfigured(): bool
    {
        return filled(config('payment.bank.bin')) && filled(config('payment.bank.account_no'));
    }

    public static function make(int $amount, string $transferNote): string
    {
        $beneficiary = self::field('00', (string) config('payment.bank.bin'))
            .self::field('01', (string) config('payment.bank.account_no'));

        $merchantAccount = self::field('00', self::NAPAS_GUID)
            .self::field('01', $beneficiary)
            .self::field('02', self::SERVICE_TRANSFER_TO_ACCOUNT);

        $payload = self::field('00', '01')
            .self::field('01', '12') // mã động: dùng cho một lần thanh toán
            .self::field('38', $merchantAccount)
            .self::field('53', '704') // VND
            .self::field('54', (string) $amount)
            .self::field('58', 'VN')
            .self::field('62', self::field('08', self::cleanNote($transferNote)))
            .'6304';

        return $payload.self::crc16($payload);
    }

    /**
     * Nội dung chuyển khoản: không dấu, chỉ chữ số/chữ cái/khoảng trắng, tối đa 25 ký tự.
     */
    public static function cleanNote(string $note): string
    {
        $note = preg_replace('/[^A-Za-z0-9 ]/', ' ', Str::ascii($note)) ?? '';

        return Str::limit(trim((string) preg_replace('/\s+/', ' ', $note)), 25, '');
    }

    private static function field(string $id, string $value): string
    {
        return $id.str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT).$value;
    }

    /**
     * CRC16-CCITT (khởi tạo 0xFFFF, đa thức 0x1021) theo chuẩn EMVCo.
     */
    private static function crc16(string $data): string
    {
        $crc = 0xFFFF;

        foreach (str_split($data) as $char) {
            $crc ^= ord($char) << 8;

            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 0x8000) !== 0 ? (($crc << 1) ^ 0x1021) & 0xFFFF : ($crc << 1) & 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
