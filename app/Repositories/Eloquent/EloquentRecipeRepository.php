<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Recipe;
use App\Repositories\Contracts\RecipeRepositoryInterface;

class EloquentRecipeRepository implements RecipeRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function findActiveRecipe(int $menuItemId, ?int $variantId = null): ?Recipe
    {
        // 1. Ưu tiên tìm công thức active đúng theo variant (size lớn, nhỏ...)
        if ($variantId !== null) {
            $variantRecipe = Recipe::with(['items.ingredient'])
                ->where('menu_item_id', $menuItemId)
                ->where('variant_id', $variantId)
                ->where('is_active', true)
                ->orderBy('version', 'desc')
                ->first();

            if ($variantRecipe !== null) {
                return $variantRecipe;
            }
        }

        // 2. Fallback tìm công thức mặc định (variant_id IS NULL)
        return Recipe::with(['items.ingredient'])
            ->where('menu_item_id', $menuItemId)
            ->whereNull('variant_id')
            ->where('is_active', true)
            ->orderBy('version', 'desc')
            ->first();
    }
}
