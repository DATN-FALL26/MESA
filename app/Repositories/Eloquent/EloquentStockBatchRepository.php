<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\StockBatchStatus;
use App\Models\StockBatch;
use App\Repositories\Contracts\StockBatchRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EloquentStockBatchRepository implements StockBatchRepositoryInterface
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = StockBatch::query()->with(['warehouse.branch', 'ingredient', 'supplier']);

        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', (int) $filters['warehouse_id']);
        }

        if (! empty($filters['branch_id'])) {
            $query->whereHas('warehouse', function (Builder $q) use ($filters) {
                $q->where('branch_id', (int) $filters['branch_id']);
            });
        }

        if (! empty($filters['ingredient_id'])) {
            $query->where('ingredient_id', (int) $filters['ingredient_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('batch_code', 'like', "%{$search}%")
                    ->orWhereHas('ingredient', function (Builder $sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    });
            });
        }

        // Lọc theo hạn sử dụng
        if (! empty($filters['expiry_filter'])) {
            $today = Carbon::today()->toDateString();
            if ($filters['expiry_filter'] === 'expired') {
                $query->expired();
            } elseif ($filters['expiry_filter'] === 'expiring_soon') {
                $days = (int) ($filters['days_threshold'] ?? 3);
                $query->expiringSoon($days);
            }
        }

        return $query->orderBy('expiry_date', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, StockBatch>
     */
    public function getActiveBatchesFefo(int $warehouseId, int $ingredientId): Collection
    {
        return StockBatch::query()
            ->where('warehouse_id', $warehouseId)
            ->where('ingredient_id', $ingredientId)
            ->where('status', StockBatchStatus::ACTIVE)
            ->where('current_quantity', '>', 0)
            ->orderBy('expiry_date', 'asc')
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();
    }

    /**
     * @return Collection<int, StockBatch>
     */
    public function getExpiringBatches(int $daysThreshold = 3, ?int $warehouseId = null): Collection
    {
        $query = StockBatch::query()
            ->with(['ingredient', 'warehouse.branch'])
            ->expiringSoon($daysThreshold);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->orderBy('expiry_date', 'asc')->get();
    }

    /**
     * @return Collection<int, StockBatch>
     */
    public function getExpiredBatches(?int $warehouseId = null): Collection
    {
        $query = StockBatch::query()
            ->with(['ingredient', 'warehouse.branch'])
            ->expired();

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->orderBy('expiry_date', 'asc')->get();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): StockBatch
    {
        if (empty($data['batch_code'])) {
            $todayStr = Carbon::today()->format('Ymd');
            $randomStr = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
            $data['batch_code'] = "LOT-{$todayStr}-{$randomStr}";
        }

        return StockBatch::create($data);
    }

    public function updateQuantity(StockBatch $batch, float $newQuantity): StockBatch
    {
        $newQuantity = max(0, $newQuantity);
        $status = $newQuantity <= 0 ? StockBatchStatus::DEPLETED : $batch->status;

        $batch->update([
            'current_quantity' => $newQuantity,
            'status' => $status,
        ]);

        return $batch;
    }
}
