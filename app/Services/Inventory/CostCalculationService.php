<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\DTOs\Inventory\DishCostAnalysisDTO;
use App\DTOs\Inventory\DishIngredientCostDTO;
use App\Models\MenuItem;
use App\Models\OrderItem;
use App\Models\Recipe;
use App\Models\StockLevel;
use App\Repositories\Contracts\RecipeRepositoryInterface;
use App\Repositories\Contracts\StockLevelRepositoryInterface;
use Illuminate\Support\Collection;

class CostCalculationService
{
    public function __construct(
        protected ?RecipeRepositoryInterface $recipeRepository = null,
        protected ?StockLevelRepositoryInterface $levelRepository = null,
        protected ?RecipeExplosionService $explosionService = null,
    ) {
        $this->recipeRepository = $this->recipeRepository ?? (app()->bound(RecipeRepositoryInterface::class) ? app(RecipeRepositoryInterface::class) : null);
        $this->levelRepository = $this->levelRepository ?? (app()->bound(StockLevelRepositoryInterface::class) ? app(StockLevelRepositoryInterface::class) : null);
        $this->explosionService = $this->explosionService ?? (app()->bound(RecipeExplosionService::class) ? app(RecipeExplosionService::class) : null);
    }

    /**
     * Tính toán giá vốn bình quân gia quyền (Weighted Average Cost) khi nhập kho mới.
     *
     * Công thức:
     * New Cost = ((Tồn hiện tại * Giá vốn hiện tại) + (Số lượng nhập * Đơn giá nhập)) / (Tồn hiện tại + Số lượng nhập)
     *
     * @param float $currentQty Số lượng tồn kho hiện thời trước khi nhập
     * @param float $currentAvgCost Đơn giá vốn bình quân hiện thời (VND)
     * @param float $inboundQty Số lượng nhập kho mới (> 0)
     * @param float $inboundUnitCost Đơn giá nhập kho mới (VND)
     * @return float Đơn giá vốn bình quân mới (làm tròn 2 chữ số thập phân)
     */
    public function calculateWeightedAverageCost(
        float $currentQty,
        float $currentAvgCost,
        float $inboundQty,
        float $inboundUnitCost
    ): float {
        // Nếu số lượng nhập <= 0 hoặc đơn giá nhập không hợp lệ, giữ nguyên giá cũ
        if ($inboundQty <= 0) {
            return round(max(0.0, $currentAvgCost), 2);
        }

        // Trường hợp tồn kho hiện tại âm hoặc bằng 0: Giá vốn mới chính là đơn giá nhập
        if ($currentQty <= 0) {
            return round(max(0.0, $inboundUnitCost), 2);
        }

        $currentTotalValue = $currentQty * $currentAvgCost;
        $inboundTotalValue = $inboundQty * $inboundUnitCost;
        $newTotalQty = $currentQty + $inboundQty;

        if ($newTotalQty <= 0) {
            return round(max(0.0, $inboundUnitCost), 2);
        }

        $newAvgCost = ($currentTotalValue + $inboundTotalValue) / $newTotalQty;

        return round(max(0.0, $newAvgCost), 2);
    }

    /**
     * Tính toán các chỉ số Lợi nhuận gộp và Tỷ lệ phần trăm từ Giá bán và Giá vốn.
     *
     * @return array{gross_profit: float, cost_percentage: float, margin_percentage: float}
     */
    public function calculateProfitMetrics(float $sellingPrice, float $costPrice): array
    {
        $grossProfit = round($sellingPrice - $costPrice, 2);

        $costPercentage = 0.0;
        $marginPercentage = 0.0;

        if ($sellingPrice > 0) {
            $costPercentage = round(($costPrice / $sellingPrice) * 100, 2);
            $marginPercentage = round(($grossProfit / $sellingPrice) * 100, 2);
        }

        return [
            'gross_profit' => $grossProfit,
            'cost_percentage' => $costPercentage,
            'margin_percentage' => $marginPercentage,
        ];
    }

