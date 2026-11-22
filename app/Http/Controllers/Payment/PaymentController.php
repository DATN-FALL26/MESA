<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payment;

use App\Enums\PaymentMethod;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Kitchen\Concerns\ResolvesBranchScope;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\Payment\OrderPaymentService;
use App\Services\Payment\VietQrPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Thanh toán – MOD04: INV-19 (yêu cầu thanh toán), INV-20 (tiền mặt / QR mô phỏng), INV-22 (trạng thái giao dịch).
 * Khách trả tiền trước khi ăn; gọi thêm thì trả thêm phần còn thiếu.
 */
class PaymentController extends Controller
{
    use ResolvesBranchScope;

    public function __construct(private OrderPaymentService $payments) {}

    /**
     * Bước 1 – Yêu cầu thanh toán: hóa đơn tạm tính, đã trả / còn phải trả, chọn phương thức.
     */
    public function show(Request $request, Order $order): View
    {
        $this->authorizeOrder($request, $order, ConstantHelper::PERM_PAYMENT_REQUEST);

        return view('payments.show', [
            ...$this->pageData($order),
            'payments' => $order->payments()->latest('id')->get(),
            'pendingQr' => $this->payments->pendingQr($order),
        ]);
    }

    /**
     * Chọn phương thức và sang bước 2.
     */
    public function start(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($request, $order, ConstantHelper::PERM_PAYMENT_REQUEST);

        $validated = $request->validate([
            'method' => ['required', Rule::in([PaymentMethod::CASH->value, PaymentMethod::QR->value])],
        ]);

        if ($validated['method'] === PaymentMethod::CASH->value) {
            return redirect()->route('payments.cash', $order);
        }

        $payment = $this->payments->startQr($order, $this->transferNote($order));

        return redirect()->route('payments.qr', $payment);
    }

    /**
     * Bước 2b – Thanh toán tiền mặt.
     */
    public function cash(Request $request, Order $order): View|RedirectResponse
    {
        $this->authorizeOrder($request, $order, ConstantHelper::PERM_PAYMENT_REQUEST);

        $data = $this->pageData($order);

        if ($data['summary']['remaining'] <= 0) {
            return redirect()->route('payments.show', $order)->with('success', 'Order này đã được thanh toán đủ.');
        }

        return view('payments.cash', $data);
    }

    public function payCash(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($request, $order, ConstantHelper::PERM_PAYMENT_REQUEST);

        $validated = $request->validate([
            'tendered_amount' => ['required', 'integer', 'min:0', 'max:999999999'],
        ]);

        $payment = $this->payments->payCash($order, (int) $validated['tendered_amount'], $request->user());

        return redirect()->route('payments.show', $order)->with('success', sprintf(
            'Đã thanh toán %sđ bằng tiền mặt. Tiền thừa trả khách: %sđ.',
            number_format((int) $payment->amount, 0, ',', '.'),
            number_format((int) $payment->change_amount, 0, ',', '.'),
        ));
    }

    /**
     * Bước 2a – Thanh toán QR / chuyển khoản (mô phỏng).
     */
    public function qr(Request $request, Payment $payment): View|RedirectResponse
    {
        $order = $payment->order;
        $this->authorizeOrder($request, $order, ConstantHelper::PERM_PAYMENT_REQUEST);

        if ($payment->method !== PaymentMethod::QR) {
            return redirect()->route('payments.show', $order);
        }

        if ($this->payments->isExpired($payment)) {
            $this->payments->failQr($payment, 'expired');
            $payment->refresh();
        }

        $bankQr = VietQrPayload::isConfigured()
            ? VietQrPayload::make((int) $payment->amount, (string) ($payment->gateway_payload['transfer_note'] ?? ''))
            : null;

        return view('payments.qr', [
            ...$this->pageData($order),
            'payment' => $payment,
            'statusLabel' => OrderPaymentService::statusLabel($payment),
            'bankQr' => $bankQr,
            'bank' => config('payment.bank'),
        ]);
    }

    public function confirmQr(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorizeOrder($request, $payment->order, ConstantHelper::PERM_PAYMENT_REQUEST);

        $payment = $this->payments->confirmQr($payment, $request->user());

        return redirect()->route('payments.show', $payment->order_id)->with('success', sprintf(
            'Đã thanh toán %sđ qua QR / chuyển khoản.',
            number_format((int) $payment->amount, 0, ',', '.'),
        ));
    }

    public function failQr(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorizeOrder($request, $payment->order, ConstantHelper::PERM_PAYMENT_REQUEST);

        $this->payments->failQr($payment, 'Nhân viên báo giao dịch thất bại');

        return redirect()->route('payments.qr', $payment);
    }

    /**
     * @return array{order: Order, place: string, sessionCode: string, summary: array{items: Collection<int, OrderItem>, unservedItems: Collection<int, OrderItem>, total: int, paid: int, remaining: int}, canCollect: bool}
     */
    private function pageData(Order $order): array
    {
        $order->loadMissing(['session.sessionTables' => fn ($query) => $query->whereNull('left_at')->with('diningTable')]);

        return [
            'order' => $order,
            'place' => $this->placeLabel($order),
            'sessionCode' => $order->session_id ? '#'.str_pad((string) $order->session_id, 4, '0', STR_PAD_LEFT) : '',
            'summary' => $this->payments->summary($order),
            'canCollect' => auth()->user()->hasPermission(ConstantHelper::PERM_PAYMENT_REQUEST),
        ];
    }

    private function authorizeOrder(Request $request, Order $order, string $permission): void
    {
        abort_unless($request->user()->hasPermission($permission), 403);
        $this->abortUnlessBranchAccessible($this->accessibleBranchIds($request->user(), $permission), (int) $order->branch_id);
    }

    private function transferNote(Order $order): string
    {
        $tableCodes = $order->session?->sessionTables
            ->map(fn ($sessionTable) => $sessionTable->diningTable?->code)
            ->filter()
            ->join(' ');

        return VietQrPayload::cleanNote('MESA '.$tableCodes.' '.str_pad((string) $order->getKey(), 4, '0', STR_PAD_LEFT));
    }

    private function placeLabel(Order $order): string
    {
        $tableCodes = $order->session?->sessionTables
            ->map(fn ($sessionTable) => $sessionTable->diningTable?->code)
            ->filter()
            ->join(', ');

        return filled($tableCodes) ? 'Bàn '.$tableCodes : 'Mang về';
    }
}
