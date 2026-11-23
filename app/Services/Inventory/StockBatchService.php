<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Enums\StockBatchStatus;
use App\Models\StockBatch;
use App\Repositories\Contracts\StockBatchRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockBatchService
{
    public function __construct(
        protected StockBatchRepositoryInterface $batchRepository
    ) {}

    /**
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->batchRepository->paginate($filters, $perPage);
    }

    /**
     * Tạo một lô hàng mới khi nhập kho (PO hoặc Bếp nhập).
     *
     * @param array<string, mixed> $data
     */
    public function createBatch(array $data): StockBatch
    {
        $data['initial_quantity'] = (float) ($data['quantity'] ?? $data['initial_quantity'] ?? 0);
        $data['current_quantity'] = (float) ($data['current_quantity'] ?? $data['initial_quantity']);
        $data['import_date'] = $data['import_date'] ?? Carbon::today()->toDateString();
        
        // Nếu không truyền hạn sử dụng, tính mặc định theo 30 ngày (hoặc ngày chỉ định)
        if (empty($data['expiry_date'])) {
            $data['expiry_date'] = Carbon::parse($data['import_date'])->addDays(30)->toDateString();
        }

        $data['status'] = StockBatchStatus::ACTIVE;

        return $this->batchRepository->create($data);
    }

    /**
     * Xuất trừ kho theo nguyên tắc FEFO (First Expired, First Out - Lô hết hạn trước xuất trước).
     * Tự động trừ tuần tự từng lô đang hoạt động với Khóa bi quan chống Race Condition.
     *
     * @return array<int, array{batch_id: int, batch_code: string, deducted_qty: float, remaining_batch_qty: float}>
     */
    public function deductFefo(int $warehouseId, int $ingredientId, float $deductQuantity): array
    {
        if ($deductQuantity <= 0) {
            return [];
        }

        return DB::transaction(function () use ($warehouseId, $ingredientId, $deductQuantity): array {
            $batches = $this->batchRepository->getActiveBatchesFefo($warehouseId, $ingredientId);
            $remainingToDeduct = $deductQuantity;
            $allocations = [];

            foreach ($batches as $batch) {
                if ($remainingToDeduct <= 0) {
                    break;
                }

                $batchQty = (float) $batch->current_quantity;
                if ($batchQty <= 0) {
                    continue;
                }

                $deductFromThisBatch = min($batchQty, $remainingToDeduct);
                $newBatchQty = $batchQty - $deductFromThisBatch;

                $this->batchRepository->updateQuantity($batch, $newBatchQty);

                $allocations[] = [
                    'batch_id' => (int) $batch->id,
                    'batch_code' => (string) $batch->batch_code,
                    'deducted_qty' => $deductFromThisBatch,
                    'remaining_batch_qty' => $newBatchQty,
                ];

                $remainingToDeduct -= $deductFromThisBatch;
            }

            if ($remainingToDeduct > 0) {
                Log::notice('Xuất kho vượt quá tổng tồn của các lô hiện có (Unallocated remainder):', [
                    'warehouse_id' => $warehouseId,
                    'ingredient_id' => $ingredientId,
                    'requested' => $deductQuantity,
                    'unallocated' => $remainingToDeduct,
                ]);
            }

            return $allocations;
        });
    }

    /**
     * @return Collection<int, StockBatch>
     */
    public function getExpiringBatches(int $daysThreshold = 3, ?int $warehouseId = null): Collection
    {
        return $this->batchRepository->getExpiringBatches($daysThreshold, $warehouseId);
    }

    /**
     * @return Collection<int, StockBatch>
     */
    public function getExpiredBatches(?int $warehouseId = null): Collection
    {
        return $this->batchRepository->getExpiredBatches($warehouseId);
    }
}
