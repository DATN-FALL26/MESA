<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\AlertType;
use App\Enums\NotificationType;
use App\Enums\StockBatchStatus;
use App\Helpers\ConstantHelper;
use App\Models\AiAlert;
use App\Models\Branch;
use App\Models\Notification;
use App\Models\StockBatch;
use App\Models\StockLevel;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockAlertService
{
    /**
     * Quét và tạo cảnh báo Hết hàng / Dưới mức tồn kho tối thiểu.
     * Tối ưu hóa truy vấn hiệu năng cao với DB join trực tiếp.
     *
     * @return array<string, int> ['created' => int, 'updated' => int]
     */
    public function scanLowStockAlerts(?int $branchId = null): array
    {
        $query = DB::table(ConstantHelper::TABLE_STOCK_LEVELS.' as sl')
            ->join(ConstantHelper::TABLE_INGREDIENTS.' as ing', 'sl.ingredient_id', '=', 'ing.id')
            ->join(ConstantHelper::TABLE_WAREHOUSES.' as wh', 'sl.warehouse_id', '=', 'wh.id')
            ->where('ing.is_active', true)
            ->whereNull('ing.deleted_at')
            ->where('wh.is_active', true)
            ->whereRaw('sl.quantity <= ing.min_stock')
            ->select([
                'sl.id as stock_level_id',
                'sl.warehouse_id',
                'wh.branch_id',
                'wh.name as warehouse_name',
                'ing.id as ingredient_id',
                'ing.sku',
                'ing.name as ingredient_name',
                'ing.base_uom',
                'ing.min_stock',
                'sl.quantity',
                'sl.avg_cost',
            ]);

        if ($branchId !== null) {
            $query->where('wh.branch_id', $branchId);
        }

        $records = $query->get();
        $created = 0;
        $updated = 0;

        foreach ($records as $item) {
            $currentQty = (float) $item->quantity;
            $minStock = (float) $item->min_stock;
            $isOutOfStock = $currentQty <= 0;

            $alertType = $isOutOfStock ? AlertType::OUT_OF_STOCK : AlertType::LOW_STOCK_RISK;
            $severity = $isOutOfStock ? AlertSeverity::CRITICAL : AlertSeverity::WARNING;

            $message = $isOutOfStock
                ? "Nguyên liệu '{$item->ingredient_name}' ({$item->sku}) tại kho {$item->warehouse_name} đã HẾT HÀNG (tồn: {$currentQty} {$item->base_uom})."
                : "Nguyên liệu '{$item->ingredient_name}' ({$item->sku}) tại kho {$item->warehouse_name} sắp hết hàng: còn {$currentQty}/{$minStock} {$item->base_uom}.";

            $payload = [
                'ingredient_id' => $item->ingredient_id,
                'ingredient_name' => $item->ingredient_name,
                'sku' => $item->sku,
                'warehouse_id' => $item->warehouse_id,
                'warehouse_name' => $item->warehouse_name,
                'current_qty' => $currentQty,
                'min_stock' => $minStock,
                'base_uom' => $item->base_uom,
                'avg_cost' => (float) $item->avg_cost,
                'message' => $message,
                'detected_at' => now()->toIso8601String(),
            ];

            // Tìm hoặc tạo bản ghi cảnh báo trong ai_alerts
            $existingAlert = AiAlert::query()
                ->where('branch_id', $item->branch_id)
                ->where('ingredient_id', $item->ingredient_id)
                ->whereIn('alert_type', [AlertType::LOW_STOCK_RISK, AlertType::OUT_OF_STOCK])
                ->where('status', AlertStatus::NEW)
                ->first();

            if ($existingAlert) {
                $existingAlert->update([
                    'alert_type' => $alertType,
                    'severity' => $severity,
                    'payload' => $payload,
                ]);
                $updated++;
            } else {
                AiAlert::query()->create([
                    'branch_id' => $item->branch_id,
                    'alert_type' => $alertType,
                    'severity' => $severity,
                    'ingredient_id' => $item->ingredient_id,
                    'payload' => $payload,
                    'status' => AlertStatus::NEW,
                ]);
                $created++;

                // Bắn thông báo Notification đến hệ thống
                $this->createNotification(
                    branchId: (int) $item->branch_id,
                    type: NotificationType::AI_ALERT,
                    title: $isOutOfStock ? "Cảnh báo Hết hàng: {$item->ingredient_name}" : "Cảnh báo Sắp hết hàng: {$item->ingredient_name}",
                    content: $message,
                    payload: $payload
                );
            }
        }

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * Quét và tạo cảnh báo Nguyên liệu sắp hết hạn (trong vòng N ngày) hoặc đã quá hạn sử dụng.
     *
     * @return array<string, int>
     */
    public function scanExpiryAlerts(int $daysThreshold = 3, ?int $branchId = null): array
    {
        $today = Carbon::today()->toDateString();
        $targetDate = Carbon::today()->addDays($daysThreshold)->toDateString();

        $query = DB::table(ConstantHelper::TABLE_STOCK_BATCHES.' as sb')
            ->join(ConstantHelper::TABLE_INGREDIENTS.' as ing', 'sb.ingredient_id', '=', 'ing.id')
            ->join(ConstantHelper::TABLE_WAREHOUSES.' as wh', 'sb.warehouse_id', '=', 'wh.id')
            ->where('sb.status', StockBatchStatus::ACTIVE->value)
            ->where('sb.current_quantity', '>', 0)
            ->where('sb.expiry_date', '<=', $targetDate)
            ->select([
                'sb.id as batch_id',
                'sb.batch_code',
                'sb.warehouse_id',
                'wh.branch_id',
                'wh.name as warehouse_name',
                'ing.id as ingredient_id',
                'ing.sku',
                'ing.name as ingredient_name',
                'ing.base_uom',
                'sb.current_quantity',
                'sb.import_date',
                'sb.expiry_date',
            ]);

        if ($branchId !== null) {
            $query->where('wh.branch_id', $branchId);
        }

        $records = $query->get();
        $created = 0;
        $updated = 0;

        foreach ($records as $batch) {
            $isExpired = $batch->expiry_date < $today;
            $alertType = $isExpired ? AlertType::EXPIRED : AlertType::EXPIRING_SOON;
            $severity = $isExpired ? AlertSeverity::CRITICAL : AlertSeverity::WARNING;

            $daysLeft = (int) Carbon::today()->diffInDays(Carbon::parse($batch->expiry_date), false);

            $message = $isExpired
                ? "Lô hàng '{$batch->batch_code}' của nguyên liệu '{$batch->ingredient_name}' tại kho {$batch->warehouse_name} ĐÃ HẾT HẠN (HSD: {$batch->expiry_date}, còn {$batch->current_quantity} {$batch->base_uom}). Yêu cầu xuất hủy ngay."
                : "Lô hàng '{$batch->batch_code}' của nguyên liệu '{$batch->ingredient_name}' sẽ HẾT HẠN TRONG {$daysLeft} NGÀY (HSD: {$batch->expiry_date}, còn {$batch->current_quantity} {$batch->base_uom}). Ưu tiên sử dụng trước.";

            $payload = [
                'batch_id' => $batch->batch_id,
                'batch_code' => $batch->batch_code,
                'ingredient_id' => $batch->ingredient_id,
                'ingredient_name' => $batch->ingredient_name,
                'warehouse_id' => $batch->warehouse_id,
                'warehouse_name' => $batch->warehouse_name,
                'current_qty' => (float) $batch->current_quantity,
                'base_uom' => $batch->base_uom,
                'import_date' => $batch->import_date,
                'expiry_date' => $batch->expiry_date,
                'days_left' => $daysLeft,
                'message' => $message,
                'detected_at' => now()->toIso8601String(),
            ];

            // Kiểm tra trùng cảnh báo cho lô này
            $existingAlert = AiAlert::query()
                ->where('branch_id', $batch->branch_id)
                ->where('ingredient_id', $batch->ingredient_id)
                ->whereJsonContains('payload->batch_id', $batch->batch_id)
                ->where('status', AlertStatus::NEW)
                ->first();

            if ($existingAlert) {
                $existingAlert->update([
                    'alert_type' => $alertType,
                    'severity' => $severity,
                    'payload' => $payload,
                ]);
                $updated++;
            } else {
                AiAlert::query()->create([
                    'branch_id' => $batch->branch_id,
                    'alert_type' => $alertType,
                    'severity' => $severity,
                    'ingredient_id' => $batch->ingredient_id,
                    'payload' => $payload,
                    'status' => AlertStatus::NEW,
                ]);
                $created++;

                $this->createNotification(
                    branchId: (int) $batch->branch_id,
                    type: NotificationType::AI_ALERT,
                    title: $isExpired ? "Hết hạn: Lô {$batch->batch_code} ({$batch->ingredient_name})" : "Sắp hết hạn: Lô {$batch->batch_code} ({$batch->ingredient_name})",
                    content: $message,
                    payload: $payload
                );
            }
        }

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * Bảng tổng hợp tồn kho và trạng thái chất lượng theo chi nhánh.
     * Thực thi 1 truy vấn tối ưu kết hợp tồn kho và HSD gần nhất.
     *
     * @param array<string, mixed> $filters
     */
    public function getBranchStockOverview(int $branchId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $today = Carbon::today()->toDateString();
        $threeDaysAhead = Carbon::today()->addDays(3)->toDateString();

        // 1. Xác định kho chính của chi nhánh
        $warehouseIds = Warehouse::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->pluck('id');

        // Subquery lấy hạn sử dụng sớm nhất của các lô còn tồn
        $earliestBatchSubquery = DB::table(ConstantHelper::TABLE_STOCK_BATCHES)
            ->select('ingredient_id', 'warehouse_id', DB::raw('MIN(expiry_date) as earliest_expiry'), DB::raw('COUNT(id) as active_batches_count'))
            ->where('status', StockBatchStatus::ACTIVE->value)
            ->where('current_quantity', '>', 0)
            ->groupBy('ingredient_id', 'warehouse_id');

        $query = DB::table(ConstantHelper::TABLE_INGREDIENTS.' as ing')
            ->leftJoin(ConstantHelper::TABLE_STOCK_LEVELS.' as sl', function ($join) use ($warehouseIds) {
                $join->on('ing.id', '=', 'sl.ingredient_id')
                    ->whereIn('sl.warehouse_id', $warehouseIds);
            })
            ->leftJoinSub($earliestBatchSubquery, 'b', function ($join) {
                $join->on('ing.id', '=', 'b.ingredient_id')
                    ->on('sl.warehouse_id', '=', 'b.warehouse_id');
            })
            ->where('ing.is_active', true)
            ->whereNull('ing.deleted_at')
            ->select([
                'ing.id as ingredient_id',
                'ing.sku',
                'ing.name',
                'ing.ingredient_type',
                'ing.base_uom',
                'ing.min_stock',
                DB::raw('COALESCE(sl.quantity, 0) as current_quantity'),
                DB::raw('COALESCE(sl.avg_cost, 0) as avg_cost'),
                DB::raw('ROUND(COALESCE(sl.quantity, 0) * COALESCE(sl.avg_cost, 0), 0) as total_value'),
                'b.earliest_expiry',
                DB::raw('COALESCE(b.active_batches_count, 0) as active_batches_count'),
            ]);

        // Lọc theo từ khóa (Tên hoặc SKU)
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('ing.name', 'like', "%{$search}%")
                    ->orWhere('ing.sku', 'like', "%{$search}%");
            });
        }

        // Lọc theo phân loại nguyên liệu
        if (! empty($filters['type'])) {
            $query->where('ing.ingredient_type', $filters['type']);
        }

        // Lọc theo trạng thái tồn kho / HSD
        if (! empty($filters['status_filter'])) {
            match ($filters['status_filter']) {
                'out_of_stock' => $query->whereRaw('COALESCE(sl.quantity, 0) <= 0'),
                'low_stock' => $query->whereRaw('COALESCE(sl.quantity, 0) > 0 AND COALESCE(sl.quantity, 0) <= ing.min_stock'),
                'expired' => $query->whereNotNull('b.earliest_expiry')->where('b.earliest_expiry', '<', $today),
                'expiring_soon' => $query->whereNotNull('b.earliest_expiry')->whereBetween('b.earliest_expiry', [$today, $threeDaysAhead]),
                'safe' => $query->whereRaw('COALESCE(sl.quantity, 0) > ing.min_stock'),
                default => null,
            };
        }

        return $query->orderByRaw('CASE WHEN COALESCE(sl.quantity, 0) <= 0 THEN 1 WHEN COALESCE(sl.quantity, 0) <= ing.min_stock THEN 2 ELSE 3 END')
            ->orderBy('ing.name', 'asc')
            ->paginate($perPage);
    }

    /**
     * Lấy các số liệu thống kê tổng hợp (KPI Cards) cho Dashboard/Kho.
     *
     * @return array<string, mixed>
     */
    public function getAlertSummary(int $branchId): array
    {
        $today = Carbon::today()->toDateString();
        $threeDaysAhead = Carbon::today()->addDays(3)->toDateString();

        $warehouseIds = Warehouse::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->pluck('id');

        // Thống kê tồn kho
        $stockStats = DB::table(ConstantHelper::TABLE_INGREDIENTS.' as ing')
            ->leftJoin(ConstantHelper::TABLE_STOCK_LEVELS.' as sl', function ($join) use ($warehouseIds) {
                $join->on('ing.id', '=', 'sl.ingredient_id')
                    ->whereIn('sl.warehouse_id', $warehouseIds);
            })
            ->where('ing.is_active', true)
            ->whereNull('ing.deleted_at')
            ->selectRaw('
                COUNT(ing.id) as total_ingredients,
                SUM(CASE WHEN COALESCE(sl.quantity, 0) <= 0 THEN 1 ELSE 0 END) as out_of_stock_count,
                SUM(CASE WHEN COALESCE(sl.quantity, 0) > 0 AND COALESCE(sl.quantity, 0) <= ing.min_stock THEN 1 ELSE 0 END) as low_stock_count,
                SUM(ROUND(COALESCE(sl.quantity, 0) * COALESCE(sl.avg_cost, 0), 0)) as total_value
            ')
            ->first();

        // Thống kê lô hàng & HSD
        $batchStats = DB::table(ConstantHelper::TABLE_STOCK_BATCHES)
            ->whereIn('warehouse_id', $warehouseIds)
            ->where('status', StockBatchStatus::ACTIVE->value)
            ->where('current_quantity', '>', 0)
            ->selectRaw("
                SUM(CASE WHEN expiry_date < '{$today}' THEN 1 ELSE 0 END) as expired_count,
                SUM(CASE WHEN expiry_date >= '{$today}' AND expiry_date <= '{$threeDaysAhead}' THEN 1 ELSE 0 END) as expiring_soon_count,
                COUNT(id) as total_active_batches
            ")
            ->first();

        return [
            'total_ingredients' => (int) ($stockStats->total_ingredients ?? 0),
            'out_of_stock_count' => (int) ($stockStats->out_of_stock_count ?? 0),
            'low_stock_count' => (int) ($stockStats->low_stock_count ?? 0),
            'total_inventory_value' => (float) ($stockStats->total_value ?? 0),
            'expired_batches_count' => (int) ($batchStats->expired_count ?? 0),
            'expiring_soon_batches_count' => (int) ($batchStats->expiring_soon_count ?? 0),
            'total_active_batches' => (int) ($batchStats->total_active_batches ?? 0),
        ];
    }

    /**
     * Helper ghi nhận Notification vào cơ sở dữ liệu.
     *
     * @param array<string, mixed> $payload
     */
    protected function createNotification(int $branchId, NotificationType $type, string $title, string $content, array $payload = []): void
    {
        Notification::query()->create([
            'branch_id' => $branchId,
            'target_user_id' => null, // Gửi cho toàn bộ nhân sự phụ trách chi nhánh
            'type' => $type,
            'title' => $title,
            'body' => $content,
            'payload' => $payload,
        ]);
    }
}
