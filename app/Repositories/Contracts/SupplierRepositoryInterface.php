<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface SupplierRepositoryInterface
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * @return Collection<int, Supplier>
     */
    public function getActiveSuppliers(): Collection;

    public function findById(int $id): ?Supplier;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Supplier;

    /**
     * @param array<string, mixed> $data
     */
    public function update(Supplier $supplier, array $data): Supplier;

    public function delete(Supplier $supplier): bool;

    /**
     * Kiểm tra nhà cung cấp có đơn mua hàng (PO) liên quan hay không.
     */
    public function hasPurchaseOrders(int $supplierId): bool;
}
