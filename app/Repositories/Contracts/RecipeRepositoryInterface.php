<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Recipe;
use Illuminate\Database\Eloquent\Collection;

interface RecipeRepositoryInterface
{
    /**
     * Tìm công thức định lượng (Recipe) đang hoạt động (is_active = true) cho một món ăn và biến thể.
     */
    public function findActiveRecipe(int $menuItemId, ?int $variantId = null): ?Recipe;

    /**
     * Lấy danh sách toàn bộ các phiên bản công thức của một món ăn (kèm items và nguyên liệu).
     *
     * @return Collection<int, Recipe>
     */
    public function listByMenuItem(int $menuItemId, ?int $variantId = null): Collection;

    /**
     * Tìm công thức theo ID kèm các nguyên liệu chi tiết.
     */
    public function findByIdWithItems(int $id): ?Recipe;

    /**
     * Xác định số phiên bản tiếp theo (max version + 1) cho món ăn và biến thể.
     */
    public function getNextVersion(int $menuItemId, ?int $variantId = null): int;

    /**
     * Hủy kích hoạt (is_active = false) tất cả phiên bản hiện hành của món ăn và biến thể.
     */
    public function deactivateAllVersions(int $menuItemId, ?int $variantId = null): void;
}