    /**
     * Tính toán chi phí nguyên liệu lý thuyết (Recipe Cost) và lợi nhuận cho một món ăn cụ thể.
     */
    public function calculateDishCost(int $menuItemId, ?int $variantId, int $warehouseId): DishCostAnalysisDTO
    {
        $menuItem = MenuItem::with(['variants'])->findOrFail($menuItemId);

        // 1. Xác định giá bán (Selling Price)
        $sellingPrice = (float) $menuItem->base_price;
        $variantName = null;

        if ($variantId !== null) {
            $variant = $menuItem->variants->firstWhere('id', $variantId);
            if ($variant !== null) {
                $sellingPrice += (float) $variant->price_delta;
                $variantName = $variant->name;
            }
        }

        // 2. Tra cứu công thức định lượng active (BOM)
        $recipe = $this->recipeRepository !== null
            ? $this->recipeRepository->findActiveRecipe($menuItemId, $variantId)
            : Recipe::with(['items.ingredient'])
                ->where('menu_item_id', $menuItemId)
                ->when($variantId !== null, fn ($q) => $q->where('variant_id', $variantId), fn ($q) => $q->whereNull('variant_id'))
                ->where('is_active', true)
                ->orderByDesc('version')
                ->first();

        if ($recipe === null) {
            $metrics = $this->calculateProfitMetrics($sellingPrice, 0.0);

            return new DishCostAnalysisDTO(
                menuItemId: $menuItem->id,
                menuItemName: $menuItem->name,
                menuItemSku: $menuItem->sku,
                variantId: $variantId,
                variantName: $variantName,
                sellingPrice: $sellingPrice,
                recipeCost: 0.0,
                grossProfit: $metrics['gross_profit'],
                costPercentage: $metrics['cost_percentage'],
                marginPercentage: $metrics['margin_percentage'],
                hasRecipe: false,
                recipeVersion: null,
                ingredients: [],
            );
        }

        // 3. Lấy giá vốn bình quân (avg_cost) của tất cả nguyên liệu trong công thức
        $ingredientIds = $recipe->items->pluck('ingredient_id')->filter()->unique()->toArray();
        $avgCosts = $this->getAvgCostsForIngredients($warehouseId, $ingredientIds);

        // 4. Tính toán chi phí từng thành phần
        $totalCost = 0.0;
        $ingredientBreakdown = [];

        foreach ($recipe->items as $item) {
            $ingredient = $item->ingredient;
            if ($ingredient === null) {
                continue;
            }

            $qty = (float) $item->quantity;
            $unitCost = (float) ($avgCosts[(int) $item->ingredient_id] ?? 0.0);
            $subtotal = round($qty * $unitCost, 2);
            $totalCost += $subtotal;

            $ingredientBreakdown[] = new DishIngredientCostDTO(
                ingredientId: (int) $item->ingredient_id,
                sku: (string) $ingredient->sku,
                name: (string) $ingredient->name,
                quantity: $qty,
                baseUom: (string) $ingredient->base_uom,
                unitCost: $unitCost,
                subtotalCost: $subtotal,
            );
        }

        $totalCost = round($totalCost, 2);
        $metrics = $this->calculateProfitMetrics($sellingPrice, $totalCost);

        return new DishCostAnalysisDTO(
            menuItemId: $menuItem->id,
            menuItemName: $menuItem->name,
            menuItemSku: $menuItem->sku,
            variantId: $variantId,
            variantName: $variantName,
            sellingPrice: $sellingPrice,
            recipeCost: $totalCost,
            grossProfit: $metrics['gross_profit'],
            costPercentage: $metrics['cost_percentage'],
            marginPercentage: $metrics['margin_percentage'],
            hasRecipe: true,
            recipeVersion: (int) $recipe->version,
            ingredients: $ingredientBreakdown,
        );
    }

