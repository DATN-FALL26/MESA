<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Ingredient
 */
class IngredientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'ingredient_type' => $this->ingredient_type->value,
            'ingredient_type_label' => $this->ingredient_type->label(),
            'base_uom' => $this->base_uom,
            'min_stock' => (float) $this->min_stock,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
