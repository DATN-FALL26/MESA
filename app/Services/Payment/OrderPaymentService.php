<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\OrderItemStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * MOD04 – Thanh toán (INV-19, INV-20, INV-22).
 *
 * Quán thu tiền trước khi ăn: mỗi lần khách trả là một giao dịch (payments).
 * Khách gọi thêm sau khi đã trả thì chỉ thu phần còn thiếu: còn phải trả = tổng món đã gửi bếp − tổng đã trả.
 * Order không bị khóa, không đóng phiên / trả bàn.
 */
class OrderPaymentService
{
    /**
     * Mã QR mô phỏng hết hạn sau số phút này.
     */
    public const QR_EXPIRES_MINUTES = 5;

    /**
     * Món tính tiền (đã gửi bếp, chưa hủy), tổng tiền, đã trả và còn phải trả của order.
     *
     * @return array{items: Collection<int, OrderItem>, unservedItems: Collection<int, OrderItem>, total: int, paid: int, remaining: int}
     */
    public function summary(Order $order): array
    {
        $items = $order->items()
            ->whereNotIn('status', [OrderItemStatus::PENDING, OrderItemStatus::CANCELLED])
            ->with('modifiers')
            ->orderBy('id')
            ->get();

        $total = max(0, (int) $items->sum('line_total') - (int) $order->discount_amount);
        $paid = $this->paidAmount($order);

        return [
            'items' => $items,
            'unservedItems' => $items->reject(fn (OrderItem $orderItem): bool => $orderItem->status === OrderItemStatus::SERVED),
            'total' => $total,
            'paid' => $paid,
            'remaining' => max(0, $total - $paid),
        ];
    }

    /**
     * INV-20: thu tiền mặt cho phần còn phải trả.
     */
    public function payCash(Order $order, int $tenderedAmount, User $cashier): Payment
    {
        return DB::transaction(function () use ($order, $tenderedAmount, $cashier): Payment {
            $remaining = $this->lockAndGetRemaining($order);

            if ($tenderedAmount < $remaining) {
                throw ValidationException::withMessages([
                    'tendered_amount' => 'Tiền khách đưa chưa đủ. Còn thiếu '.number_format($remaining - $tenderedAmount, 0, ',', '.').'đ.',
                ]);
            }

            $this->cancelPendingQr($order, 'Đổi sang thanh toán tiền mặt');

            return Payment::query()->create([
                'branch_id' => $order->branch_id,
                'order_id' => $order->getKey(),
                'method' => PaymentMethod::CASH,
                'amount' => $remaining,
                'tendered_amount' => $tenderedAmount,
                'change_amount' => $tenderedAmount - $remaining,
                'status' => PaymentStatus::SUCCESS,
                'confirmed_by' => $cashier->getKey(),
                'paid_at' => now(),
            ]);
        });
    }

    /**
     * INV-20 / INV-22: tạo (hoặc dùng lại) giao dịch QR đang chờ cho phần còn phải trả.
     */
    public function startQr(Order $order, string $transferNote): Payment
    {
        return DB::transaction(function () use ($order, $transferNote): Payment {
            $remaining = $this->lockAndGetRemaining($order);

            $pending = $this->pendingQr($order);

            if ($pending !== null && ! $this->isExpired($pending) && (int) $pending->amount === $remaining) {
                return $pending;
            }

            if ($pending !== null) {
                $this->markFailed($pending, $this->isExpired($pending) ? 'expired' : 'Số tiền thay đổi, tạo mã mới');
            }

            return Payment::query()->create([
                'branch_id' => $order->branch_id,
                'order_id' => $order->getKey(),
                'method' => PaymentMethod::QR,
                'amount' => $remaining,
                'status' => PaymentStatus::PENDING,
                'gateway_payload' => [
                    'transfer_note' => $transferNote,
                    'expires_at' => now()->addMinutes(self::QR_EXPIRES_MINUTES)->toIso8601String(),
                ],
            ]);
        });
    }

