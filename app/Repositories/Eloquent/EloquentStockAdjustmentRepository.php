<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\AdjustmentStatus;
use App\Models\StockAdjustment;
use App\Repositories\Contracts\StockAdjustmentRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentStockAdjustmentRepository implements StockAdjustmentRepositoryInterface
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = StockAdjustment::query()
            ->with(['warehouse.branch', 'creator', 'approver', 'items.ingredient'])
            ->withCount('items');

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', (int) $filters['warehouse_id']);
        }

        if (!empty($filters['reason'])) {
            $query->where('reason', $filters['reason']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('adjustment_no', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%");
            });
        }

        return $query->latest('id')->paginate($perPage);
    }

    public function findByIdWithItems(int $id): ?StockAdjustment
    {
        return StockAdjustment::query()
            ->with(['warehouse.branch', 'creator', 'approver', 'items.ingredient'])
            ->find($id);
    }

    public function findByAdjustmentNo(string $adjustmentNo): ?StockAdjustment
    {
        return StockAdjustment::query()
            ->with(['warehouse.branch', 'creator', 'approver', 'items.ingredient'])
            ->where('adjustment_no', $adjustmentNo)
            ->first();
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $items
     */
    public function create(array $data, array $items): StockAdjustment
    {
        return DB::transaction(function () use ($data, $items) {
            if (empty($data['adjustment_no'])) {
                $data['adjustment_no'] = $this->generateAdjustmentNo((int) $data['warehouse_id']);
            }

            $adjustment = StockAdjustment::create($data);

            foreach ($items as $item) {
                $adjustment->items()->create([
                    'ingredient_id' => $item['ingredient_id'],
                    'system_qty' => $item['system_qty'],
                    'actual_qty' => $item['actual_qty'] ?? $item['system_qty'],
                    'note' => $item['note'] ?? null,
                ]);
            }

            return $adjustment->load(['warehouse.branch', 'creator', 'items.ingredient']);
        });
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function updateItems(StockAdjustment $adjustment, array $items): StockAdjustment
    {
        return DB::transaction(function () use ($adjustment, $items) {
            foreach ($items as $item) {
                if (isset($item['id'])) {
                    $adjustment->items()->where('id', $item['id'])->update([
                        'actual_qty' => $item['actual_qty'],
                        'note' => $item['note'] ?? null,
                    ]);
                } elseif (isset($item['ingredient_id'])) {
                    $adjustment->items()->where('ingredient_id', $item['ingredient_id'])->update([
                        'actual_qty' => $item['actual_qty'],
                        'note' => $item['note'] ?? null,
                    ]);
                }
            }

            return $adjustment->fresh(['warehouse.branch', 'creator', 'items.ingredient']);
        });
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function updateStatus(StockAdjustment $adjustment, AdjustmentStatus $status, array $attributes = []): StockAdjustment
    {
        $attributes['status'] = $status;
        $adjustment->update($attributes);

        return $adjustment->fresh(['warehouse.branch', 'creator', 'approver', 'items.ingredient']);
    }

    public function generateAdjustmentNo(int $warehouseId): string
    {
        $dateStr = Carbon::now()->format('Ymd');
        $prefix = "ADJ{$warehouseId}-{$dateStr}-";

        $lastAdj = StockAdjustment::where('warehouse_id', $warehouseId)
            ->where('adjustment_no', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $seq = 1;
        if ($lastAdj) {
            $parts = explode('-', $lastAdj->adjustment_no);
            $lastSeq = (int) end($parts);
            $seq = $lastSeq + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
