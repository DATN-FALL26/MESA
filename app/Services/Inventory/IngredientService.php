<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Models\Ingredient;
use App\Repositories\Contracts\IngredientRepositoryInterface;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class IngredientService
{
    public function __construct(
        protected IngredientRepositoryInterface $ingredientRepository
    ) {}

    /**
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->ingredientRepository->paginate($filters, $perPage);
    }

    /**
     * @return Collection<int, Ingredient>
     */
    public function getActiveIngredients(): Collection
    {
        return $this->ingredientRepository->getActiveIngredients();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Ingredient
    {
        $data['sku'] = strtoupper(trim((string) $data['sku']));

        return $this->ingredientRepository->create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Ingredient $ingredient, array $data): Ingredient
    {
        if (isset($data['sku'])) {
            $data['sku'] = strtoupper(trim((string) $data['sku']));
        }

        return $this->ingredientRepository->update($ingredient, $data);
    }

    /**
     * Xóa mềm nguyên vật liệu với cơ chế Safe Guard bảo vệ dữ liệu.
     *
     * @throws DomainException
     */
    public function safeDelete(Ingredient $ingredient): bool
    {
        // Chặn xóa nếu nguyên liệu đang nằm trong công thức định lượng (Recipe) đang kích hoạt
        if ($this->ingredientRepository->isUsedInActiveRecipes($ingredient->id)) {
            throw new DomainException(
                "Không thể xóa nguyên liệu '{$ingredient->name}' vì đang được sử dụng trong công thức định lượng (BOM) đang hoạt động. Vui lòng gỡ nguyên liệu khỏi công thức trước."
            );
        }

        // Chặn xóa nếu nguyên liệu vẫn còn tồn kho > 0 tại bất kỳ kho nào
        if ($this->ingredientRepository->hasRemainingStock($ingredient->id)) {
            throw new DomainException(
                "Không thể xóa nguyên liệu '{$ingredient->name}' vì vẫn còn số lượng tồn kho trong hệ thống. Vui lòng xuất hủy hoặc cân đối kho trước."
            );
        }

        return $this->ingredientRepository->delete($ingredient);
    }
}
