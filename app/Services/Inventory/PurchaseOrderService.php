<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\DTOs\Inventory\StockMovementDTO;
use App\Enums\ApprovalStatus;
use App\Enums\ApprovalType;
use App\Enums\MovementType;
use App\Enums\PurchaseOrderStatus;
use App\Models\ApprovalRequest;
use App\Models\PurchaseOrder;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderService
{
    public function __construct(
        protected PurchaseOrderRepositoryInterface $poRepository,
        protected StockLedgerService $stockLedger,
    ) {}

    /**
     * Tạo mới đơn đặt/nhập hàng với trạng thái Bản nháp.
     *
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $items
     */
    public function create(array $data, array $items, int $userId): PurchaseOrder
    {
        $data['created_by'] = $userId;
        $data['status'] = PurchaseOrderStatus::DRAFT;
        $data['source'] = $data['source'] ?? \App\Enums\PurchaseOrderSource::MANUAL;

        return $this->poRepository->create($data, $items);
    }

    /**
     * Cập nhật thông tin và danh sách mặt hàng của đơn nhập hàng (chỉ khi còn ở bản nháp).
     *
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>>|null $items
     *
     * @throws ValidationException
     */
    public function update(PurchaseOrder $po, array $data, ?array $items = null): PurchaseOrder
    {
        if ($po->status !== PurchaseOrderStatus::DRAFT) {
            throw ValidationException::withMessages([
                'status' => 'Không thể sửa đổi đơn nhập hàng khi đã được gửi duyệt hoặc đã nhận hàng.',
            ]);
        }

        return $this->poRepository->update($po, $data, $items);
    }

    /**
     * Gửi đơn nhập hàng đi phê duyệt.
     *
     * @throws ValidationException
     */
    public function submitForApproval(PurchaseOrder $po, int $userId): PurchaseOrder
    {
        if ($po->status !== PurchaseOrderStatus::DRAFT) {
            throw ValidationException::withMessages([
                'status' => 'Chỉ có thể gửi duyệt đơn nhập hàng đang ở trạng thái Bản nháp.',
            ]);
        }

        if ($po->items()->count() === 0) {
            throw ValidationException::withMessages([
                'items' => 'Đơn nhập hàng phải có ít nhất 1 mặt hàng để gửi duyệt.',
            ]);
        }

        return DB::transaction(function () use ($po, $userId): PurchaseOrder {
            // Chuyển trạng thái PO sang Chờ duyệt
            $updatedPo = $this->poRepository->updateStatus($po, PurchaseOrderStatus::PENDING_APPROVAL);

            // Tạo yêu cầu phê duyệt liên kết
            ApprovalRequest::updateOrCreate(
                [
                    'request_type' => ApprovalType::PURCHASE_ORDER,
                    'ref_id' => $po->id,
                ],
                [
                    'branch_id' => $po->branch_id,
                    'requested_by' => $userId,
                    'status' => ApprovalStatus::PENDING,
                    'decided_by' => null,
                    'decided_at' => null,
                    'note' => $po->note,
                ]
            );

            return $updatedPo;
        });
    }

    /**
     * Phê duyệt đơn nhập hàng.
     *
     * @throws ValidationException
     */
    public function approve(PurchaseOrder $po, int $approverId, ?string $note = null): PurchaseOrder
    {
        if ($po->status !== PurchaseOrderStatus::PENDING_APPROVAL) {
            throw ValidationException::withMessages([
                'status' => 'Chỉ có thể phê duyệt đơn nhập hàng đang ở trạng thái Chờ duyệt.',
            ]);
        }

        return DB::transaction(function () use ($po, $approverId, $note): PurchaseOrder {
            $updatedPo = $this->poRepository->updateStatus($po, PurchaseOrderStatus::APPROVED, [
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            ApprovalRequest::where('request_type', ApprovalType::PURCHASE_ORDER)
                ->where('ref_id', $po->id)
                ->update([
                    'status' => ApprovalStatus::APPROVED,
                    'decided_by' => $approverId,
                    'decided_at' => now(),
                    'note' => $note ?? $po->note,
                ]);

            return $updatedPo;
        });
    }

    /**
     * Từ chối đơn nhập hàng (trả về Bản nháp để chỉnh sửa hoặc hủy).
     *
     * @throws ValidationException
     */
    public function reject(PurchaseOrder $po, int $deciderId, ?string $note = null): PurchaseOrder
    {
        if ($po->status !== PurchaseOrderStatus::PENDING_APPROVAL) {
            throw ValidationException::withMessages([
                'status' => 'Chỉ có thể từ chối đơn nhập hàng đang ở trạng thái Chờ duyệt.',
            ]);
        }

        return DB::transaction(function () use ($po, $deciderId, $note): PurchaseOrder {
            $updatedPo = $this->poRepository->updateStatus($po, PurchaseOrderStatus::DRAFT, [
                'approved_by' => null,
                'approved_at' => null,
            ]);

            ApprovalRequest::where('request_type', ApprovalType::PURCHASE_ORDER)
                ->where('ref_id', $po->id)
                ->update([
                    'status' => ApprovalStatus::REJECTED,
                    'decided_by' => $deciderId,
                    'decided_at' => now(),
                    'note' => $note,
                ]);

            return $updatedPo;
        });
    }

    /**
     * Hủy đơn nhập hàng.
     *
     * @throws ValidationException
     */
    public function cancel(PurchaseOrder $po, int $userId, ?string $note = null): PurchaseOrder
    {
        if ($po->status === PurchaseOrderStatus::RECEIVED) {
            throw ValidationException::withMessages([
                'status' => 'Không thể hủy đơn hàng đã hoàn tất nhập kho.',
            ]);
        }

        return DB::transaction(function () use ($po, $note): PurchaseOrder {
            $updatedPo = $this->poRepository->updateStatus($po, PurchaseOrderStatus::CANCELLED, [
                'note' => $note ?? $po->note,
            ]);

            ApprovalRequest::where('request_type', ApprovalType::PURCHASE_ORDER)
                ->where('ref_id', $po->id)
                ->update([
                    'status' => ApprovalStatus::REJECTED,
                    'note' => $note ?? 'Đơn hàng đã bị hủy bởi người dùng.',
                ]);

            return $updatedPo;
        });
    }

    /**
     * Xác nhận nhập kho thực tế & tăng tồn kho vào sổ cái bất biến.
     *
     * @param array<int, array<string, mixed>>|null $receivedItems [ ['ingredient_id' => int, 'received_qty' => float, 'unit_price' => ?float] ]
     *
     * @throws ValidationException
     */
    public function receive(PurchaseOrder $po, int $receiverId, ?array $receivedItems = null): PurchaseOrder
    {
        if ($po->status !== PurchaseOrderStatus::APPROVED) {
            throw ValidationException::withMessages([
                'status' => 'Chỉ có thể nhận hàng khi đơn đã được phê duyệt (APPROVED).',
            ]);
        }

        return DB::transaction(function () use ($po, $receiverId, $receivedItems): PurchaseOrder {
            $items = $po->items()->get();

            // Lập map nếu người dùng truyền danh sách số lượng nhận riêng biệt
            $receivedMap = [];
            if ($receivedItems !== null) {
                foreach ($receivedItems as $item) {
                    $receivedMap[(int) $item['ingredient_id']] = [
                        'qty' => (float) $item['received_qty'],
                        'unit_price' => isset($item['unit_price']) ? (float) $item['unit_price'] : null,
                    ];
                }
            }

            foreach ($items as $poItem) {
                $ingId = (int) $poItem->ingredient_id;
                $receivedQty = isset($receivedMap[$ingId])
                    ? $receivedMap[$ingId]['qty']
                    : (float) $poItem->quantity;

                $unitPrice = (isset($receivedMap[$ingId]['unit_price']) && $receivedMap[$ingId]['unit_price'] !== null)
                    ? $receivedMap[$ingId]['unit_price']
                    : (float) $poItem->unit_price;

                // Cập nhật số lượng thực nhận trên dòng PO item
                $poItem->update([
                    'received_qty' => $receivedQty,
                    'unit_price' => $unitPrice,
                ]);

                // Nếu số lượng thực nhận > 0 thì ghi nhận biến động kho
                if ($receivedQty > 0) {
                    $dto = new StockMovementDTO(
                        warehouseId: (int) $po->warehouse_id,
                        ingredientId: $ingId,
                        movementType: MovementType::PURCHASE_IN,
                        quantity: $receivedQty,
                        unitCost: $unitPrice,
                        refType: 'purchase_order',
                        refId: $po->id,
                        idempotencyKey: "po_{$po->id}_ing_{$ingId}",
                        note: "Nhập hàng từ NCC theo đơn {$po->po_no}",
                        createdBy: $receiverId
                    );

                    $this->stockLedger->recordMovement($dto);
                }
            }

            // Cập nhật trạng thái đơn hàng sang Đã nhận hàng
            return $this->poRepository->updateStatus($po, PurchaseOrderStatus::RECEIVED, [
                'received_by' => $receiverId,
                'received_at' => now(),
            ]);
        });
    }
}