    /**
     * Tính toán hàng loạt (Batch) chi phí nguyên liệu và lợi nhuận cho danh sách món ăn.
     * Tối ưu hóa truy vấn CSDL: Chỉ 3 queries bất kể số lượng món ăn (O(1) database queries, không N+1).
     *
     * @param array<int> $menuItemIds
     * @return Collection<int, DishCostAnalysisDTO>
     */
    public function calculateBatchDishCosts(array $menuItemIds, int $warehouseId): Collection
    {
        if (empty($menuItemIds)) {
            return collect();
        }

        // Query 1: Lấy thông tin các món ăn kèm biến thể
        $menuItems = MenuItem::with(['variants'])
            ->whereIn('id', $menuItemIds)
            ->get()
            ->keyBy('id');

        // Query 2: Lấy tất cả công thức định lượng active của các món ăn này
        $recipes = Recipe::with(['items.ingredient'])
            ->whereIn('menu_item_id', $menuItemIds)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->get();

        // Gom nhóm recipe theo "menu_item_id_variant_id"
        $recipeMap = [];
        $allIngredientIds = [];

        foreach ($recipes as $recipe) {
            $key = $recipe->menu_item_id . '_' . ($recipe->variant_id ?? 'null');
            if (! isset($recipeMap[$key])) {
                $recipeMap[$key] = $recipe;
            }

            foreach ($recipe->items as $item) {
                if ($item->ingredient_id) {
                    $allIngredientIds[] = (int) $item->ingredient_id;
                }
            }
        }

        // Query 3: Lấy giá vốn bình quân (avg_cost) của tất cả nguyên liệu liên quan trong kho
        $allIngredientIds = array_values(array_unique($allIngredientIds));
        $avgCosts = $this->getAvgCostsForIngredients($warehouseId, $allIngredientIds);

        // Map và tính toán hoàn toàn trong bộ nhớ (In-memory computation)
        $result = collect();

        foreach ($menuItems as $menuItem) {
            // Nếu món có các biến thể, tính toán cho từng biến thể
            if ($menuItem->variants->isNotEmpty()) {
                foreach ($menuItem->variants as $variant) {
                    $recipeKey = $menuItem->id . '_' . $variant->id;
                    $fallbackKey = $menuItem->id . '_null';
                    $recipe = $recipeMap[$recipeKey] ?? ($recipeMap[$fallbackKey] ?? null);

                    $sellingPrice = (float) $menuItem->base_price + (float) $variant->price_delta;
                    $result->push($this->buildDishCostAnalysisFromRecipe(
                        menuItem: $menuItem,
                        variantId: $variant->id,
                        variantName: $variant->name,
                        sellingPrice: $sellingPrice,
                        recipe: $recipe,
                        avgCosts: $avgCosts
                    ));
                }
            } else {
                // Món chuẩn không có biến thể
                $recipeKey = $menuItem->id . '_null';
                $recipe = $recipeMap[$recipeKey] ?? null;

                $sellingPrice = (float) $menuItem->base_price;
                $result->push($this->buildDishCostAnalysisFromRecipe(
                    menuItem: $menuItem,
                    variantId: null,
                    variantName: null,
                    sellingPrice: $sellingPrice,
                    recipe: $recipe,
                    avgCosts: $avgCosts
                ));
            }
        }

        return $result;
    }

