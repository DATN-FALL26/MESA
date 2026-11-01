<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use App\Models\RecipeItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RecipeItem
 */
class RecipeItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ingredient_id' => $this->ingredient_id,
            'ingredient_sku' => $this->ingredient?->sku,
            'ingredient_name' => $this->ingredient?->name,
            'base_uom' => $this->ingredient?->base_uom,
            'quantity' => (float) $this->quantity,
        ];
    }
}