    /**
     * INV-20: nhân viên order xác nhận đã nhận tiền chuyển khoản (mô phỏng).
     */
    public function confirmQr(Payment $payment, User $cashier): Payment
    {
        // Mã đã hết hạn: ghi nhận "Hết hạn" trước (ngoài giao dịch để không bị hoàn tác khi báo lỗi).
        if ($payment->status === PaymentStatus::PENDING && $this->isExpired($payment)) {
            $this->markFailed($payment, 'expired');

            throw ValidationException::withMessages(['payment' => 'Mã QR đã hết hạn. Vui lòng tạo mã mới hoặc đổi phương thức.']);
        }

        if ($payment->status === PaymentStatus::PENDING && (int) $payment->amount !== $this->summary($payment->order)['remaining']) {
            $this->markFailed($payment, 'Số tiền thay đổi, tạo mã mới');

            throw ValidationException::withMessages(['payment' => 'Số tiền cần thanh toán đã thay đổi. Vui lòng tạo mã QR mới.']);
        }

        return DB::transaction(function () use ($payment, $cashier): Payment {
            $remaining = $this->lockAndGetRemaining($payment->order);
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());

            if ($payment->status !== PaymentStatus::PENDING || (int) $payment->amount !== $remaining) {
                throw ValidationException::withMessages(['payment' => 'Giao dịch này đã được xử lý.']);
            }

            $payment->update([
                'status' => PaymentStatus::SUCCESS,
                'gateway_txn_id' => 'MOCK-'.Str::upper(Str::random(12)),
                'confirmed_by' => $cashier->getKey(),
                'paid_at' => now(),
            ]);

            return $payment;
        });
    }

    /**
     * INV-22: ghi nhận giao dịch QR thất bại / hết hạn.
     */
    public function failQr(Payment $payment, string $reason): void
    {
        if ($payment->status === PaymentStatus::PENDING) {
            $this->markFailed($payment, $this->isExpired($payment) ? 'expired' : $reason);
        }
    }

    public function pendingQr(Order $order): ?Payment
    {
        return $order->payments()
            ->where('method', PaymentMethod::QR)
            ->where('status', PaymentStatus::PENDING)
            ->latest('id')
            ->first();
    }

    public function isExpired(Payment $payment): bool
    {
        $expiresAt = $payment->gateway_payload['expires_at'] ?? null;

        return $expiresAt !== null && now()->greaterThanOrEqualTo($expiresAt);
    }

    /**
     * Nhãn trạng thái giao dịch để hiển thị: Đang chờ / Thành công / Thất bại / Hết hạn.
     */
    public static function statusLabel(Payment $payment): string
    {
        return match (true) {
            $payment->status === PaymentStatus::SUCCESS => 'Thành công',
            $payment->status === PaymentStatus::PENDING => 'Đang chờ',
            ($payment->gateway_payload['failure_reason'] ?? null) === 'expired' => 'Hết hạn',
            $payment->status === PaymentStatus::REFUNDED => 'Đã hoàn tiền',
            default => 'Thất bại',
        };
    }

    private function paidAmount(Order $order): int
    {
        return (int) $order->payments()->where('status', PaymentStatus::SUCCESS)->sum('amount');
    }

    /**
     * Khóa order để hai nhân viên không thu trùng, rồi tính lại số còn phải trả.
     */
    private function lockAndGetRemaining(Order $order): int
    {
        Order::query()->lockForUpdate()->findOrFail($order->getKey());
        $remaining = $this->summary($order)['remaining'];

        if ($remaining <= 0) {
            throw ValidationException::withMessages(['payment' => 'Order này đã được thanh toán đủ.']);
        }

        return $remaining;
    }

    private function cancelPendingQr(Order $order, string $reason): void
    {
        $pending = $this->pendingQr($order);

        if ($pending !== null) {
            $this->markFailed($pending, $reason);
        }
    }

    private function markFailed(Payment $payment, string $reason): void
    {
        $payment->update([
            'status' => PaymentStatus::FAILED,
            'gateway_payload' => array_merge($payment->gateway_payload ?? [], [
                'failure_reason' => $reason,
                'failed_at' => now()->toIso8601String(),
            ]),
        ]);
    }
}
