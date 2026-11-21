<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Inventory\IngredientController;
use App\Http\Controllers\Api\Inventory\PurchaseOrderController;
use App\Http\Controllers\Api\Inventory\RecipeController;
use App\Http\Controllers\Api\Inventory\StockAdjustmentController;
use App\Http\Controllers\Api\Inventory\StockLedgerController;
use App\Http\Controllers\Api\Inventory\SupplierController;
use App\Http\Controllers\Api\Inventory\SupplierDocumentController;
use App\Http\Controllers\Api\Inventory\WarehouseController;
use App\Http\Controllers\table\DiningSessionController;
use App\Http\Controllers\table\TableController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'permission:table.view'])->group(function (): void {
    Route::get('/tables/status', [TableController::class, 'status'])->name('api.tables.status');

    Route::middleware('permission:table.layout.manage')->group(function (): void {
        Route::post('/tables', [TableController::class, 'store'])->name('api.tables.store');
        Route::put('/tables/{id}', [TableController::class, 'update'])->name('api.tables.update');
        Route::delete('/tables/{id}', [TableController::class, 'destroy'])->name('api.tables.destroy');
    });

    Route::post('/dining-sessions', [DiningSessionController::class, 'store'])->name('api.dining-sessions.store');
    Route::post('/tables/{tableId}/dining-session/close', [DiningSessionController::class, 'closeByTable'])
        ->name('api.tables.dining-session.close');
    Route::post('/tables/{tableId}/dining-session/transfer', [DiningSessionController::class, 'transferTable'])
        ->name('api.tables.dining-session.transfer');
    Route::get('/dining-sessions/employees', [DiningSessionController::class, 'employees'])
        ->name('api.dining-sessions.employees');
    Route::get('/tables/{tableId}/dining-session', [DiningSessionController::class, 'showByTable'])
        ->name('api.tables.dining-session.show');
});

/*
|--------------------------------------------------------------------------
| API Routes: Phân hệ Kho & Định Lượng (Module 07)
|--------------------------------------------------------------------------
*/

