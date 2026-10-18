<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Inventory\IngredientController;
use App\Http\Controllers\Api\Inventory\RecipeController;
use App\Http\Controllers\Api\Inventory\StockLedgerController;
use App\Http\Controllers\Api\Inventory\SupplierController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes: Phân hệ Kho & Định Lượng (Module 07)
|--------------------------------------------------------------------------
*/

Route::prefix('v1/inventory')->group(function () {
    // ==========================================
    // 1. SỔ CÁI & TỒN KHO (CORE ENGINE)
    // ==========================================
    // Ghi nhận biến động kho vào sổ cái (Immutable Ledger)
    Route::post('/movements', [StockLedgerController::class, 'recordMovement'])
        ->name('api.inventory.movements.record');

    // Tra cứu thẻ kho (Sổ cái chi tiết) của nguyên liệu
    Route::get('/warehouses/{warehouseId}/ingredients/{ingredientId}/ledger', [StockLedgerController::class, 'getLedgerCard'])
        ->name('api.inventory.ledger.card');

    // Tra cứu mức tồn kho tức thời & giá vốn bình quân
    Route::get('/warehouses/{warehouseId}/ingredients/{ingredientId}/stock', [StockLedgerController::class, 'getStockLevel'])
        ->name('api.inventory.stock.level');

    // Bóc tách BOM định lượng của món ăn theo size & multiplier
    Route::post('/recipes/explode', [StockLedgerController::class, 'explodeRecipe'])
        ->name('api.inventory.recipes.explode');

    // ==========================================
    // 2. MASTER DATA: NGUYÊN VẬT LIỆU (INGREDIENTS)
    // ==========================================
    Route::get('/ingredients/active', [IngredientController::class, 'active'])
        ->name('api.inventory.ingredients.active');
    Route::apiResource('ingredients', IngredientController::class)
        ->names('api.inventory.ingredients');

    // ==========================================
    // 3. MASTER DATA: NHÀ CUNG CẤP (SUPPLIERS)
    // ==========================================
    Route::get('/suppliers/active', [SupplierController::class, 'active'])
        ->name('api.inventory.suppliers.active');
    Route::apiResource('suppliers', SupplierController::class)
        ->names('api.inventory.suppliers');

    // ==========================================
    // 4. QUẢN LÝ CÔNG THỨC ĐỊNH LƯỢNG (BOM RECIPES)
    // ==========================================
    Route::get('/recipes', [RecipeController::class, 'index'])
        ->name('api.inventory.recipes.index');
    Route::post('/recipes', [RecipeController::class, 'store'])
        ->name('api.inventory.recipes.store');
    Route::get('/recipes/{recipe}', [RecipeController::class, 'show'])
        ->name('api.inventory.recipes.show');
    Route::post('/recipes/{recipe}/activate', [RecipeController::class, 'activate'])
        ->name('api.inventory.recipes.activate');
    Route::post('/recipes/{recipe}/clone', [RecipeController::class, 'cloneAsDraft'])
        ->name('api.inventory.recipes.clone');
});
