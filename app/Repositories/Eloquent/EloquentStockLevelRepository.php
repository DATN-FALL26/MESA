<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\StockLevel;
use App\Repositories\Contracts\StockLevelRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentStockLevelRepository implements StockLevelRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function lockForUpdateOrCreate(int $warehouseId, int $ingredientId): StockLevel
    {
        // Kiểm tra xem đã tồn tại chưa
        $existing = StockLevel::where('warehouse_id', $warehouseId)
            ->where('ingredient_id', $ingredientId)
            ->first();

        if ($existing === null) {
            StockLevel::create([
                'warehouse_id' => $warehouseId,
                'ingredient_id' => $ingredientId,
                'quantity' => 0,
                'avg_cost' => 0,
            ]);
        }

        /** @var StockLevel $locked */
        $locked = StockLevel::where('warehouse_id', $warehouseId)
            ->where('ingredient_id', $ingredientId)
            ->lockForUpdate()
            ->firstOrFail();

        return $locked;
    }

    /**
     * {@inheritDoc}
     */
    public function findByWarehouseAndIngredient(int $warehouseId, int $ingredientId): ?StockLevel
    {
        return StockLevel::with(['ingredient', 'warehouse'])
            ->where('warehouse_id', $warehouseId)
            ->where('ingredient_id', $ingredientId)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function updateQuantityAndCost(StockLevel $stockLevel, float $newQuantity, float $newAvgCost): bool
    {
        return $stockLevel->update([
            'quantity' => number_format($newQuantity, 3, '.', ''),
            'avg_cost' => number_format($newAvgCost, 2, '.', ''),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getByWarehouse(int $warehouseId): Collection
    {
        return StockLevel::with(['ingredient'])
            ->where('warehouse_id', $warehouseId)
            ->orderBy('ingredient_id')
            ->get();
    }
}
