<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PurchaseOrder;
use App\Models\DiningSession;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Models\User;
use App\Services\Auth\BranchAccessService;
use Closure;
use Illuminate\Http\Request;

class EnsureBranchAccess
{
    public function __construct(private BranchAccessService $branchAccess) {}

    /**
     * @param  array<int, string>  $permissions
     */
    public function handle(Request $request, Closure $next, string ...$permissions): mixed
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $permissions = $permissions === [] ? null : $permissions;
        $allowedBranchIds = $this->branchAccess->accessibleBranchIds($user, $permissions);
        $request->attributes->set('authorized_branch_ids', $allowedBranchIds);

        $selectedBranchId = $this->selectedBranchId($request);
        $request->attributes->set('selected_branch_id', $selectedBranchId);
        $resourceBranchId = $this->resourceBranchId($request);
        $warehouseBranchId = $this->warehouseBranchId($request);
        $requestedBranchId = $request->filled('branch_id')
            ? $request->integer('branch_id')
            : null;

        if ($requestedBranchId !== null && $warehouseBranchId !== null) {
            abort_unless($requestedBranchId === $warehouseBranchId, 404);
        }

        foreach ([
            $selectedBranchId,
            $resourceBranchId,
            $warehouseBranchId,
            $requestedBranchId,
        ] as $branchId) {
            if ($branchId === null) {
                continue;
            }

            $this->branchAccess->authorizeBranch($user, (int) $branchId, $permissions);
        }

        if ($selectedBranchId !== null && $resourceBranchId !== null) {
            abort_unless($selectedBranchId === $resourceBranchId, 404);
        }

        if ($requestedBranchId !== null && $resourceBranchId !== null) {
            abort_unless($requestedBranchId === $resourceBranchId, 404);
        }

        if ($selectedBranchId !== null && $warehouseBranchId !== null) {
            abort_unless($selectedBranchId === $warehouseBranchId, 404);
        }

        if ($resourceBranchId !== null && $warehouseBranchId !== null) {
            abort_unless($resourceBranchId === $warehouseBranchId, 404);
        }

        return $next($request);
    }

    private function selectedBranchId(Request $request): ?int
    {
        if ($request->route('branch') !== null) {
            $branch = $request->route('branch');

            return (int) (is_object($branch) ? $branch->getKey() : $branch);
        }

        if ($request->filled('branch_id')) {
            return $request->integer('branch_id');
        }

        $sessionBranchId = $request->session()->get('branch_id');

        return is_numeric($sessionBranchId) ? (int) $sessionBranchId : null;
    }

    private function resourceBranchId(Request $request): ?int
    {
        $order = $request->route('order');
        if ($order instanceof Order) {
            return (int) $order->branch_id;
        }

        $orderId = $request->route('orderId');
        if (is_numeric($orderId)) {
            return (int) Order::query()->findOrFail((int) $orderId)->branch_id;
        }

        $sessionId = $request->route('sessionId');
        if (is_numeric($sessionId)) {
            return (int) DiningSession::query()->findOrFail((int) $sessionId)->branch_id;
        }

        $tableId = $request->route('tableId');
        if (is_numeric($tableId)) {
            return (int) DiningTable::query()->findOrFail((int) $tableId)->branch_id;
        }

        $purchaseOrder = $request->route('purchaseOrder');
        if ($purchaseOrder instanceof PurchaseOrder || is_numeric($purchaseOrder)) {
            return (int) PurchaseOrder::query()->findOrFail(
                $purchaseOrder instanceof PurchaseOrder ? $purchaseOrder->getKey() : (int) $purchaseOrder
            )->branch_id;
        }

        $stockAdjustment = $request->route('stockAdjustment');
        if ($stockAdjustment instanceof StockAdjustment || is_numeric($stockAdjustment)) {
            $adjustment = StockAdjustment::query()->with('warehouse')->findOrFail(
                $stockAdjustment instanceof StockAdjustment ? $stockAdjustment->getKey() : (int) $stockAdjustment
            );

            return (int) $adjustment->warehouse->branch_id;
        }

        return null;
    }

    private function warehouseBranchId(Request $request): ?int
    {
        $warehouseId = $request->route('warehouseId') ?? $request->input('warehouse_id');
        if (! is_numeric($warehouseId)) {
            return null;
        }

        return (int) Warehouse::query()->findOrFail((int) $warehouseId)->branch_id;
    }
}
