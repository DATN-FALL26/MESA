<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use App\DTOs\Inventory\ExplodedIngredientDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read ExplodedIngredientDTO $resource
 */
class ExplodedIngredientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ingredient_id' => $this->resource->ingredientId,
            'sku' => $this->resource->sku,
            'name' => $this->resource->name,
            'quantity' => $this->resource->quantity,
            'base_uom' => $this->resource->baseUom,
        ];
    }
}
