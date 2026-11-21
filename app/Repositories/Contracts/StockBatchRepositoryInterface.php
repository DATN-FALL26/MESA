<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\StockBatch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface StockBatchRepositoryInterface
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Lấy các lô hàng đang hoạt động của 1 nguyên liệu trong kho, sắp xếp theo FEFO (hạn sử dụng tăng dần).
     *
     * @return Collection<int, StockBatch>
     */
    public function getActiveBatchesFefo(int $warehouseId, int $ingredientId): Collection;

    /**
     * Lấy các lô sắp hết hạn.
     *
     * @return Collection<int, StockBatch>
     */
    public function getExpiringBatches(int $daysThreshold = 3, ?int $warehouseId = null): Collection;

    /**
     * Lấy các lô đã quá hạn sử dụng.
     *
     * @return Collection<int, StockBatch>
     */
    public function getExpiredBatches(?int $warehouseId = null): Collection;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): StockBatch;

    /**
     * Cập nhật số lượng và trạng thái lô hàng.
     */
    public function updateQuantity(StockBatch $batch, float $newQuantity): StockBatch;
}
