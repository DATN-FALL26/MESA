<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreRecipeRequest;
use App\Http\Resources\Inventory\RecipeResource;
use App\Models\Recipe;
use App\Repositories\Contracts\RecipeRepositoryInterface;
use App\Services\Inventory\RecipeManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecipeController extends Controller
{
    public function __construct(
        protected RecipeRepositoryInterface $recipeRepository,
        protected RecipeManagementService $recipeManagementService
    ) {}

    /**
     * Lấy danh sách toàn bộ các phiên bản công thức của một món ăn / biến thể.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $menuItemId = $request->input('menu_item_id');

        if (! $menuItemId) {
            return response()->json([
                'message' => 'Tham số menu_item_id là bắt buộc để tra cứu công thức định lượng.',
            ], 400);
        }

        $variantId = $request->has('variant_id') && $request->input('variant_id') !== ''
            ? (int) $request->input('variant_id')
            : null;

        $recipes = $this->recipeRepository->listByMenuItem((int) $menuItemId, $variantId);

        return RecipeResource::collection($recipes);
    }

    /**
     * Chi tiết một phiên bản công thức định lượng kèm danh sách nguyên vật liệu.
     */
    public function show(Recipe $recipe): RecipeResource
    {
        $recipe->load(['items.ingredient', 'menuItem', 'variant']);

        return new RecipeResource($recipe);
    }

    /**
     * Tạo một phiên bản công thức định lượng mới (tự động tăng version).
     */
    public function store(StoreRecipeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $recipe = $this->recipeManagementService->createRecipeVersion(
            menuItemId: (int) $validated['menu_item_id'],
            variantId: ! empty($validated['variant_id']) ? (int) $validated['variant_id'] : null,
            items: $validated['items'],
            activateImmediately: (bool) ($validated['activate_immediately'] ?? true)
        );

        return (new RecipeResource($recipe))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Kích hoạt phiên bản công thức định lượng được chọn thành bản active.
     */
    public function activate(Recipe $recipe): RecipeResource
    {
        $activated = $this->recipeManagementService->activateVersion($recipe->id);

        return new RecipeResource($activated);
    }

    /**
     * Nhân bản một phiên bản hiện có thành bản dự thảo mới (draft version).
     */
    public function cloneAsDraft(Recipe $recipe): JsonResponse
    {
        $draft = $this->recipeManagementService->cloneAsDraft($recipe->id);

        return (new RecipeResource($draft))
            ->response()
            ->setStatusCode(201);
    }
}
