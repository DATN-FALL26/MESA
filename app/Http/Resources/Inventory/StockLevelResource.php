<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use App\Models\StockLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockLevel
 */
class StockLevelResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $quantity = (float) $this->quantity;
        $minStock = (float) ($this->ingredient?->min_stock ?? 0);

        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->warehouse?->name,
            'ingredient_id' => $this->ingredient_id,
            'ingredient_sku' => $this->ingredient?->sku,
            'ingredient_name' => $this->ingredient?->name,
            'base_uom' => $this->ingredient?->base_uom,
            'quantity' => $quantity,
            'avg_cost' => (float) $this->avg_cost,
            'total_value' => round($quantity * (float) $this->avg_cost, 0),
            'min_stock' => $minStock,
            'is_low_stock' => $quantity <= $minStock,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
