<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Recipe
 */
class RecipeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'menu_item_id' => $this->menu_item_id,
            'menu_item_name' => $this->menuItem?->name,
            'variant_id' => $this->variant_id,
            'variant_name' => $this->variant?->name,
            'version' => (int) $this->version,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'items' => RecipeItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
