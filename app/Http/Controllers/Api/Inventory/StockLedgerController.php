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
use App\Services\Inventory\StockAlertService;
use App\Services\Inventory\StockBatchService;
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

    /**
     * Bảng tổng hợp tồn kho và trạng thái chất lượng theo chi nhánh.
     */
    public function stockOverview(Request $request, StockAlertService $alertService): JsonResponse
    {
        $branchId = $this->requireSelectedBranchId($request);
        $perPage = (int) ($request->query('per_page') ?? 20);

        $overview = $alertService->getBranchStockOverview($branchId, [
            'search' => $request->query('search'),
            'type' => $request->query('type'),
            'status_filter' => $request->query('status_filter'),
        ], $perPage);

        return response()->json($overview);
    }

    /**
     * Lấy thống kê cảnh báo tồn kho và HSD của chi nhánh.
     */
    public function alertSummary(Request $request, StockAlertService $alertService): JsonResponse
    {
        $branchId = $this->requireSelectedBranchId($request);
        $summary = $alertService->getAlertSummary($branchId);

        return response()->json([
            'status' => 'success',
            'data' => $summary,
        ]);
    }

    /**
     * Kích hoạt quét cảnh báo tồn kho & HSD thủ công từ giao diện.
     */
    public function scanAlerts(Request $request, StockAlertService $alertService): JsonResponse
    {
        $branchId = $this->requireSelectedBranchId($request);
        $days = (int) ($request->input('days') ?? 3);

        $lowStock = $alertService->scanLowStockAlerts($branchId);
        $expiry = $alertService->scanExpiryAlerts($days, $branchId);

        return response()->json([
            'status' => 'success',
            'message' => 'Đã quét và cập nhật cảnh báo kho thành công.',
            'data' => [
                'low_stock' => $lowStock,
                'expiry' => $expiry,
            ],
        ]);
    }

    /**
     * Danh sách các lô hàng (Batches) có phân trang & lọc.
     */
    public function batches(Request $request, StockBatchService $batchService): JsonResponse
    {
        $branchId = $request->attributes->get('selected_branch_id');
        $warehouseId = $request->query('warehouse_id');

        if ($branchId === null && ! is_numeric($warehouseId)) {
            $branchId = $this->requireSelectedBranchId($request);
        }

        $filters = [
            'warehouse_id' => $warehouseId,
            'branch_id' => $branchId,
            'ingredient_id' => $request->query('ingredient_id'),
            'status' => $request->query('status'),
            'search' => $request->query('search'),
            'expiry_filter' => $request->query('expiry_filter'),
            'days_threshold' => $request->query('days_threshold', 3),
        ];

        $batches = $batchService->paginate(array_filter($filters), (int) ($request->query('per_page') ?? 15));

        return response()->json($batches);
    }

    private function requireSelectedBranchId(Request $request): int
    {
        $branchId = $request->attributes->get('selected_branch_id');
        if (is_numeric($branchId)) {
            return (int) $branchId;
        }

        $allowedBranchIds = $request->attributes->get('authorized_branch_ids');
        if (is_array($allowedBranchIds) && count($allowedBranchIds) === 1) {
            return (int) $allowedBranchIds[0];
        }

        abort(422, 'Vui lòng chọn chi nhánh trước khi xem dữ liệu kho.');
    }

    /**
     * Tạo mới một lô hàng thủ công (nếu cần quản lý riêng).
     */
    public function storeBatch(Request $request, StockBatchService $batchService): JsonResponse
    {
        $validated = $request->validate([
            'batch_code' => ['nullable', 'string', 'max:50'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'import_date' => ['nullable', 'date'],
            'expiry_date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['created_by'] = $request->user()?->id;
        $batch = $batchService->createBatch($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Đã tạo lô nguyên liệu thành công.',
            'data' => $batch,
        ], 201);
    }
}
