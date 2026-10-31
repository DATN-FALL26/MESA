<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreIngredientRequest;
use App\Http\Requests\Inventory\UpdateIngredientRequest;
use App\Http\Resources\Inventory\IngredientResource;
use App\Models\Ingredient;
use App\Services\Inventory\IngredientService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IngredientController extends Controller
{
    public function __construct(
        protected IngredientService $ingredientService
    ) {}

    /**
     * Danh sách nguyên vật liệu có phân trang và bộ lọc.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->input('per_page', 15);
        $filters = $request->only(['search', 'ingredient_type', 'is_active']);

        $ingredients = $this->ingredientService->paginate($filters, $perPage);

        return IngredientResource::collection($ingredients);
    }

    /**
     * Danh sách toàn bộ nguyên liệu đang hoạt động (dùng cho dropdown selector).
     */
    public function active(): AnonymousResourceCollection
    {
        $ingredients = $this->ingredientService->getActiveIngredients();

        return IngredientResource::collection($ingredients);
    }

    /**
     * Thêm mới nguyên vật liệu.
     */
    public function store(StoreIngredientRequest $request): JsonResponse
    {
        $ingredient = $this->ingredientService->create($request->validated());

        return (new IngredientResource($ingredient))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Chi tiết nguyên vật liệu.
     */
    public function show(Ingredient $ingredient): IngredientResource
    {
        return new IngredientResource($ingredient);
    }

    /**
     * Cập nhật thông tin nguyên vật liệu.
     */
    public function update(UpdateIngredientRequest $request, Ingredient $ingredient): IngredientResource
    {
        $updated = $this->ingredientService->update($ingredient, $request->validated());

        return new IngredientResource($updated);
    }

    /**
     * Xóa mềm nguyên vật liệu (có kiểm tra an toàn Safe Guard).
     */
    public function destroy(Ingredient $ingredient): JsonResponse
    {
        try {
            $this->ingredientService->safeDelete($ingredient);

            return response()->json([
                'message' => 'Đã xóa nguyên vật liệu thành công.',
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