Route::prefix('v1/inventory')->middleware(['web', 'auth'])->group(function () {
    // ==========================================
    // 1. SỔ CÁI & TỒN KHO (CORE ENGINE)
    // ==========================================
    Route::middleware(['permission:inventory.view', 'branch.access:inventory.view'])->group(function (): void {
        Route::get('/warehouses', [WarehouseController::class, 'index'])->name('api.inventory.warehouses.index');
        Route::get('/branches', [WarehouseController::class, 'branches'])->name('api.inventory.branches.index');
        Route::get('/warehouses/{warehouseId}/ingredients/{ingredientId}/ledger', [StockLedgerController::class, 'getLedgerCard'])
            ->name('api.inventory.ledger.card');
        Route::get('/warehouses/{warehouseId}/ingredients/{ingredientId}/stock', [StockLedgerController::class, 'getStockLevel'])
            ->name('api.inventory.stock.level');
        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('api.inventory.purchase-orders.index');
        Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('api.inventory.purchase-orders.show');
        Route::get('/adjustments', [StockAdjustmentController::class, 'index'])->name('api.inventory.adjustments.index');
        Route::get('/adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'show'])->name('api.inventory.adjustments.show');
    });

    Route::middleware(['permission:inventory.receive_issue', 'branch.access:inventory.receive_issue'])->group(function (): void {
        Route::post('/movements', [StockLedgerController::class, 'recordMovement'])->name('api.inventory.movements.record');
        Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])
            ->name('api.inventory.purchase-orders.receive');
    });

    // Master data is shared across branches; feature permissions still protect it.
    Route::get('/ingredients/active', [IngredientController::class, 'active'])
        ->middleware('permission:inventory.view')
        ->name('api.inventory.ingredients.active');
    Route::get('/ingredients', [IngredientController::class, 'index'])
        ->middleware('permission:inventory.view')
        ->name('api.inventory.ingredients.index');
    Route::post('/ingredients', [IngredientController::class, 'store'])
        ->middleware('permission:inventory.adjust')
        ->name('api.inventory.ingredients.store');
    Route::get('/ingredients/{ingredient}', [IngredientController::class, 'show'])
        ->middleware('permission:inventory.view')
        ->name('api.inventory.ingredients.show');
    Route::put('/ingredients/{ingredient}', [IngredientController::class, 'update'])
        ->middleware('permission:inventory.adjust')
        ->name('api.inventory.ingredients.update');
    Route::delete('/ingredients/{ingredient}', [IngredientController::class, 'destroy'])
        ->middleware('permission:inventory.adjust')
        ->name('api.inventory.ingredients.destroy');
    Route::get('/suppliers/active', [SupplierController::class, 'active'])
        ->middleware('permission:inventory.view')
        ->name('api.inventory.suppliers.active');
    Route::get('/suppliers', [SupplierController::class, 'index'])
        ->middleware('permission:inventory.view')
        ->name('api.inventory.suppliers.index');
    Route::post('/suppliers', [SupplierController::class, 'store'])
        ->middleware('permission:supplier.manage')
        ->name('api.inventory.suppliers.store');
    Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])
        ->middleware('permission:inventory.view')
        ->name('api.inventory.suppliers.show');
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])
        ->middleware('permission:supplier.manage')
        ->name('api.inventory.suppliers.update');
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])
        ->middleware('permission:supplier.manage')
        ->name('api.inventory.suppliers.destroy');
    Route::get('/supplier-documents/types', [SupplierDocumentController::class, 'types'])
        ->middleware('permission:inventory.view')
        ->name('api.inventory.supplier-documents.types');
    Route::get('/supplier-documents/expiring', [SupplierDocumentController::class, 'expiring'])
        ->middleware('permission:inventory.view')
        ->name('api.inventory.supplier-documents.expiring');
    Route::get('/suppliers/{supplier}/documents', [SupplierDocumentController::class, 'index'])
        ->middleware('permission:inventory.view')
        ->name('api.inventory.suppliers.documents.index');
    Route::post('/suppliers/{supplier}/documents', [SupplierDocumentController::class, 'store'])
        ->middleware('permission:supplier.manage')
        ->name('api.inventory.suppliers.documents.store');
    Route::get('/suppliers/{supplier}/documents/{document}', [SupplierDocumentController::class, 'show'])
        ->middleware('permission:inventory.view')
        ->name('api.inventory.suppliers.documents.show');
    Route::post('/suppliers/{supplier}/documents/{document}', [SupplierDocumentController::class, 'update'])
        ->middleware('permission:supplier.manage')
        ->name('api.inventory.suppliers.documents.update');
    Route::delete('/suppliers/{supplier}/documents/{document}', [SupplierDocumentController::class, 'destroy'])
        ->middleware('permission:supplier.manage')
        ->name('api.inventory.suppliers.documents.destroy');

    Route::get('/recipes', [RecipeController::class, 'index'])
        ->middleware('permission:inventory.view')
        ->name('api.inventory.recipes.index');
    Route::post('/recipes/explode', [StockLedgerController::class, 'explodeRecipe'])
        ->middleware('permission:inventory.view')
        ->name('api.inventory.recipes.explode');
    Route::post('/recipes', [RecipeController::class, 'store'])
        ->middleware('permission:recipe.manage')
        ->name('api.inventory.recipes.store');
    Route::get('/recipes/{recipe}', [RecipeController::class, 'show'])
        ->middleware('permission:inventory.view')
        ->name('api.inventory.recipes.show');
    Route::post('/recipes/{recipe}/activate', [RecipeController::class, 'activate'])
        ->middleware('permission:recipe.manage')
        ->name('api.inventory.recipes.activate');
    Route::post('/recipes/{recipe}/clone', [RecipeController::class, 'cloneAsDraft'])
        ->middleware('permission:recipe.manage')
        ->name('api.inventory.recipes.clone');

    Route::middleware(['permission:purchase.create', 'branch.access:purchase.create'])->group(function (): void {
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('api.inventory.purchase-orders.store');
        Route::put('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('api.inventory.purchase-orders.update');
        Route::post('/purchase-orders/{purchaseOrder}/submit', [PurchaseOrderController::class, 'submit'])->name('api.inventory.purchase-orders.submit');
        Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('api.inventory.purchase-orders.cancel');
    });

    Route::middleware(['permission:inventory.approve', 'branch.access:inventory.approve'])->group(function (): void {
        Route::post('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('api.inventory.purchase-orders.approve');
        Route::post('/purchase-orders/{purchaseOrder}/reject', [PurchaseOrderController::class, 'reject'])->name('api.inventory.purchase-orders.reject');
        Route::post('/adjustments/{stockAdjustment}/approve', [StockAdjustmentController::class, 'approve'])->name('api.inventory.adjustments.approve');
        Route::post('/adjustments/{stockAdjustment}/reject', [StockAdjustmentController::class, 'reject'])->name('api.inventory.adjustments.reject');
    });

    Route::middleware(['permission:inventory.adjust', 'branch.access:inventory.adjust'])->group(function (): void {
        Route::post('/adjustments', [StockAdjustmentController::class, 'store'])->name('api.inventory.adjustments.store');
        Route::put('/adjustments/{stockAdjustment}/items', [StockAdjustmentController::class, 'updateItems'])
            ->name('api.inventory.adjustments.update-items');
        Route::post('/adjustments/{stockAdjustment}/submit', [StockAdjustmentController::class, 'submit'])
            ->name('api.inventory.adjustments.submit');
        Route::post('/waste', [StockAdjustmentController::class, 'waste'])->name('api.inventory.waste.record');
    });
});
