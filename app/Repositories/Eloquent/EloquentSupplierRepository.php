<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentSupplierRepository implements SupplierRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Supplier::query();

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('contact', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveSuppliers(): Collection
    {
        return Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $id): ?Supplier
    {
        return Supplier::query()->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Supplier
    {
        return Supplier::query()->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);

        return $supplier->fresh();
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Supplier $supplier): bool
    {
        return (bool) $supplier->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function hasPurchaseOrders(int $supplierId): bool
    {
        return PurchaseOrder::query()
            ->where('supplier_id', $supplierId)
            ->exists();
    }
}
