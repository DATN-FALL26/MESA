<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PurchaseOrderItem
 */
class PurchaseOrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_order_id' => $this->purchase_order_id,
            'ingredient_id' => $this->ingredient_id,
            'ingredient_sku' => $this->ingredient?->sku,
            'ingredient_name' => $this->ingredient?->name,
            'base_uom' => $this->ingredient?->base_uom,
            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'received_qty' => (float) $this->received_qty,
            'subtotal' => (float) ($this->quantity * $this->unit_price),
        ];
    }
}
