<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Kitchen\Concerns\ResolvesBranchScope;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * MOD04 – INV-23: lịch sử giao dịch cho Quản lý và số liệu phương thức thanh toán cho Dashboard (MOD09).
 */
class PaymentHistoryService
{
    use ResolvesBranchScope;

    public const PER_PAGE = 15;

    /**
     * Chỉ Quản lý (Admin / Quản lý chi nhánh) được xem lịch sử giao dịch.
     */
    public static function canView(?User $user): bool
    {
        return $user !== null
            && $user->hasPermission(ConstantHelper::PERM_REPORT_VIEW)
            && $user->hasPermission(ConstantHelper::PERM_PAYMENT_CONFIRM);
    }

    /**
     * Tổng tiền thu thành công theo phương thức trong khoảng thời gian, chỉ trong chi nhánh người dùng được xem.
     *
     * @return array{cash: int, transfer: int}
     */
    public function methodTotals(User $user, string $permission, CarbonInterface $from, CarbonInterface $to): array
    {
        $totals = $this->scoped($user, $permission)
            ->where('status', PaymentStatus::SUCCESS)
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw('method, SUM(amount) as total')
            ->groupBy('method')
            ->pluck('total', 'method');

        return [
            'cash' => (int) ($totals[PaymentMethod::CASH->value] ?? 0),
            'transfer' => (int) ($totals[PaymentMethod::QR->value] ?? 0),
        ];
    }

    /**
     * @return array{payments: LengthAwarePaginator<int, Payment>, totals: array{cash: int, transfer: int}, successCount: int}
     */
    public function history(User $user, string $fromDate, string $toDate, ?string $method, ?string $status): array
    {
        $from = $this->parseDate($fromDate, now()->subDays(7))->startOfDay();
        $to = $this->parseDate($toDate, now())->endOfDay();

        $query = $this->scoped($user, ConstantHelper::PERM_REPORT_VIEW)
            ->whereBetween('created_at', [$from, $to])
            ->when(PaymentMethod::tryFrom((string) $method), fn (Builder $q, PaymentMethod $m) => $q->where('method', $m))
            ->when(PaymentStatus::tryFrom((string) $status), fn (Builder $q, PaymentStatus $s) => $q->where('status', $s));

        return [
            'payments' => (clone $query)
                ->with(['order.session.sessionTables.diningTable', 'confirmer'])
                ->latest('id')
                ->paginate(self::PER_PAGE, pageName: 'payments_page')
                ->withQueryString(),
            'totals' => $this->methodTotals($user, ConstantHelper::PERM_REPORT_VIEW, $from, $to),
            'successCount' => (clone $query)->where('status', PaymentStatus::SUCCESS)->count(),
        ];
    }

    /**
     * Bàn của order (vd "B05, B06"), lấy từ lượt dùng bữa.
     */
    public static function tableLabel(Payment $payment): string
    {
        return $payment->order?->session?->sessionTables
            ->map(fn ($sessionTable) => $sessionTable->diningTable?->code)
            ->filter()
            ->unique()
            ->join(', ') ?: '—';
    }

    /**
     * @return Builder<Payment>
     */
    private function scoped(User $user, string $permission): Builder
    {
        $branchIds = $this->accessibleBranchIds($user, $permission);

        return Payment::query()->when($branchIds !== null, fn (Builder $q) => $q->whereIn('branch_id', $branchIds));
    }

    private function parseDate(string $value, CarbonInterface $fallback): Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return Carbon::parse($fallback);
        }
    }
}