    /**
     * Tính toán Giá vốn hàng bán (COGS) thực tế cho một dòng OrderItem (gồm món chính + variant + modifiers).
     *
     * @return array{
     *     unit_cogs: float,
     *     total_cogs: float,
     *     gross_profit: float,
     *     cost_percentage: float,
     *     margin_percentage: float,
     *     breakdown: array<int, array{ingredient_id: int, sku: string, name: string, quantity: float, unit_cost: float, subtotal: float}>
     * }
     */
    public function calculateOrderItemCogs(OrderItem $orderItem, int $warehouseId): array
    {
        $explosion = $this->explosionService ?? app(RecipeExplosionService::class);
        $consumedIngredients = $explosion->explodeOrderItem($orderItem);

        if (empty($consumedIngredients)) {
            $sellingTotal = (float) $orderItem->line_total;
            $metrics = $this->calculateProfitMetrics($sellingTotal, 0.0);

            return [
                'unit_cogs' => 0.0,
                'total_cogs' => 0.0,
                'gross_profit' => $metrics['gross_profit'],
                'cost_percentage' => $metrics['cost_percentage'],
                'margin_percentage' => $metrics['margin_percentage'],
                'breakdown' => [],
            ];
        }

        $ingredientIds = array_map(fn ($item) => $item->ingredientId, $consumedIngredients);
        $avgCosts = $this->getAvgCostsForIngredients($warehouseId, $ingredientIds);

        $totalCogs = 0.0;
        $breakdown = [];

        foreach ($consumedIngredients as $item) {
            $unitCost = (float) ($avgCosts[$item->ingredientId] ?? 0.0);
            $qty = abs((float) $item->quantity);
            $subtotal = round($qty * $unitCost, 2);
            $totalCogs += $subtotal;

            $breakdown[] = [
                'ingredient_id' => $item->ingredientId,
                'sku' => $item->sku,
                'name' => $item->name,
                'quantity' => $qty,
                'unit_cost' => $unitCost,
                'subtotal' => $subtotal,
            ];
        }

        $totalCogs = round($totalCogs, 2);
        $itemQty = max(1, (int) $orderItem->quantity);
        $unitCogs = round($totalCogs / $itemQty, 2);

        $sellingTotal = (float) $orderItem->line_total;
        $metrics = $this->calculateProfitMetrics($sellingTotal, $totalCogs);

        return [
            'unit_cogs' => $unitCogs,
            'total_cogs' => $totalCogs,
            'gross_profit' => $metrics['gross_profit'],
            'cost_percentage' => $metrics['cost_percentage'],
            'margin_percentage' => $metrics['margin_percentage'],
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Tra cứu giá vốn bình quân (avg_cost) của danh sách nguyên liệu trong kho chỉ định.
     *
     * @param array<int> $ingredientIds
     * @return array<int, float> [ingredient_id => avg_cost]
     */
    protected function getAvgCostsForIngredients(int $warehouseId, array $ingredientIds): array
    {
        if (empty($ingredientIds)) {
            return [];
        }

        return StockLevel::query()
            ->where('warehouse_id', $warehouseId)
            ->whereIn('ingredient_id', $ingredientIds)
            ->pluck('avg_cost', 'ingredient_id')
            ->map(fn ($cost) => (float) $cost)
            ->toArray();
    }

    /**
     * Helper lắp ráp DTO phân tích từ recipe và giá vốn nguyên liệu.
     *
     * @param array<int, float> $avgCosts
     */
    protected function buildDishCostAnalysisFromRecipe(
        MenuItem $menuItem,
        ?int $variantId,
        ?string $variantName,
        float $sellingPrice,
        ?Recipe $recipe,
        array $avgCosts
    ): DishCostAnalysisDTO {
        if ($recipe === null) {
            $metrics = $this->calculateProfitMetrics($sellingPrice, 0.0);

            return new DishCostAnalysisDTO(
                menuItemId: $menuItem->id,
                menuItemName: $menuItem->name,
                menuItemSku: $menuItem->sku,
                variantId: $variantId,
                variantName: $variantName,
                sellingPrice: $sellingPrice,
                recipeCost: 0.0,
                grossProfit: $metrics['gross_profit'],
                costPercentage: $metrics['cost_percentage'],
                marginPercentage: $metrics['margin_percentage'],
                hasRecipe: false,
                recipeVersion: null,
                ingredients: [],
            );
        }

        $totalCost = 0.0;
        $ingredientBreakdown = [];

        foreach ($recipe->items as $item) {
            $ingredient = $item->ingredient;
            if ($ingredient === null) {
                continue;
            }

            $qty = (float) $item->quantity;
            $unitCost = (float) ($avgCosts[(int) $item->ingredient_id] ?? 0.0);
            $subtotal = round($qty * $unitCost, 2);
            $totalCost += $subtotal;

            $ingredientBreakdown[] = new DishIngredientCostDTO(
                ingredientId: (int) $item->ingredient_id,
                sku: (string) $ingredient->sku,
                name: (string) $ingredient->name,
                quantity: $qty,
                baseUom: (string) $ingredient->base_uom,
                unitCost: $unitCost,
                subtotalCost: $subtotal,
            );
        }

        $totalCost = round($totalCost, 2);
        $metrics = $this->calculateProfitMetrics($sellingPrice, $totalCost);

        return new DishCostAnalysisDTO(
            menuItemId: $menuItem->id,
            menuItemName: $menuItem->name,
            menuItemSku: $menuItem->sku,
            variantId: $variantId,
            variantName: $variantName,
            sellingPrice: $sellingPrice,
            recipeCost: $totalCost,
            grossProfit: $metrics['gross_profit'],
            costPercentage: $metrics['cost_percentage'],
            marginPercentage: $metrics['margin_percentage'],
            hasRecipe: true,
            recipeVersion: (int) $recipe->version,
            ingredients: $ingredientBreakdown,
        );
    }
}
