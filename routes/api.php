<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Inventory\StockLedgerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes: Phân hệ Kho & Định Lượng (Module 07)
|--------------------------------------------------------------------------
*/

Route::prefix('v1/inventory')->group(function () {
    //Ghi nhận biến động kho vào sổ cái (Immutable Ledger)
    Route::post('/movements', [StockLedgerController::class, 'recordMovement'])
        ->name('api.inventory.movements.record');

    //Tra cứu thẻ kho (Sổ cái chi tiết) của nguyên liệu
    Route::get('/warehouses/{warehouseId}/ingredients/{ingredientId}/ledger', [StockLedgerController::class, 'getLedgerCard'])
        ->name('api.inventory.ledger.card');

    //Tra cứu mức tồn kho tức thời & giá vốn bình quân
    Route::get('/warehouses/{warehouseId}/ingredients/{ingredientId}/stock', [StockLedgerController::class, 'getStockLevel'])
        ->name('api.inventory.stock.level');

    //Bóc tách BOM định lượng của món ăn theo size & multiplier
    Route::post('/recipes/explode', [StockLedgerController::class, 'explodeRecipe'])
        ->name('api.inventory.recipes.explode');
});
