<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\DTOs\Inventory\StockMovementDTO;
use App\Enums\MovementType;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Repositories\Contracts\StockLevelRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockLedgerService
{
    public function __construct(
        protected StockMovementRepositoryInterface $movementRepository,
        protected StockLevelRepositoryInterface $levelRepository,
        protected CostCalculationService $costCalculator,
    ) {}

    /**
     * Ghi nhận một biến động kho vào sổ cái bất biến (Immutable Ledger) và cập nhật cache tồn tức thời.
     * Toàn bộ thao tác được bọc trong DB Transaction với Pessimistic Lock trên dòng tồn kho.
     */
    public function recordMovement(StockMovementDTO $dto): StockMovement
    {
        // 1. Kiểm tra khóa chống trùng lặp (Idempotency Check)
        if ($dto->idempotencyKey !== null) {
            $existing = $this->movementRepository->findByIdempotencyKey($dto->idempotencyKey);
            if ($existing !== null) {
                Log::info('Phát hiện giao dịch kho trùng lặp qua idempotency_key, bỏ qua ghi sổ:', [
                    'idempotency_key' => $dto->idempotencyKey,
                    'movement_id' => $existing->id,
                ]);
                return $existing;
            }
        }

        // 2. Thực thi trong Transaction với Khóa bi quan (Pessimistic Lock)
        return DB::transaction(function () use ($dto): StockMovement {
            // Khóa dòng tồn kho để chống Race Condition khi nhiều tiến trình cùng xuất/nhập
            $stockLevel = $this->levelRepository->lockForUpdateOrCreate(
                $dto->warehouseId,
                $dto->ingredientId
            );

            $currentQty = (float) $stockLevel->quantity;
            $currentAvgCost = (float) $stockLevel->avg_cost;

            // Tính toán số lượng tồn kho mới
            $newQuantity = $currentQty + $dto->quantity;

            // Tính toán giá vốn bình quân mới nếu là nhập kho (purchase_in)
            $newAvgCost = $currentAvgCost;
            if ($dto->movementType === MovementType::PURCHASE_IN && $dto->unitCost !== null && $dto->quantity > 0) {
                $newAvgCost = $this->costCalculator->calculateWeightedAverageCost(
                    currentQty: $currentQty,
                    currentAvgCost: $currentAvgCost,
                    inboundQty: $dto->quantity,
                    inboundUnitCost: $dto->unitCost
                );
            }

            // Cập nhật bảng cache tồn kho (stock_levels)
            $this->levelRepository->updateQuantityAndCost(
                $stockLevel,
                $newQuantity,
                $newAvgCost
            );

            // Ghi nhận dòng phát sinh mới vào sổ cái bất biến (stock_movements)
            $movement = $this->movementRepository->create($dto);

            return $movement;
        });
    }

    /**
     * Lấy thẻ kho chi tiết của một nguyên liệu theo khoảng thời gian.
     *
     * @return Collection<int, StockMovement>
     */
    public function getLedgerCard(
        int $warehouseId,
        int $ingredientId,
        ?string $fromDate = null,
        ?string $toDate = null
    ): Collection {
        return $this->movementRepository->getLedgerByIngredient(
            $warehouseId,
            $ingredientId,
            $fromDate,
            $toDate
        );
    }

    /**
     * Lấy mức tồn kho hiện thời của nguyên liệu.
     */
    public function getStockLevel(int $warehouseId, int $ingredientId): ?StockLevel
    {
        return $this->levelRepository->findByWarehouseAndIngredient($warehouseId, $ingredientId);
    }
}
