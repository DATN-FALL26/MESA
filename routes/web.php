<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MOD03 - Quản lý gọi món
|--------------------------------------------------------------------------
| Route tạm dùng để phát triển Module 3 độc lập trên local.
*/

// Mở project sẽ đi thẳng vào Module 3
Route::get('/', function () {
    return redirect()->route('orders.index');
});

// Trang chính quản lý gọi món
Route::get('/orders', [OrderController::class, 'index'])
    ->name('orders.index');

Route::get('/orders/session/{sessionId}', [OrderController::class, 'showSession'])
    ->name('orders.session');

Route::post('/orders/session/{sessionId}/start', [OrderController::class, 'startOrder'])
    ->name('orders.start');

Route::post('/orders/{orderId}/items/{menuItemId}', [OrderController::class, 'addItem'])
    ->name('orders.items.add');

Route::post('/orders/{orderId}/items/{orderItemId}/decrease', [OrderController::class, 'decreaseItem'])
    ->name('orders.items.decrease');

Route::post('/orders/{orderId}/items/{orderItemId}/note', [OrderController::class, 'updateItemNote'])
    ->name('orders.items.note');

Route::post('/orders/{orderId}/send-kitchen', [OrderController::class, 'sendToKitchen'])
    ->name('orders.send-kitchen');

Route::post('/orders/{orderId}/update-kitchen', [OrderController::class, 'updateKitchen'])
    ->name('orders.update-kitchen');

Route::post('/orders/tables/{tableId}/open', [OrderController::class, 'openTable'])
    ->name('orders.table.open');