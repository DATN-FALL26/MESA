<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockMovement
 */
class StockMovementResource extends JsonResource
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
            'ingredient_id' => $this->ingredient_id,
            'ingredient_name' => $this->ingredient?->name,
            'ingredient_sku' => $this->ingredient?->sku,
            'movement_type' => $this->movement_type->value,
            'movement_type_label' => $this->movement_type->label(),
            'quantity' => (float) $this->quantity,
            'unit_cost' => $this->unit_cost !== null ? (float) $this->unit_cost : null,
            'ref_type' => $this->ref_type,
            'ref_id' => $this->ref_id,
            'idempotency_key' => $this->idempotency_key,
            'note' => $this->note,
            'created_by' => $this->created_by,
            'creator_name' => $this->creator?->full_name,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
