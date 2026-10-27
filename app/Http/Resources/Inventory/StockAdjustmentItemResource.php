<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use App\Models\StockAdjustmentItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockAdjustmentItem
 */
class StockAdjustmentItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $systemQty = (float) $this->system_qty;
        $actualQty = (float) $this->actual_qty;
        $diff = $actualQty - $systemQty;

        return [
            'id' => $this->id,
            'adjustment_id' => $this->adjustment_id,
            'ingredient_id' => $this->ingredient_id,
            'ingredient_sku' => $this->ingredient?->sku,
            'ingredient_name' => $this->ingredient?->name,
            'base_uom' => $this->ingredient?->base_uom,
            'system_qty' => $systemQty,
            'actual_qty' => $actualQty,
            'difference_qty' => $diff,
            'note' => $this->note,
        ];
    }
}
