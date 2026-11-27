<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\DTOs\Inventory\StockMovementDTO;
use App\Enums\ApprovalStatus;
use App\Enums\ApprovalType;
use App\Enums\MovementType;
use App\Enums\PurchaseOrderStatus;
use App\Models\ApprovalRequest;
use App\Models\AuditLog;
use App\Models\PurchaseOrder;
use App\Models\Warehouse;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderService
{
    public function __construct(
        protected PurchaseOrderRepositoryInterface $poRepository,
        protected StockLedgerService $stockLedger,
        protected StockBatchService $batchService,
    ) {}

    /**
     * Tạo mới đơn đặt/nhập hàng với trạng thái Bản nháp.
     *
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $items
     */
    public function create(array $data, array $items, int $userId): PurchaseOrder
    {
        $warehouse = Warehouse::query()->findOrFail((int) $data['warehouse_id']);
        if ((int) $warehouse->branch_id !== (int) $data['branch_id']) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'Kho nhận hàng phải thuộc chi nhánh của phiếu nhập.',
            ]);
        }

        $data['created_by'] = $userId;
        $data['status'] = PurchaseOrderStatus::DRAFT;
        $data['source'] = $data['source'] ?? \App\Enums\PurchaseOrderSource::MANUAL;

        return DB::transaction(function () use ($data, $items, $userId): PurchaseOrder {
            $po = $this->poRepository->create($data, $items);

            $totalAmount = collect($items)->sum(
                fn ($item) => (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0)
            );

            $this->recordAuditLog($po, $userId, 'purchase_order_created', null, [
                'status' => PurchaseOrderStatus::DRAFT->value,
                'po_no' => $po->po_no,
                'source' => $po->source?->value,
                'items_count' => count($items),
                'total_amount' => $totalAmount,
                'note' => $po->note,
            ]);

            return $po;
        });
    }

    /**
     * Cập nhật thông tin và danh sách mặt hàng của đơn nhập hàng (chỉ khi còn ở bản nháp).
     *
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>>|null $items
     *
     * @throws ValidationException
     */
    public function update(PurchaseOrder $po, array $data, ?array $items = null, ?int $userId = null): PurchaseOrder
    {
        if ($po->status !== PurchaseOrderStatus::DRAFT) {
            throw ValidationException::withMessages([
                'status' => 'Không thể sửa đổi đơn nhập hàng khi đã được gửi duyệt hoặc đã nhận hàng.',
            ]);
        }

        $warehouseId = isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : (int) $po->warehouse_id;
        $warehouse = Warehouse::query()->findOrFail($warehouseId);
        if ((int) $warehouse->branch_id !== (int) $po->branch_id) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'Kho nhận hàng phải thuộc chi nhánh của phiếu nhập.',
            ]);
        }

        return DB::transaction(function () use ($po, $data, $items, $userId): PurchaseOrder {
            $oldItems = $po->items()->get();
            $oldSummary = [
                'warehouse_id' => $po->warehouse_id,
                'supplier_id' => $po->supplier_id,
                'items_count' => $oldItems->count(),
                'total_amount' => (float) $oldItems->sum(fn ($i) => (float) $i->quantity * (float) $i->unit_price),
                'note' => $po->note,
            ];

            $updatedPo = $this->poRepository->update($po, $data, $items);

            $newItems = $updatedPo->items;
            $newSummary = [
                'warehouse_id' => $updatedPo->warehouse_id,
                'supplier_id' => $updatedPo->supplier_id,
                'items_count' => $newItems->count(),
                'total_amount' => (float) $newItems->sum(fn ($i) => (float) $i->quantity * (float) $i->unit_price),
                'note' => $updatedPo->note,
            ];

            $actorId = $userId ?? (int) (auth()->id() ?? $po->created_by);
            $this->recordAuditLog($updatedPo, $actorId, 'purchase_order_updated', $oldSummary, $newSummary);

            return $updatedPo;
        });
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
            $oldStatus = $po->status->value;
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

            $this->recordAuditLog(
                $updatedPo,
                $userId,
                'purchase_order_submitted',
                ['status' => $oldStatus],
                ['status' => PurchaseOrderStatus::PENDING_APPROVAL->value, 'note' => $po->note]
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
            $oldStatus = $po->status->value;
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

            $this->recordAuditLog(
                $updatedPo,
                $approverId,
                'purchase_order_approved',
                ['status' => $oldStatus],
                ['status' => PurchaseOrderStatus::APPROVED->value, 'note' => $note]
            );

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
            $oldStatus = $po->status->value;
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

            $this->recordAuditLog(
                $updatedPo,
                $deciderId,
                'purchase_order_rejected',
                ['status' => $oldStatus],
                ['status' => PurchaseOrderStatus::DRAFT->value, 'note' => $note]
            );

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

        return DB::transaction(function () use ($po, $userId, $note): PurchaseOrder {
            $oldStatus = $po->status->value;
            $cancelNote = $note ?? 'Đơn hàng đã bị hủy bởi người dùng.';
            $updatedPo = $this->poRepository->updateStatus($po, PurchaseOrderStatus::CANCELLED, [
                'note' => $note ?? $po->note,
            ]);

            ApprovalRequest::where('request_type', ApprovalType::PURCHASE_ORDER)
                ->where('ref_id', $po->id)
                ->update([
                    'status' => ApprovalStatus::REJECTED,
                    'decided_by' => $userId,
                    'decided_at' => now(),
                    'note' => $cancelNote,
                ]);

            $this->recordAuditLog(
                $updatedPo,
                $userId,
                'purchase_order_cancelled',
                ['status' => $oldStatus],
                ['status' => PurchaseOrderStatus::CANCELLED->value, 'note' => $cancelNote]
            );

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
            $oldStatus = $po->status->value;

            // Lập map nếu người dùng truyền danh sách số lượng nhận riêng biệt
            $receivedMap = [];
            if ($receivedItems !== null) {
                foreach ($receivedItems as $item) {
                    $receivedMap[(int) $item['ingredient_id']] = [
                        'qty' => (float) $item['received_qty'],
                        'unit_price' => isset($item['unit_price']) ? (float) $item['unit_price'] : null,
                        'batch_no' => isset($item['batch_no']) ? (string) $item['batch_no'] : null,
                        'expiry_date' => isset($item['expiry_date']) ? (string) $item['expiry_date'] : null,
                    ];
                }
            }

            $batchesCreated = 0;
            $totalReceivedAmount = 0.0;
            $hasDiscrepancy = false;

            foreach ($items as $poItem) {
                $ingId = (int) $poItem->ingredient_id;
                $receivedQty = isset($receivedMap[$ingId])
                    ? $receivedMap[$ingId]['qty']
                    : (float) $poItem->quantity;

                $unitPrice = (isset($receivedMap[$ingId]['unit_price']) && $receivedMap[$ingId]['unit_price'] !== null)
                    ? $receivedMap[$ingId]['unit_price']
                    : (float) $poItem->unit_price;

                $batchNo = $receivedMap[$ingId]['batch_no'] ?? $poItem->batch_no ?? null;
                $expiryDate = $receivedMap[$ingId]['expiry_date'] ?? $poItem->expiry_date ?? null;

                if (abs($receivedQty - (float) $poItem->quantity) > 0.0001) {
                    $hasDiscrepancy = true;
                }
                $totalReceivedAmount += $receivedQty * $unitPrice;

                // Cập nhật số lượng thực nhận trên dòng PO item
                $poItem->update([
                    'received_qty' => $receivedQty,
                    'unit_price' => $unitPrice,
                    'batch_no' => $batchNo,
                    'expiry_date' => $expiryDate,
                ]);

                // Nếu số lượng thực nhận > 0 thì ghi nhận biến động kho và tạo Lô hàng (Batch)
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

                    // Tự động khởi tạo lô hàng để quản lý HSD
                    $batchCode = $batchNo ?: ('LOT-' . date('Ymd') . '-PO' . $po->id . '-' . $ingId);
                    $finalExpiry = $expiryDate ?: now()->addDays(30)->toDateString();

                    $this->batchService->createBatch([
                        'batch_code' => $batchCode,
                        'warehouse_id' => (int) $po->warehouse_id,
                        'ingredient_id' => $ingId,
                        'purchase_order_id' => $po->id,
                        'supplier_id' => $po->supplier_id,
                        'initial_quantity' => $receivedQty,
                        'current_quantity' => $receivedQty,
                        'unit_cost' => $unitPrice,
                        'import_date' => now()->toDateString(),
                        'expiry_date' => $finalExpiry,
                        'created_by' => $receiverId,
                    ]);

                    $batchesCreated++;
                }
            }

            // Cập nhật trạng thái đơn hàng sang Đã nhận hàng
            $updatedPo = $this->poRepository->updateStatus($po, PurchaseOrderStatus::RECEIVED, [
                'received_by' => $receiverId,
                'received_at' => now(),
            ]);

            $this->recordAuditLog(
                $updatedPo,
                $receiverId,
                'purchase_order_received',
                ['status' => $oldStatus],
                [
                    'status' => PurchaseOrderStatus::RECEIVED->value,
                    'items_count' => $items->count(),
                    'batches_created' => $batchesCreated,
                    'total_received_amount' => $totalReceivedAmount,
                    'has_discrepancy' => $hasDiscrepancy,
                ]
            );

            return $updatedPo;
        });
    }

    /**
     * Ghi nhận nhật ký quá trình xử lý đơn nhập hàng vào audit_logs.
     *
     * @param array<string, mixed>|null $oldValue
     * @param array<string, mixed>|null $newValue
     */
    private function recordAuditLog(
        PurchaseOrder $po,
        int $userId,
        string $action,
        ?array $oldValue = null,
        ?array $newValue = null
    ): void {
        AuditLog::query()->create([
            'branch_id' => $po->branch_id,
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => 'purchase_order',
            'entity_id' => $po->id,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
