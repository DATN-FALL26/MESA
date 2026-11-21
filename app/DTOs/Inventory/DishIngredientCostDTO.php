<?php

declare(strict_types=1);

namespace App\DTOs\Inventory;

final readonly class DishIngredientCostDTO
{
    public function __construct(
        public int $ingredientId,
        public string $sku,
        public string $name,
        public float $quantity,
        public string $baseUom,
        public float $unitCost,
        public float $subtotalCost,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ingredient_id' => $this->ingredientId,
            'sku' => $this->sku,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'base_uom' => $this->baseUom,
            'unit_cost' => $this->unitCost,
            'subtotal_cost' => $this->subtotalCost,
        ];
    }
}
