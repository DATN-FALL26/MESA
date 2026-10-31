<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Ingredient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface IngredientRepositoryInterface
{
    /**
     * Lấy danh sách nguyên vật liệu có phân trang và bộ lọc.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Lấy toàn bộ nguyên vật liệu đang hoạt động để hiển thị dropdown hoặc BOM selector.
     *
     * @return Collection<int, Ingredient>
     */
    public function getActiveIngredients(): Collection;

    public function findById(int $id): ?Ingredient;

    public function findBySku(string $sku): ?Ingredient;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Ingredient;

    /**
     * @param array<string, mixed> $data
     */
    public function update(Ingredient $ingredient, array $data): Ingredient;

    public function delete(Ingredient $ingredient): bool;

    /**
     * Kiểm tra xem nguyên liệu có đang được sử dụng trong công thức active nào không.
     */
    public function isUsedInActiveRecipes(int $ingredientId): bool;

    /**
     * Kiểm tra xem nguyên liệu còn tồn kho tại bất kỳ kho nào không.
     */
    public function hasRemainingStock(int $ingredientId): bool;
}
