<?php

declare(strict_types=1);

namespace App\Services\Kitchen;

use App\DTOs\Inventory\StockMovementDTO;
use App\Enums\MovementType;
use App\Enums\PurchaseOrderSource;
use App\Enums\PurchaseOrderStatus;
use App\Models\BranchMenuItem;
use App\Models\Ingredient;
use App\Models\KitchenDailyCheckin;
use App\Models\KitchenDailyCheckinItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\RecipeItem;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Services\Inventory\StockLedgerService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KitchenDailyCheckinService
{
    public function __construct(
        protected StockLedgerService $stockLedgerService,
        protected PurchaseOrderRepositoryInterface $poRepository,
    ) {}

    /**
     * Kiểm tra xem chi nhánh đã thực hiện xác nhận nhập nguyên liệu tươi đầu ca cho ngày chỉ định chưa.
     */
    public function hasCheckedInToday(int $branchId, ?string $date = null): bool
    {
        $targetDate = $date ?? now()->toDateString();

        return KitchenDailyCheckin::query()
            ->where('branch_id', $branchId)
            ->whereDate('checkin_date', $targetDate)
            ->where('type', 'start_shift')
            ->exists();
    }

    /**
     * Lấy danh sách nguyên liệu nhập theo ngày kèm mức tồn kho tức thời tại kho chi nhánh.
     *
     * @return Collection<int, Ingredient>
     */
    public function getDailyIngredientsWithStock(int $warehouseId): Collection
    {
        $ingredients = Ingredient::query()
            ->where('is_daily_procured', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($ingredients->isEmpty()) {
            return $ingredients;
        }

        $stockLevels = StockLevel::query()
            ->where('warehouse_id', $warehouseId)
            ->whereIn('ingredient_id', $ingredients->pluck('id'))
            ->get()
            ->keyBy('ingredient_id');

        foreach ($ingredients as $ingredient) {
            $stockLevel = $stockLevels->get($ingredient->id);
            $ingredient->current_stock_qty = $stockLevel ? (float) $stockLevel->quantity : 0.0;
            $ingredient->avg_cost = $stockLevel ? (float) $stockLevel->avg_cost : 0.0;
        }

        return $ingredients;
    }

    /**
     * Thực hiện chốt & ghi nhận nhập nguyên liệu từ màn hình Bếp.
     * Tự động đồng bộ sang Đơn nhập hàng (Purchase Order) trong hệ thống Quản trị (Admin).
     *
     * @param  array<int, array{ingredient_id: int, quantity?: float|numeric-string, unit_price?: float|numeric-string, action?: string, note?: string|null}>  $itemsData
     */
    public function performCheckin(
        int $branchId,
        int $warehouseId,
        int $userId,
        array $itemsData,
        string $type = 'start_shift',
        ?string $note = null
    ): KitchenDailyCheckin {
        return DB::transaction(function () use ($branchId, $warehouseId, $userId, $itemsData, $type, $note): KitchenDailyCheckin {
            $checkin = KitchenDailyCheckin::query()->create([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'checkin_date' => now()->toDateString(),
                'user_id' => $userId,
                'type' => $type,
                'note' => $note,
            ]);

            $ingredientIds = array_column($itemsData, 'ingredient_id');
            $ingredients = Ingredient::query()
                ->whereIn('id', $ingredientIds)
                ->get()
                ->keyBy('id');

            $stockLevels = StockLevel::query()
                ->where('warehouse_id', $warehouseId)
                ->whereIn('ingredient_id', $ingredientIds)
                ->get()
                ->keyBy('ingredient_id');

            // 1. Lọc các món có hành động nhập mới (inward) với số lượng > 0
            $inwardRows = array_filter($itemsData, function ($row) {
                $action = (string) ($row['action'] ?? 'inward');
                $qty = (float) ($row['quantity'] ?? 0);

                return $action === 'inward' && $qty > 0;
            });

            // 2. Nếu có hàng nhập mới -> Tạo đơn nhập hàng (Purchase Order) chuẩn hóa sang module Admin
            $purchaseOrder = null;
            if (! empty($inwardRows)) {
                $poNo = $this->poRepository->generatePoNo($branchId);
                $supplierId = Supplier::query()->where('is_active', true)->value('id');

                $typeLabel = $type === 'start_shift' ? 'đầu ca' : 'bổ sung trong ca';
                $poNote = "Bếp xác nhận nhập hàng {$typeLabel}".($note ? ": {$note}" : '');

                $purchaseOrder = PurchaseOrder::query()->create([
                    'branch_id' => $branchId,
                    'warehouse_id' => $warehouseId,
                    'supplier_id' => $supplierId,
                    'po_no' => $poNo,
                    'status' => PurchaseOrderStatus::RECEIVED,
                    'source' => PurchaseOrderSource::MANUAL,
                    'note' => $poNote,
                    'created_by' => $userId,
                    'approved_by' => $userId,
                    'approved_at' => now(),
                    'received_by' => $userId,
                    'received_at' => now(),
                ]);

                $checkin->update([
                    'purchase_order_id' => $purchaseOrder->id,
                ]);
            }

            foreach ($itemsData as $row) {
                $ingredientId = (int) $row['ingredient_id'];
                $ingredient = $ingredients->get($ingredientId);
                if (! $ingredient) {
                    continue;
                }

                $action = (string) ($row['action'] ?? 'inward');
                $quantity = max(0, (float) ($row['quantity'] ?? 0));
                $stockLevel = $stockLevels->get($ingredientId);

                // Lấy đơn giá người dùng nhập, nếu không có lấy avg_cost hiện tại
                $unitPrice = isset($row['unit_price']) && is_numeric($row['unit_price'])
                    ? max(0, (float) $row['unit_price'])
                    : ($stockLevel && (float) $stockLevel->avg_cost > 0 ? (float) $stockLevel->avg_cost : 0.0);

                $itemNote = isset($row['note']) ? (string) $row['note'] : null;

                // Lưu dòng chi tiết checkin
                KitchenDailyCheckinItem::query()->create([
                    'checkin_id' => $checkin->id,
                    'ingredient_id' => $ingredientId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'action' => $action,
                    'note' => $itemNote,
                ]);

                // 3. Nếu là nhập hàng mới (inward) với số lượng > 0 -> ghi nhận vào PO Items & Sổ cái kho
                if ($action === 'inward' && $quantity > 0 && $purchaseOrder !== null) {
                    PurchaseOrderItem::query()->create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'ingredient_id' => $ingredientId,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'received_qty' => $quantity,
                    ]);

                    $dto = new StockMovementDTO(
                        warehouseId: $warehouseId,
                        ingredientId: $ingredientId,
                        movementType: MovementType::PURCHASE_IN,
                        quantity: $quantity,
                        unitCost: $unitPrice > 0 ? $unitPrice : null,
                        refType: 'purchase_order',
                        refId: $purchaseOrder->id,
                        idempotencyKey: "po_{$purchaseOrder->id}_ing_{$ingredientId}",
                        note: "Nhập hàng từ NCC theo đơn {$purchaseOrder->po_no} (Bếp xác nhận)",
                        createdBy: $userId,
                    );

                    $this->stockLedgerService->recordMovement($dto);

                    // Mở lại trạng thái sẵn sàng cho món ăn tương ứng tại chi nhánh
                    $this->syncMenuItemAvailability($branchId, $ingredientId, true);
                }

                // 4. Nếu báo hết hàng (out_of_stock) -> tự động chuyển trạng thái món sang hết hàng (is_available = false)
                if ($action === 'out_of_stock') {
                    $this->syncMenuItemAvailability($branchId, $ingredientId, false);
                }
            }

            Log::info('KitchenDailyCheckinService: Hoàn tất chốt nguyên liệu từ Bếp và đồng bộ PO.', [
                'checkin_id' => $checkin->id,
                'purchase_order_id' => $purchaseOrder?->id,
                'po_no' => $purchaseOrder?->po_no,
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'type' => $type,
                'items_count' => count($itemsData),
            ]);

            return $checkin;
        });
    }

    /**
     * Đồng bộ trạng thái còn hàng / hết hàng trên POS cho các món ăn sử dụng nguyên liệu này.
     */
    protected function syncMenuItemAvailability(int $branchId, int $ingredientId, bool $isAvailable): void
    {
        $menuItemIds = RecipeItem::query()
            ->where('ingredient_id', $ingredientId)
            ->join('recipes', 'recipes.id', '=', 'recipe_items.recipe_id')
            ->where('recipes.is_active', true)
            ->whereNotNull('recipes.menu_item_id')
            ->pluck('recipes.menu_item_id')
            ->unique();

        foreach ($menuItemIds as $menuItemId) {
            BranchMenuItem::query()->updateOrCreate(
                [
                    'branch_id' => $branchId,
                    'menu_item_id' => (int) $menuItemId,
                ],
                [
                    'is_available' => $isAvailable,
                ]
            );
        }
    }
}
