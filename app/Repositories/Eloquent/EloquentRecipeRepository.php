<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Recipe;
use App\Repositories\Contracts\RecipeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

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

    /**
     * {@inheritDoc}
     */
    public function listByMenuItem(int $menuItemId, ?int $variantId = null): Collection
    {
        $query = Recipe::with(['items.ingredient', 'variant'])
            ->where('menu_item_id', $menuItemId);

        if ($variantId !== null) {
            $query->where('variant_id', $variantId);
        }

        return $query->orderBy('version', 'desc')->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findByIdWithItems(int $id): ?Recipe
    {
        return Recipe::with(['items.ingredient', 'menuItem', 'variant'])->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function getNextVersion(int $menuItemId, ?int $variantId = null): int
    {
        $query = Recipe::where('menu_item_id', $menuItemId);

        if ($variantId !== null) {
            $query->where('variant_id', $variantId);
        } else {
            $query->whereNull('variant_id');
        }

        $maxVersion = (int) $query->max('version');

        return $maxVersion + 1;
    }

    /**
     * {@inheritDoc}
     */
    public function deactivateAllVersions(int $menuItemId, ?int $variantId = null): void
    {
        $query = Recipe::where('menu_item_id', $menuItemId);

        if ($variantId !== null) {
            $query->where('variant_id', $variantId);
        } else {
            $query->whereNull('variant_id');
        }

        $query->update(['is_active' => false]);
    }
}
