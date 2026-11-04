<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Ingredient;
use App\Models\StockLevel;
use App\Repositories\Contracts\IngredientRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentIngredientRepository implements IngredientRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Ingredient::query();

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['ingredient_type'])) {
            $query->where('ingredient_type', $filters['ingredient_type']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveIngredients(): Collection
    {
        return Ingredient::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $id): ?Ingredient
    {
        return Ingredient::query()->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findBySku(string $sku): ?Ingredient
    {
        return Ingredient::query()->where('sku', $sku)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Ingredient
    {
        return Ingredient::query()->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(Ingredient $ingredient, array $data): Ingredient
    {
        $ingredient->update($data);

        return $ingredient->fresh();
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Ingredient $ingredient): bool
    {
        return (bool) $ingredient->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function isUsedInActiveRecipes(int $ingredientId): bool
    {
        return Ingredient::query()
            ->where('id', $ingredientId)
            ->whereHas('recipeItems.recipe', function ($query) {
                $query->where('is_active', true);
            })
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function hasRemainingStock(int $ingredientId): bool
    {
        return StockLevel::query()
            ->where('ingredient_id', $ingredientId)
            ->where('quantity', '>', 0)
            ->exists();
    }
}
