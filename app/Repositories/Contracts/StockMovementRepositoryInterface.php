<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTOs\Inventory\StockMovementDTO;
use App\Models\StockMovement;
use Illuminate\Support\Collection;

interface StockMovementRepositoryInterface
{
    /**
     * Tạo một bút toán mới trên sổ cái biến động kho.
     */
    public function create(StockMovementDTO $dto): StockMovement;

    /**
     * Tìm bản ghi theo khóa chống trùng lặp (Idempotency Key).
     */
    public function findByIdempotencyKey(string $idempotencyKey): ?StockMovement;

    /**
     * Lấy danh sách lịch sử biến động kho (Thẻ kho) của một nguyên liệu.
     *
     * @return Collection<int, StockMovement>
     */
    public function getLedgerByIngredient(
        int $warehouseId,
        int $ingredientId,
        ?string $fromDate = null,
        ?string $toDate = null
    ): Collection;
}
