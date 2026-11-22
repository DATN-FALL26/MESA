<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\AdjustmentStatus;
use App\Models\StockAdjustment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StockAdjustmentRepositoryInterface
{
    /**
     * Lấy danh sách phiếu kiểm kê có phân trang và bộ lọc.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Lấy chi tiết phiếu kiểm kê kèm items, ingredient, warehouse, creator, approver.
     */
    public function findByIdWithItems(int $id): ?StockAdjustment;

    /**
     * Tra cứu theo mã phiếu kiểm kê.
     */
    public function findByAdjustmentNo(string $adjustmentNo): ?StockAdjustment;

    /**
     * Tạo mới phiếu kiểm kê kèm snapshot chi tiết mặt hàng.
     *
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $items
     */
    public function create(array $data, array $items): StockAdjustment;

    /**
     * Cập nhật danh sách số lượng thực tế kiểm kê.
     *
     * @param array<int, array<string, mixed>> $items
     */
    public function updateItems(StockAdjustment $adjustment, array $items): StockAdjustment;

    /**
     * Cập nhật trạng thái phiếu kiểm kê.
     *
     * @param array<string, mixed> $attributes
     */
    public function updateStatus(StockAdjustment $adjustment, AdjustmentStatus $status, array $attributes = []): StockAdjustment;

    /**
     * Tự động sinh mã phiếu kiểm kê: ADJ{YYYYMMDD}{SEQ4}.
     */
    public function generateAdjustmentNo(int $warehouseId): string;
}
