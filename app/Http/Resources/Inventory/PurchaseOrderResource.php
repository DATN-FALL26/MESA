<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use App\Models\PurchaseOrder;
use App\Services\Inventory\PurchaseOrderProcessPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PurchaseOrder
 */
class PurchaseOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hasDetailProcess = $this->relationLoaded('auditLogs') || $this->relationLoaded('stockBatches');
        $processData = $hasDetailProcess
            ? app(PurchaseOrderProcessPresenter::class)->build($this->resource)
            : null;

        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'branch_name' => $this->branch?->name,
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->warehouse?->name,
            'supplier_id' => $this->supplier_id,
            'supplier_name' => $this->supplier?->name,
            'po_no' => $this->po_no,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'source' => $this->source?->value ?? \App\Enums\PurchaseOrderSource::MANUAL->value,
            'source_label' => $this->source?->label() ?? 'Thủ công',
            'note' => $this->note,
            'total_amount' => $this->items ? (float) $this->items->sum(fn ($i) => (float) $i->quantity * (float) $i->unit_price) : 0.0,
            'creator_name' => $this->creator?->full_name ?? $this->creator?->name,
            'approver_name' => $this->approver?->full_name ?? $this->approver?->name,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'receiver_name' => $this->receiver?->full_name ?? $this->receiver?->name,
            'received_at' => $this->received_at?->toIso8601String(),
            'approval_status' => $this->whenLoaded('approvalRequest', fn () => $this->approvalRequest?->status?->value),
            'approval_status_label' => $this->whenLoaded('approvalRequest', fn () => $this->approvalRequest?->status?->label()),
            'approval_note' => $this->whenLoaded('approvalRequest', fn () => $this->approvalRequest?->note),
            'items_count' => $this->items_count ?? $this->items?->count(),
            'items' => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
            'approval_request' => $this->when($hasDetailProcess, fn () => $processData['approval_request'] ?? null),
            'process_steps' => $this->when($hasDetailProcess, fn () => $processData['process_steps'] ?? []),
            'process_timeline' => $this->when($hasDetailProcess, fn () => $processData['process_timeline'] ?? []),
            'stock_batches' => $this->whenLoaded('stockBatches', fn () => $this->stockBatches->map(fn ($batch) => [
                'id' => $batch->id,
                'batch_code' => $batch->batch_code,
                'ingredient_id' => $batch->ingredient_id,
                'ingredient_sku' => $batch->ingredient?->sku,
                'ingredient_name' => $batch->ingredient?->name,
                'base_uom' => $batch->ingredient?->base_uom,
                'initial_quantity' => (float) $batch->initial_quantity,
                'current_quantity' => (float) $batch->current_quantity,
                'unit_cost' => (float) $batch->unit_cost,
                'import_date' => $batch->import_date?->toDateString(),
                'expiry_date' => $batch->expiry_date?->toDateString(),
                'status' => $batch->status?->value,
                'quality_status' => $batch->quality_status,
            ])->values()),
            'stock_movements_count' => $this->whenLoaded('stockMovements', fn () => $this->stockMovements->count()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
