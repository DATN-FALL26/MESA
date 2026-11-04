<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\DTOs\Inventory\StockMovementDTO;
use App\Models\StockMovement;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentStockMovementRepository implements StockMovementRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(StockMovementDTO $dto): StockMovement
    {
        return StockMovement::create($dto->toArray());
    }

    /**
     * {@inheritDoc}
     */
    public function findByIdempotencyKey(string $idempotencyKey): ?StockMovement
    {
        return StockMovement::where('idempotency_key', $idempotencyKey)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getLedgerByIngredient(
        int $warehouseId,
        int $ingredientId,
        ?string $fromDate = null,
        ?string $toDate = null
    ): Collection {
        $query = StockMovement::query()
            ->with(['creator', 'ingredient', 'warehouse'])
            ->where('warehouse_id', $warehouseId)
            ->where('ingredient_id', $ingredientId);

        if ($fromDate !== null) {
            $query->where('created_at', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $query->where('created_at', '<=', $toDate);
        }

        return $query->orderBy('created_at', 'asc')->get();
    }
}
