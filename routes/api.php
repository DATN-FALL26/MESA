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
    // 3. MASTER DATA: NHÀ CUNG CẤP & HỒ SƠ ATVSTP (SUPPLIERS & DOCUMENTS)
    // ==========================================
    Route::get('/suppliers/active', [SupplierController::class, 'active'])
        ->name('api.inventory.suppliers.active');
    Route::get('/supplier-documents/types', [SupplierDocumentController::class, 'types'])
        ->name('api.inventory.supplier-documents.types');
    Route::get('/supplier-documents/expiring', [SupplierDocumentController::class, 'expiring'])
        ->name('api.inventory.supplier-documents.expiring');
    Route::apiResource('suppliers', SupplierController::class)
        ->names('api.inventory.suppliers');
    Route::get('/suppliers/{supplier}/documents', [SupplierDocumentController::class, 'index'])
        ->name('api.inventory.suppliers.documents.index');
    Route::post('/suppliers/{supplier}/documents', [SupplierDocumentController::class, 'store'])
        ->name('api.inventory.suppliers.documents.store');
    Route::get('/suppliers/{supplier}/documents/{document}', [SupplierDocumentController::class, 'show'])
        ->name('api.inventory.suppliers.documents.show');
    Route::post('/suppliers/{supplier}/documents/{document}', [SupplierDocumentController::class, 'update'])
        ->name('api.inventory.suppliers.documents.update');
    Route::delete('/suppliers/{supplier}/documents/{document}', [SupplierDocumentController::class, 'destroy'])
        ->name('api.inventory.suppliers.documents.destroy');

    // ==========================================
    // 3B. MASTER DATA: KHO & CHI NHÁNH / CƠ SỞ
    // ==========================================
    Route::get('/warehouses', [WarehouseController::class, 'index'])
        ->name('api.inventory.warehouses.index');
    Route::get('/branches', [WarehouseController::class, 'branches'])
        ->name('api.inventory.branches.index');

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

    // ==========================================
    // 5. ĐƠN ĐẶT & NHẬP HÀNG TỪ NCC (PURCHASE ORDERS)
    // ==========================================
    Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])
        ->name('api.inventory.purchase-orders.index');
    Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])
        ->name('api.inventory.purchase-orders.store');
    Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])
        ->name('api.inventory.purchase-orders.show');
    Route::put('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])
        ->name('api.inventory.purchase-orders.update');
    Route::post('/purchase-orders/{purchaseOrder}/submit', [PurchaseOrderController::class, 'submit'])
        ->name('api.inventory.purchase-orders.submit');
    Route::post('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])
        ->name('api.inventory.purchase-orders.approve');
    Route::post('/purchase-orders/{purchaseOrder}/reject', [PurchaseOrderController::class, 'reject'])
        ->name('api.inventory.purchase-orders.reject');
    Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])
        ->name('api.inventory.purchase-orders.cancel');
    Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])
        ->name('api.inventory.purchase-orders.receive');

    // ==========================================
    // 6. KIỂM KÊ, CÂN ĐỐI & HAO HỤT (STOCK ADJUSTMENTS & WASTE)
    // ==========================================
    Route::get('/adjustments', [StockAdjustmentController::class, 'index'])
        ->name('api.inventory.adjustments.index');
    Route::post('/adjustments', [StockAdjustmentController::class, 'store'])
        ->name('api.inventory.adjustments.store');
    Route::get('/adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'show'])
        ->name('api.inventory.adjustments.show');
    Route::put('/adjustments/{stockAdjustment}/items', [StockAdjustmentController::class, 'updateItems'])
        ->name('api.inventory.adjustments.update-items');
    Route::post('/adjustments/{stockAdjustment}/submit', [StockAdjustmentController::class, 'submit'])
        ->name('api.inventory.adjustments.submit');
    Route::post('/adjustments/{stockAdjustment}/approve', [StockAdjustmentController::class, 'approve'])
        ->name('api.inventory.adjustments.approve');
    Route::post('/adjustments/{stockAdjustment}/reject', [StockAdjustmentController::class, 'reject'])
        ->name('api.inventory.adjustments.reject');
    Route::post('/waste', [StockAdjustmentController::class, 'waste'])
        ->name('api.inventory.waste.record');
});
