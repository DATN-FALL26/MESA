<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Repositories\Contracts\RecipeRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RecipeManagementService
{
    public function __construct(
        protected RecipeRepositoryInterface $recipeRepository
    ) {}

    /**
     * Tạo một phiên bản công thức (BOM) mới cho món ăn / biến thể.
     *
     * @param array<int, array{ingredient_id: int, quantity: float|string}> $items
     */
    public function createRecipeVersion(
        int $menuItemId,
        ?int $variantId,
        array $items,
        bool $activateImmediately = true
    ): Recipe {
        if (empty($items)) {
            throw new InvalidArgumentException('Công thức định lượng phải có ít nhất 1 nguyên vật liệu.');
        }

        return DB::transaction(function () use ($menuItemId, $variantId, $items, $activateImmediately): Recipe {
            // Xác định version tiếp theo
            $nextVersion = $this->recipeRepository->getNextVersion($menuItemId, $variantId);

            if ($activateImmediately) {
                // Tắt kích hoạt tất cả các phiên bản trước đó của cùng món & size
                $this->recipeRepository->deactivateAllVersions($menuItemId, $variantId);
            }

            // Tạo Header Recipe mới
            $recipe = Recipe::query()->create([
                'menu_item_id' => $menuItemId,
                'variant_id' => $variantId,
                'version' => $nextVersion,
                'is_active' => $activateImmediately,
            ]);

            // Thêm các dòng chi tiết nguyên vật liệu
            $itemRows = [];
            $now = now();
            foreach ($items as $item) {
                $itemRows[] = [
                    'recipe_id' => $recipe->id,
                    'ingredient_id' => (int) $item['ingredient_id'],
                    'quantity' => (string) $item['quantity'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            RecipeItem::query()->insert($itemRows);

            return $recipe->fresh(['items.ingredient', 'menuItem', 'variant']);
        });
    }

    /**
     * Kích hoạt một phiên bản công thức cụ thể thành phiên bản chính thức (is_active = true).
     */
    public function activateVersion(int $recipeId): Recipe
    {
        return DB::transaction(function () use ($recipeId): Recipe {
            $recipe = Recipe::query()->lockForUpdate()->findOrFail($recipeId);

            if ($recipe->is_active) {
                return $recipe->load(['items.ingredient', 'menuItem', 'variant']);
            }

            // Tắt active các phiên bản khác của cùng món ăn & size
            $this->recipeRepository->deactivateAllVersions(
                (int) $recipe->menu_item_id,
                $recipe->variant_id !== null ? (int) $recipe->variant_id : null
            );

            // Bật active cho phiên bản chỉ định
            $recipe->update(['is_active' => true]);

            return $recipe->fresh(['items.ingredient', 'menuItem', 'variant']);
        });
    }

    /**
     * Nhân bản một phiên bản công thức thành phiên bản dự thảo mới (draft version).
     */
    public function cloneAsDraft(int $sourceRecipeId): Recipe
    {
        $sourceRecipe = Recipe::with('items')->findOrFail($sourceRecipeId);

        $items = $sourceRecipe->items->map(fn ($item) => [
            'ingredient_id' => (int) $item->ingredient_id,
            'quantity' => (string) $item->quantity,
        ])->toArray();

        return $this->createRecipeVersion(
            menuItemId: (int) $sourceRecipe->menu_item_id,
            variantId: $sourceRecipe->variant_id !== null ? (int) $sourceRecipe->variant_id : null,
            items: $items,
            activateImmediately: false
        );
    }
}
