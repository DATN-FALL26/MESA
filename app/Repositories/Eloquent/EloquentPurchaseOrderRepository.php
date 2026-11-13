<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentPurchaseOrderRepository implements PurchaseOrderRepositoryInterface
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PurchaseOrder::query()
            ->with(['branch', 'warehouse', 'supplier', 'creator', 'approver', 'receiver', 'items.ingredient'])
            ->withCount('items');

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', (int) $filters['warehouse_id']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', (int) $filters['supplier_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (!empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('po_no', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%");
            });
        }

        return $query->latest('id')->paginate($perPage);
    }

    public function findByIdWithItems(int $id): ?PurchaseOrder
    {
        return PurchaseOrder::query()
            ->with(['branch', 'warehouse', 'supplier', 'creator', 'approver', 'receiver', 'items.ingredient'])
            ->find($id);
    }

    public function findByPoNo(int $branchId, string $poNo): ?PurchaseOrder
    {
        return PurchaseOrder::query()
            ->with(['branch', 'warehouse', 'supplier', 'creator', 'approver', 'receiver', 'items.ingredient'])
            ->where('branch_id', $branchId)
            ->where('po_no', $poNo)
            ->first();
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $items
     */
    public function create(array $data, array $items): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $items) {
            if (empty($data['po_no'])) {
                $data['po_no'] = $this->generatePoNo((int) $data['branch_id']);
            }

            $po = PurchaseOrder::create($data);

            foreach ($items as $item) {
                $po->items()->create([
                    'ingredient_id' => $item['ingredient_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'] ?? 0,
                    'received_qty' => $item['received_qty'] ?? 0,
                ]);
            }

            return $po->load(['branch', 'warehouse', 'supplier', 'creator', 'items.ingredient']);
        });
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>>|null $items
     */
    public function update(PurchaseOrder $po, array $data, ?array $items = null): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $data, $items) {
            $po->update($data);

            if ($items !== null) {
                $po->items()->delete();
                foreach ($items as $item) {
                    $po->items()->create([
                        'ingredient_id' => $item['ingredient_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'] ?? 0,
                        'received_qty' => $item['received_qty'] ?? 0,
                    ]);
                }
            }

            return $po->fresh(['branch', 'warehouse', 'supplier', 'creator', 'items.ingredient']);
        });
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function updateStatus(PurchaseOrder $po, PurchaseOrderStatus $status, array $attributes = []): PurchaseOrder
    {
        $attributes['status'] = $status;
        $po->update($attributes);

        return $po->fresh(['branch', 'warehouse', 'supplier', 'creator', 'approver', 'receiver', 'items.ingredient']);
    }

    public function generatePoNo(int $branchId): string
    {
        $dateStr = Carbon::now()->format('Ymd');
        $prefix = "PO{$branchId}-{$dateStr}-";

        $lastOrder = PurchaseOrder::where('branch_id', $branchId)
            ->where('po_no', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $seq = 1;
        if ($lastOrder) {
            $parts = explode('-', $lastOrder->po_no);
            $lastSeq = (int) end($parts);
            $seq = $lastSeq + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
