<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Inventory;

use App\Enums\AdjustmentReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\RecordDirectWasteRequest;
use App\Http\Requests\Inventory\StoreStockAdjustmentRequest;
use App\Http\Requests\Inventory\UpdateStockAdjustmentItemsRequest;
use App\Http\Resources\Inventory\StockAdjustmentResource;
use App\Models\StockAdjustment;
use App\Repositories\Contracts\StockAdjustmentRepositoryInterface;
use App\Services\Inventory\StockAdjustmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockAdjustmentController extends Controller
{
    public function __construct(
        protected StockAdjustmentRepositoryInterface $adjustmentRepository,
        protected StockAdjustmentService $adjustmentService,
    ) {}

    /**
     * Danh sách phiếu kiểm kê có phân trang và bộ lọc.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->input('per_page', 15);
        $filters = $request->only(['warehouse_id', 'reason', 'status', 'search']);

        $adjustments = $this->adjustmentRepository->paginate($filters, $perPage);

        return StockAdjustmentResource::collection($adjustments);
    }

    /**
     * Tạo mới phiếu kiểm kê ở trạng thái Bản nháp (DRAFT) kèm snapshot tồn tức thời.
     */
    public function store(StoreStockAdjustmentRequest $request): JsonResponse
    {
        $warehouseId = (int) $request->input('warehouse_id');
        $reason = AdjustmentReason::from((string) $request->input('reason'));
        $note = $request->input('note');
        $ingredientIds = $request->input('ingredient_ids');
        $userId = auth()->id() ? (int) auth()->id() : 1;

        $adjustment = $this->adjustmentService->createDraft(
            warehouseId: $warehouseId,
            reason: $reason,
            note: $note,
            ingredientIds: $ingredientIds,
            userId: $userId
        );

        return (new StockAdjustmentResource($adjustment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Chi tiết phiếu kiểm kê.
     */
    public function show(StockAdjustment $stockAdjustment): StockAdjustmentResource
    {
        $adj = $this->adjustmentRepository->findByIdWithItems($stockAdjustment->id) ?? $stockAdjustment;

        return new StockAdjustmentResource($adj);
    }

    /**
     * Cập nhật số lượng đếm thực tế của các mặt hàng trong phiếu kiểm kê nháp.
     */
    public function updateItems(UpdateStockAdjustmentItemsRequest $request, StockAdjustment $stockAdjustment): StockAdjustmentResource
    {
        $items = $request->input('items', []);
        $updated = $this->adjustmentService->updateItems($stockAdjustment, $items);

        return new StockAdjustmentResource($updated);
    }

    /**
     * Gửi phiếu kiểm kê đi phê duyệt cân đối.
     */
    public function submit(StockAdjustment $stockAdjustment): StockAdjustmentResource
    {
        $userId = auth()->id() ? (int) auth()->id() : 1;
        $updated = $this->adjustmentService->submitForApproval($stockAdjustment, $userId);

        return new StockAdjustmentResource($updated);
    }

    /**
     * Phê duyệt phiếu kiểm kê & tự động cân đối sổ cái kho.
     */
    public function approve(Request $request, StockAdjustment $stockAdjustment): StockAdjustmentResource
    {
        $approverId = auth()->id() ? (int) auth()->id() : 1;
        $note = $request->input('note');

        $updated = $this->adjustmentService->approve($stockAdjustment, $approverId, $note);

        return new StockAdjustmentResource($updated);
    }

    /**
     * Từ chối phiếu kiểm kê.
     */
    public function reject(Request $request, StockAdjustment $stockAdjustment): StockAdjustmentResource
    {
        $deciderId = auth()->id() ? (int) auth()->id() : 1;
        $note = $request->input('note');

        $updated = $this->adjustmentService->reject($stockAdjustment, $deciderId, $note);

        return new StockAdjustmentResource($updated);
    }

    /**
     * Ghi nhận xuất hủy / hao hụt nhanh hằng ngày (Direct Waste Disposal).
     */
    public function waste(RecordDirectWasteRequest $request): JsonResponse
    {
        $userId = auth()->id() ? (int) auth()->id() : 1;

        $adjustment = $this->adjustmentService->recordDirectWaste(
            warehouseId: (int) $request->input('warehouse_id'),
            ingredientId: (int) $request->input('ingredient_id'),
            quantity: (float) $request->input('quantity'),
            reasonNote: (string) $request->input('note'),
            userId: $userId
        );

        return (new StockAdjustmentResource($adjustment))
            ->response()
            ->setStatusCode(201);
    }
}
