<?php

declare(strict_types=1);

namespace App\DTOs\Inventory;

final readonly class ExplodedIngredientDTO
{
    public function __construct(
        public int $ingredientId,
        public string $sku,
        public string $name,
        public float $quantity,
        public string $baseUom,
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
        ];
    }
}
