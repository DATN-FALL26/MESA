<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\DTOs\Inventory\ExplodedIngredientDTO;
use App\Models\Ingredient;
use App\Models\Modifier;
use App\Models\OrderItem;
use App\Repositories\Contracts\RecipeRepositoryInterface;

class RecipeExplosionService
{
    public function __construct(
        protected RecipeRepositoryInterface $recipeRepository
    ) {}

    /**
     * Bóc tách toàn bộ nguyên vật liệu tiêu hao từ một dòng OrderItem (gồm món chính + size + topping kèm theo).
     *
     * @return array<int, ExplodedIngredientDTO> Danh sách DTO nguyên liệu được gộp theo ingredient_id
     */
    public function explodeOrderItem(OrderItem $orderItem): array
    {
        $orderItem->loadMissing([
            'menuItem',
            'variant',
            'modifiers.modifier.ingredient',
        ]);

        $itemQuantity = max(1, (int) $orderItem->quantity);

        // 1. Lấy danh sách nguyên vật liệu từ công thức món chính (BOM)
        $exploded = $this->explodeByRecipe(
            menuItemId: $orderItem->menu_item_id,
            variantId: $orderItem->variant_id,
            multiplier: $itemQuantity
        );

        // 2. Cộng dồn nguyên vật liệu phát sinh từ topping kèm theo (Modifiers)
        foreach ($orderItem->modifiers as $orderModifier) {
            $modifier = $orderModifier->modifier;
            if ($modifier === null || $modifier->ingredient_id === null || $modifier->ingredient === null) {
                continue;
            }

            $modQty = (float) ($modifier->ingredient_qty ?? 1.0) * (int) $orderModifier->quantity;
            $ingId = (int) $modifier->ingredient_id;

            if (isset($exploded[$ingId])) {
                $prev = $exploded[$ingId];
                $exploded[$ingId] = new ExplodedIngredientDTO(
                    ingredientId: $ingId,
                    sku: $prev->sku,
                    name: $prev->name,
                    quantity: $prev->quantity + $modQty,
                    baseUom: $prev->baseUom
                );
            } else {
                $ingredient = $modifier->ingredient;
                $exploded[$ingId] = new ExplodedIngredientDTO(
                    ingredientId: $ingId,
                    sku: $ingredient->sku,
                    name: $ingredient->name,
                    quantity: $modQty,
                    baseUom: $ingredient->base_uom
                );
            }
        }

        return array_values($exploded);
    }

    /**
     * Bóc tách nguyên liệu theo công thức BOM và nhân theo số lượng.
     *
     * @return array<int, ExplodedIngredientDTO> Key là ingredient_id
     */
    public function explodeByRecipe(int $menuItemId, ?int $variantId = null, int $multiplier = 1): array
    {
        $recipe = $this->recipeRepository->findActiveRecipe($menuItemId, $variantId);

        if ($recipe === null) {
            return [];
        }

        $result = [];

        foreach ($recipe->items as $item) {
            $ingredient = $item->ingredient;
            if ($ingredient === null) {
                continue;
            }

            $ingId = (int) $item->ingredient_id;
            $quantity = (float) $item->quantity * $multiplier;

            if (isset($result[$ingId])) {
                $prev = $result[$ingId];
                $result[$ingId] = new ExplodedIngredientDTO(
                    ingredientId: $ingId,
                    sku: $prev->sku,
                    name: $prev->name,
                    quantity: $prev->quantity + $quantity,
                    baseUom: $prev->baseUom
                );
            } else {
                $result[$ingId] = new ExplodedIngredientDTO(
                    ingredientId: $ingId,
                    sku: $ingredient->sku,
                    name: $ingredient->name,
                    quantity: $quantity,
                    baseUom: $ingredient->base_uom
                );
            }
        }

        return $result;
    }
}
