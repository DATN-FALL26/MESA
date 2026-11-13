<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\ReceivePurchaseOrderRequest;
use App\Http\Requests\Inventory\StorePurchaseOrderRequest;
use App\Http\Requests\Inventory\UpdatePurchaseOrderRequest;
use App\Http\Resources\Inventory\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Services\Inventory\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PurchaseOrderController extends Controller
{
    public function __construct(
        protected PurchaseOrderRepositoryInterface $poRepository,
        protected PurchaseOrderService $poService,
    ) {}

    /**
     * Danh sách đơn đặt/nhập hàng có phân trang và bộ lọc.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->input('per_page', 15);
        $filters = $request->only(['branch_id', 'warehouse_id', 'supplier_id', 'status', 'source', 'search']);

        $orders = $this->poRepository->paginate($filters, $perPage);

        return PurchaseOrderResource::collection($orders);
    }

    /**
     * Thêm mới đơn nhập hàng (Bản nháp).
     */
    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $data = $request->safe()->except('items');
        $items = $request->input('items', []);
        $userId = auth()->id() ? (int) auth()->id() : 1;

        $po = $this->poService->create($data, $items, $userId);

        return (new PurchaseOrderResource($po))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Chi tiết đơn nhập hàng.
     */
    public function show(PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        $po = $this->poRepository->findByIdWithItems($purchaseOrder->id) ?? $purchaseOrder;

        return new PurchaseOrderResource($po);
    }

    /**
     * Cập nhật đơn nhập hàng (khi còn là bản nháp).
     */
    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        $data = $request->safe()->except('items');
        $items = $request->has('items') ? $request->input('items') : null;

        $updated = $this->poService->update($purchaseOrder, $data, $items);

        return new PurchaseOrderResource($updated);
    }

    /**
     * Gửi đơn nhập hàng đi phê duyệt.
     */
    public function submit(PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        $userId = auth()->id() ? (int) auth()->id() : 1;
        $updated = $this->poService->submitForApproval($purchaseOrder, $userId);

        return new PurchaseOrderResource($updated);
    }

    /**
     * Phê duyệt đơn nhập hàng.
     */
    public function approve(Request $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        $approverId = auth()->id() ? (int) auth()->id() : 1;
        $note = $request->input('note');

        $updated = $this->poService->approve($purchaseOrder, $approverId, $note);

        return new PurchaseOrderResource($updated);
    }

    /**
     * Từ chối đơn nhập hàng (trả về bản nháp).
     */
    public function reject(Request $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        $deciderId = auth()->id() ? (int) auth()->id() : 1;
        $note = $request->input('note');

        $updated = $this->poService->reject($purchaseOrder, $deciderId, $note);

        return new PurchaseOrderResource($updated);
    }

    /**
     * Hủy đơn nhập hàng.
     */
    public function cancel(Request $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        $userId = auth()->id() ? (int) auth()->id() : 1;
        $note = $request->input('note');

        $updated = $this->poService->cancel($purchaseOrder, $userId, $note);

        return new PurchaseOrderResource($updated);
    }

    /**
     * Tiếp nhận hàng về kho & ghi nhận biến động vào sổ cái kho.
     */
    public function receive(ReceivePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        $receiverId = auth()->id() ? (int) auth()->id() : 1;
        $receivedItems = $request->input('items');

        $updated = $this->poService->receive($purchaseOrder, $receiverId, $receivedItems);

        return new PurchaseOrderResource($updated);
    }
}
