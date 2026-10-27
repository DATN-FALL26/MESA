<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\DTOs\Inventory\StockMovementDTO;
use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentStatus;
use App\Enums\ApprovalStatus;
use App\Enums\ApprovalType;
use App\Enums\MovementType;
use App\Models\ApprovalRequest;
use App\Models\Ingredient;
use App\Models\StockAdjustment;
use App\Models\StockLevel;
use App\Repositories\Contracts\StockAdjustmentRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockAdjustmentService
{
    public function __construct(
        protected StockAdjustmentRepositoryInterface $adjustmentRepository,
        protected StockLedgerService $stockLedger,
    ) {}

    /**
     * Tạo mới phiếu kiểm kê ở trạng thái Bản nháp (DRAFT) kèm snapshot tồn kho sổ sách tức thời.
     *
     * @param array<int>|null $ingredientIds Nếu null, hệ thống sẽ lấy snapshot toàn bộ nguyên liệu
     *
     * @throws ValidationException
     */
    public function createDraft(
        int $warehouseId,
        AdjustmentReason $reason,
        ?string $note = null,
        ?array $ingredientIds = null,
        ?int $userId = null
    ): StockAdjustment {
        $userId = $userId ?? (auth()->id() ? (int) auth()->id() : 1);

        // Lấy danh sách nguyên vật liệu cần kiểm kê
        $ingredientsQuery = Ingredient::query();
        if (!empty($ingredientIds)) {
            $ingredientsQuery->whereIn('id', $ingredientIds);
        }
        $ingredients = $ingredientsQuery->get();

        if ($ingredients->isEmpty()) {
            throw ValidationException::withMessages([
                'ingredient_ids' => 'Không tìm thấy nguyên vật liệu nào để tạo phiếu kiểm kê.',
            ]);
        }

        // Lấy mức tồn kho tức thời (Snapshot) của các nguyên liệu tại kho này
        $stockLevels = StockLevel::where('warehouse_id', $warehouseId)
            ->whereIn('ingredient_id', $ingredients->pluck('id'))
            ->get()
            ->keyBy('ingredient_id');

        $items = [];
        foreach ($ingredients as $ingredient) {
            $currentQty = isset($stockLevels[$ingredient->id])
                ? (float) $stockLevels[$ingredient->id]->quantity
                : 0.0;

            $items[] = [
                'ingredient_id' => $ingredient->id,
                'system_qty' => $currentQty,
                'actual_qty' => $currentQty, // Mặc định ban đầu bằng tồn sổ sách
                'note' => null,
            ];
        }

        $data = [
            'warehouse_id' => $warehouseId,
            'reason' => $reason,
            'note' => $note,
            'status' => AdjustmentStatus::DRAFT,
            'created_by' => $userId,
        ];

        return $this->adjustmentRepository->create($data, $items);
    }

    /**
     * Cập nhật số lượng đếm thực tế cho các mặt hàng trong phiếu kiểm kê nháp.
     *
     * @param array<int, array<string, mixed>> $items
     *
     * @throws ValidationException
     */
    public function updateItems(StockAdjustment $adjustment, array $items): StockAdjustment
    {
        if ($adjustment->status !== AdjustmentStatus::DRAFT) {
            throw ValidationException::withMessages([
                'status' => 'Chỉ có thể cập nhật số lượng khi phiếu kiểm kê đang ở trạng thái Bản nháp.',
            ]);
        }

        return $this->adjustmentRepository->updateItems($adjustment, $items);
    }

    /**
     * Gửi phiếu kiểm kê đi phê duyệt cân đối.
     *
     * @throws ValidationException
     */
    public function submitForApproval(StockAdjustment $adjustment, int $userId): StockAdjustment
    {
        if ($adjustment->status !== AdjustmentStatus::DRAFT) {
            throw ValidationException::withMessages([
                'status' => 'Chỉ có thể gửi duyệt phiếu kiểm kê đang ở trạng thái Bản nháp.',
            ]);
        }

        return DB::transaction(function () use ($adjustment, $userId): StockAdjustment {
            $updatedAdj = $this->adjustmentRepository->updateStatus($adjustment, AdjustmentStatus::PENDING_APPROVAL);

            $branchId = (int) $adjustment->warehouse->branch_id;

            ApprovalRequest::updateOrCreate(
                [
                    'request_type' => ApprovalType::STOCK_ADJUSTMENT,
                    'ref_id' => $adjustment->id,
                ],
                [
                    'branch_id' => $branchId,
                    'requested_by' => $userId,
                    'status' => ApprovalStatus::PENDING,
                    'decided_by' => null,
                    'decided_at' => null,
                    'note' => $adjustment->note,
                ]
            );

            return $updatedAdj;
        });
    }

    /**
     * Phê duyệt phiếu kiểm kê & tự động ghi nhận cân đối sổ cái kho (ADJUST / WASTE).
     *
     * @throws ValidationException
     */
    public function approve(StockAdjustment $adjustment, int $approverId, ?string $note = null): StockAdjustment
    {
        if ($adjustment->status !== AdjustmentStatus::PENDING_APPROVAL) {
            throw ValidationException::withMessages([
                'status' => 'Chỉ có thể phê duyệt phiếu kiểm kê đang ở trạng thái Chờ duyệt.',
            ]);
        }

        return DB::transaction(function () use ($adjustment, $approverId, $note): StockAdjustment {
            $items = $adjustment->items()->get();

            // Cân đối sổ cái cho từng dòng có chênh lệch giữa thực tế và sổ sách
            foreach ($items as $item) {
                $systemQty = (float) $item->system_qty;
                $actualQty = (float) $item->actual_qty;
                $difference = $actualQty - $systemQty;

                if (abs($difference) > 0.0001) {
                    $movementType = $adjustment->reason === AdjustmentReason::WASTE
                        ? MovementType::WASTE
                        : MovementType::ADJUST;

                    $dto = new StockMovementDTO(
                        warehouseId: (int) $adjustment->warehouse_id,
                        ingredientId: (int) $item->ingredient_id,
                        movementType: $movementType,
                        quantity: $difference, // Nếu thiếu ghi âm, thừa ghi dương
                        unitCost: null,
                        refType: 'stock_adjustment',
                        refId: $adjustment->id,
                        idempotencyKey: "adj_{$adjustment->id}_ing_{$item->ingredient_id}",
                        note: "Cân đối kiểm kê {$adjustment->adjustment_no}: " . ($item->note ?? $adjustment->reason->label()),
                        createdBy: $approverId
                    );

                    $this->stockLedger->recordMovement($dto);
                }
            }

            // Cập nhật trạng thái phiếu kiểm kê sang Đã duyệt
            $updatedAdj = $this->adjustmentRepository->updateStatus($adjustment, AdjustmentStatus::APPROVED, [
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            ApprovalRequest::where('request_type', ApprovalType::STOCK_ADJUSTMENT)
                ->where('ref_id', $adjustment->id)
                ->update([
                    'status' => ApprovalStatus::APPROVED,
                    'decided_by' => $approverId,
                    'decided_at' => now(),
                    'note' => $note ?? $adjustment->note,
                ]);

            return $updatedAdj;
        });
    }

    /**
     * Từ chối phiếu kiểm kê.
     *
     * @throws ValidationException
     */
    public function reject(StockAdjustment $adjustment, int $deciderId, ?string $note = null): StockAdjustment
    {
        if ($adjustment->status !== AdjustmentStatus::PENDING_APPROVAL) {
            throw ValidationException::withMessages([
                'status' => 'Chỉ có thể từ chối phiếu kiểm kê đang ở trạng thái Chờ duyệt.',
            ]);
        }

        return DB::transaction(function () use ($adjustment, $deciderId, $note): StockAdjustment {
            $updatedAdj = $this->adjustmentRepository->updateStatus($adjustment, AdjustmentStatus::REJECTED);

            ApprovalRequest::where('request_type', ApprovalType::STOCK_ADJUSTMENT)
                ->where('ref_id', $adjustment->id)
                ->update([
                    'status' => ApprovalStatus::REJECTED,
                    'decided_by' => $deciderId,
                    'decided_at' => now(),
                    'note' => $note,
                ]);

            return $updatedAdj;
        });
    }

    /**
     * Ghi nhận xuất hủy / hao hụt hằng ngày nhanh gọn (Direct Waste Disposal).
     */
    public function recordDirectWaste(
        int $warehouseId,
        int $ingredientId,
        float $quantity,
        string $reasonNote,
        int $userId
    ): StockAdjustment {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Số lượng xuất hủy hao hụt phải lớn hơn 0.',
            ]);
        }

        return DB::transaction(function () use ($warehouseId, $ingredientId, $quantity, $reasonNote, $userId): StockAdjustment {
            // Lấy mức tồn hệ thống
            $stockLevel = StockLevel::where('warehouse_id', $warehouseId)
                ->where('ingredient_id', $ingredientId)
                ->first();

            $currentQty = $stockLevel ? (float) $stockLevel->quantity : 0.0;
            $newActualQty = max(0.0, $currentQty - $quantity);

            // Tạo phiếu kiểm kê lý do WASTE ở trạng thái APPROVED
            $adjustment = $this->adjustmentRepository->create(
                [
                    'warehouse_id' => $warehouseId,
                    'reason' => AdjustmentReason::WASTE,
                    'note' => $reasonNote,
                    'status' => AdjustmentStatus::APPROVED,
                    'created_by' => $userId,
                    'approved_by' => $userId,
                    'approved_at' => now(),
                ],
                [
                    [
                        'ingredient_id' => $ingredientId,
                        'system_qty' => $currentQty,
                        'actual_qty' => $newActualQty,
                        'note' => $reasonNote,
                    ],
                ]
            );

            // Ghi nhận dòng biến động xuất hủy
            $dto = new StockMovementDTO(
                warehouseId: $warehouseId,
                ingredientId: $ingredientId,
                movementType: MovementType::WASTE,
                quantity: -$quantity, // Trừ kho
                unitCost: null,
                refType: 'stock_adjustment',
                refId: $adjustment->id,
                idempotencyKey: "direct_waste_{$adjustment->id}_ing_{$ingredientId}",
                note: "Xuất hủy trực tiếp: {$reasonNote}",
                createdBy: $userId
            );

            $this->stockLedger->recordMovement($dto);

            return $adjustment;
        });
    }
}
