<?php

declare(strict_types=1);

namespace App\DTOs\Inventory;

final readonly class DishCostAnalysisDTO
{
    /**
     * @param array<int, DishIngredientCostDTO> $ingredients
     */
    public function __construct(
        public int $menuItemId,
        public string $menuItemName,
        public string $menuItemSku,
        public ?int $variantId,
        public ?string $variantName,
        public float $sellingPrice,
        public float $recipeCost,
        public float $grossProfit,
        public float $costPercentage,
        public float $marginPercentage,
        public bool $hasRecipe,
        public ?int $recipeVersion = null,
        public array $ingredients = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'menu_item_id' => $this->menuItemId,
            'menu_item_name' => $this->menuItemName,
            'menu_item_sku' => $this->menuItemSku,
            'variant_id' => $this->variantId,
            'variant_name' => $this->variantName,
            'selling_price' => $this->sellingPrice,
            'recipe_cost' => $this->recipeCost,
            'gross_profit' => $this->grossProfit,
            'cost_percentage' => $this->costPercentage,
            'margin_percentage' => $this->marginPercentage,
            'has_recipe' => $this->hasRecipe,
            'recipe_version' => $this->recipeVersion,
            'ingredients' => array_map(fn (DishIngredientCostDTO $item) => $item->toArray(), $this->ingredients),
        ];
    }
}
