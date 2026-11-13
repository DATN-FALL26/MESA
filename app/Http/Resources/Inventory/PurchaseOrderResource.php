<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use App\Models\PurchaseOrder;
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
            'items_count' => $this->items_count ?? $this->items?->count(),
            'items' => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
