<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use App\Models\StockAdjustment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockAdjustment
 */
class StockAdjustmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->warehouse?->name,
            'branch_name' => $this->warehouse?->branch?->name,
            'adjustment_no' => $this->adjustment_no,
            'reason' => $this->reason->value,
            'reason_label' => $this->reason->label(),
            'note' => $this->note,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'creator_name' => $this->creator?->full_name ?? $this->creator?->name,
            'approver_name' => $this->approver?->full_name ?? $this->approver?->name,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'items_count' => $this->items_count ?? $this->items?->count(),
            'items' => StockAdjustmentItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
