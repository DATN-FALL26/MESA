<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\StockLevel;
use Illuminate\Support\Collection;

interface StockLevelRepositoryInterface
{
    /**
     * Khóa dòng dữ liệu tồn kho bằng Pessimistic Lock (SELECT ... FOR UPDATE).
     * Nếu chưa có bản ghi thì tự động tạo mới với số lượng = 0.
     */
    public function lockForUpdateOrCreate(int $warehouseId, int $ingredientId): StockLevel;

    /**
     * Tra cứu mức tồn kho hiện thời (Read-only, không lock).
     */
    public function findByWarehouseAndIngredient(int $warehouseId, int $ingredientId): ?StockLevel;

    /**
     * Cập nhật số lượng tồn và giá vốn bình quân mới cho mức tồn kho.
     */
    public function updateQuantityAndCost(StockLevel $stockLevel, float $newQuantity, float $newAvgCost): bool;

    /**
     * Lấy toàn bộ danh sách mức tồn kho của một kho hàng.
     *
     * @return Collection<int, StockLevel>
     */
    public function getByWarehouse(int $warehouseId): Collection;
}
