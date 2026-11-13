<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payment;

use App\Enums\OrderItemStatus;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Kitchen\Concerns\ResolvesBranchScope;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Thanh toán – MOD04, INV-19: hóa đơn tạm tính của bàn (lượt dùng bữa).
 * Thu tiền (INV-20), in hóa đơn (INV-21), trạng thái giao dịch (INV-22) sẽ làm tiếp trên trang này.
 */
class PaymentController extends Controller
{
    use ResolvesBranchScope;

    public function show(Request $request, Order $order): View
    {
        $branchIds = $this->accessibleBranchIds($request->user(), ConstantHelper::PERM_PAYMENT_REQUEST);
        $this->abortUnlessBranchAccessible($branchIds, (int) $order->branch_id);

        $order->load([
            'session.sessionTables' => fn ($query) => $query->whereNull('left_at')->with('diningTable'),
            'items' => fn ($query) => $query
                ->where('status', '!=', OrderItemStatus::CANCELLED)
                ->orderBy('id')
                ->with('modifiers'),
        ]);

        $billableItems = $order->items->reject(
            fn (OrderItem $orderItem): bool => $orderItem->status === OrderItemStatus::PENDING
        );
        $unservedItems = $billableItems->reject(
            fn (OrderItem $orderItem): bool => $orderItem->status === OrderItemStatus::SERVED
        );
        $subtotal = (int) $billableItems->sum('line_total');
        $discount = (int) $order->discount_amount;

        return view('payments.show', [
            'order' => $order,
            'place' => $this->placeLabel($order),
            'billableItems' => $billableItems,
            'unservedItems' => $unservedItems,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => max(0, $subtotal - $discount),
        ]);
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
