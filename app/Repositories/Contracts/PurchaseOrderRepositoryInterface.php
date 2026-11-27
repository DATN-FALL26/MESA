<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PurchaseOrderRepositoryInterface
{
    /**
     * Lấy danh sách đơn nhập hàng có phân trang và bộ lọc.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Lấy chi tiết đơn nhập hàng kèm các quan hệ liên quan (items, ingredient, branch, warehouse, supplier, creator, approver, receiver).
     */
    public function findByIdWithItems(int $id): ?PurchaseOrder;

    /**
     * Tra cứu theo mã số PO và chi nhánh.
     */
    public function findByPoNo(int $branchId, string $poNo): ?PurchaseOrder;

    /**
     * Tạo mới đơn nhập hàng kèm danh sách chi tiết mặt hàng.
     *
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $items
     */
    public function create(array $data, array $items): PurchaseOrder;

    /**
     * Cập nhật thông tin đơn nhập hàng (chỉ khi đang ở trạng thái nháp).
     *
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>>|null $items
     */
    public function update(PurchaseOrder $po, array $data, ?array $items = null): PurchaseOrder;

    /**
     * Cập nhật trạng thái đơn nhập hàng và các thông tin phê duyệt / tiếp nhận.
     *
     * @param array<string, mixed> $attributes
     */
    public function updateStatus(PurchaseOrder $po, PurchaseOrderStatus $status, array $attributes = []): PurchaseOrder;

    /**
     * Tự động sinh mã PO duy nhất theo chi nhánh và ngày: PO{BRANCH_ID}{YYYYMMDD}{SEQ4}.
     */
    public function generatePoNo(int $branchId): string;
}
