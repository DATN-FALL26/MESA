<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Inventory;

use App\DTOs\Inventory\StockMovementDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\ExplodeRecipeRequest;
use App\Http\Requests\Inventory\RecordStockMovementRequest;
use App\Http\Resources\Inventory\ExplodedIngredientResource;
use App\Http\Resources\Inventory\StockLevelResource;
use App\Http\Resources\Inventory\StockMovementResource;
use App\Services\Inventory\RecipeExplosionService;
use App\Services\Inventory\StockLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockLedgerController extends Controller
{
    public function __construct(
        protected StockLedgerService $ledgerService,
        protected RecipeExplosionService $explosionService,
    ) {}

    /**
     * Ghi nhận một biến động kho mới vào sổ cái bất biến.
     */
    public function recordMovement(RecordStockMovementRequest $request): JsonResponse
    {
        $dto = StockMovementDTO::fromRequest($request);
        $movement = $this->ledgerService->recordMovement($dto);

        return (new StockMovementResource($movement))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Tra cứu thẻ kho (sổ cái phát sinh) của một nguyên liệu.
     */
    public function getLedgerCard(Request $request, int $warehouseId, int $ingredientId): AnonymousResourceCollection
    {
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $movements = $this->ledgerService->getLedgerCard(
            warehouseId: $warehouseId,
            ingredientId: $ingredientId,
            fromDate: is_string($fromDate) ? $fromDate : null,
            toDate: is_string($toDate) ? $toDate : null
        );

        return StockMovementResource::collection($movements);
    }

    /**
     * Lấy số tồn kho tức thời và giá vốn bình quân hiện thời.
     */
    public function getStockLevel(int $warehouseId, int $ingredientId): JsonResponse
    {
        $stockLevel = $this->ledgerService->getStockLevel($warehouseId, $ingredientId);

        if ($stockLevel === null) {
            return response()->json([
                'message' => 'Không tìm thấy mức tồn kho cho nguyên liệu này.',
            ], 404);
        }

        return (new StockLevelResource($stockLevel))->response();
    }

    /**
     * Bóc tách công thức định lượng (BOM) của một món ăn ra danh sách nguyên liệu tiêu hao.
     */
    public function explodeRecipe(ExplodeRecipeRequest $request): AnonymousResourceCollection
    {
        $menuItemId = (int) $request->validated('menu_item_id');
        $variantId = $request->filled('variant_id') ? (int) $request->validated('variant_id') : null;
        $multiplier = (int) $request->validated('multiplier', 1);

        $exploded = $this->explosionService->explodeByRecipe(
            menuItemId: $menuItemId,
            variantId: $variantId,
            multiplier: $multiplier
        );

        return ExplodedIngredientResource::collection(collect($exploded));
    }
}
