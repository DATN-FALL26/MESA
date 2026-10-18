<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Recipe;

interface RecipeRepositoryInterface
{
    /**
     * Tìm công thức định lượng (Recipe) đang hoạt động (is_active = true) cho một món ăn và biến thể.
     */
    public function findActiveRecipe(int $menuItemId, ?int $variantId = null): ?Recipe;
}
