<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\PermissionController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\RoleController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Kitchen\KitchenDailyCheckinController;
use App\Http\Controllers\Kitchen\KitchenDisplayController;
use App\Http\Controllers\Kitchen\ServeController;
use App\Http\Controllers\PublicDashboardController;
use App\Http\Controllers\table\TableController;
use App\Http\Controllers\Admin\DashboardController;
// use Symfony\Component\Routing\Router;


Route::prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/reports', [DashboardController::class, 'reports'])->name('admin.reports');
    Route::get('/api/revenue', [DashboardController::class, 'getRevenueApi'])->name('admin.api.revenue');
});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // Route API cho biểu đồ Dashboard (Thanh toán & Khung giờ)
    Route::get('/dashboard/api-payment-stats', [DashboardController::class, 'getPaymentMethodStats'])->name('dashboard.payment_stats');
    Route::get('/dashboard/api-hourly-traffic', [DashboardController::class, 'getHourlyTrafficStats'])->name('dashboard.hourly_traffic');

    // Route báo cáo kho & hạn sử dụng
    Route::get('/reports/storage-status', [DashboardController::class, 'getFoodStorageReport'])->name('reports.storage');
});

/*
| Root
|--------------------------------------------------------------------------
| Điều hướng mặc định khi truy cập hệ thống
*/
Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

/*
|--------------------------------------------------------------------------
| Guest Routes (Chưa đăng nhập)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showRequestForm'])
        ->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendOtp'])
        ->middleware('throttle:10,60')
        ->name('password.email');
    Route::get('/forgot-password/verify', [ForgotPasswordController::class, 'showVerifyForm'])
        ->name('password.otp.form');
    Route::post('/forgot-password/verify', [ForgotPasswordController::class, 'verifyOtp'])
        ->name('password.otp.verify');
    Route::post('/forgot-password/resend', [ForgotPasswordController::class, 'resendOtp'])
        ->middleware('throttle:10,60')
        ->name('password.otp.resend');
    Route::get('/forgot-password/reset', [ForgotPasswordController::class, 'showResetForm'])
        ->name('password.reset.form');
    Route::put('/forgot-password/reset', [ForgotPasswordController::class, 'resetPassword'])
        ->name('password.reset');

});

// Trang public dashboard qua QR code hoặc link ngoài
Route::get('/public-dashboard', [PublicDashboardController::class, 'show'])->name('public.dashboard');

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Đã đăng nhập)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/tables', [TableController::class, 'index'])
        ->middleware('permission:table.view')
        ->name('tables.index');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    /* Hồ sơ cá nhân */
    Route::get('/profile', [ProfileController::class, 'show'])->middleware('permission:profile.view')->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->middleware('permission:profile.update')->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->middleware('permission:profile.update')->name('profile.password.update');

    /* Quản lý User */
    Route::middleware('permission:users.view')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
    });
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::middleware('permission:users.update')->group(function () {
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    });

    /* Quản lý Role */
    Route::middleware('permission:roles.view')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    });
    Route::middleware('permission:roles.create')->group(function () {
        Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    });
    Route::middleware('permission:roles.update')->group(function () {
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete')->name('roles.destroy');

    /* Quản lý Permission */
    Route::middleware('permission:permissions.view')->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
    });
    Route::middleware('permission:permissions.create')->group(function () {
        Route::get('/permissions/create', [PermissionController::class, 'create'])->name('permissions.create');
        Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
    });
    Route::middleware('permission:permissions.update')->group(function () {
        Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
        Route::put('/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
    });
    Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->middleware('permission:permissions.delete')->name('permissions.destroy');
});

/*
|--------------------------------------------------------------------------
| MOD03 - Quản lý gọi món
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('orders')->name('orders.')->group(function () {
    Route::get('/session/{sessionId}', [OrderController::class, 'showSession'])->middleware('permission:order.view')->name('session');
    Route::post('/session/{sessionId}/start', [OrderController::class, 'startOrder'])->middleware('permission:order.create')->name('start');
    Route::post('/{orderId}/items/{menuItemId}', [OrderController::class, 'addItem'])->middleware('permission:order.update')->name('items.add');
    Route::post('/{orderId}/items/{orderItemId}/decrease', [OrderController::class, 'decreaseItem'])->middleware('permission:order.update')->name('items.decrease');
    Route::post('/{orderId}/items/{orderItemId}/note', [OrderController::class, 'updateItemNote'])->middleware('permission:order.update')->name('items.note');
    Route::post('/{orderId}/send-kitchen', [OrderController::class, 'sendToKitchen'])->middleware('permission:order.batch.release,order.update,order.create')->name('send-kitchen');
    Route::post('/{orderId}/update-kitchen', [OrderController::class, 'updateKitchen'])->middleware('permission:order.update')->name('update-kitchen');
});


/*
|--------------------------------------------------------------------------
| MOD03 - Quản lý gọi món
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('orders')->name('orders.')->group(function () {

    // Xem màn gọi món của Dining Session
    Route::get('/session/{sessionId}', [OrderController::class, 'showSession'])
        ->middleware('permission:order.view')
        ->name('session');

    // Tạo Order cho Dining Session
    Route::post('/session/{sessionId}/start', [OrderController::class, 'startOrder'])
        ->middleware('permission:order.create')
        ->name('start');

    // Thêm / tăng món
    Route::post('/{orderId}/items/{menuItemId}', [OrderController::class, 'addItem'])
        ->middleware('permission:order.update')
        ->name('items.add');

    // Giảm món
    Route::post('/{orderId}/items/{orderItemId}/decrease', [OrderController::class, 'decreaseItem'])
        ->middleware('permission:order.update')
        ->name('items.decrease');

    // Ghi chú món
    Route::post('/{orderId}/items/{orderItemId}/note', [OrderController::class, 'updateItemNote'])
        ->middleware('permission:order.update')
        ->name('items.note');

    // Gửi Batch xuống bếp
    Route::post('/{orderId}/send-kitchen', [OrderController::class, 'sendToKitchen'])
        ->middleware('permission:order.batch.release,order.update,order.create')
        ->name('send-kitchen');

    // Cập nhật món đã gửi nhưng bếp chưa bắt đầu
    Route::post('/{orderId}/update-kitchen', [OrderController::class, 'updateKitchen'])
        ->middleware('permission:order.update')
        ->name('update-kitchen');
});

/*
|--------------------------------------------------------------------------
| MOD04 - Bếp / KDS
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('kitchen')->name('kitchen.')->group(function () {
    Route::middleware('permission:kitchen.view')->group(function () {
        Route::get('/', [KitchenDisplayController::class, 'index'])->name('index');
        Route::get('/board', [KitchenDisplayController::class, 'board'])->name('board');
        Route::post('/daily-checkin', [KitchenDailyCheckinController::class, 'store'])->name('daily-checkin.store');
    });

    Route::middleware('permission:kitchen.update_status')->group(function () {
        Route::patch('/ticket-items/{kitchenTicketItem}/advance', [KitchenDisplayController::class, 'advanceItem'])->name('ticket-items.advance');
        Route::patch('/tickets/{kitchenTicket}/advance', [KitchenDisplayController::class, 'advanceTicket'])->name('tickets.advance');
    });

    Route::post('/tickets/{kitchenTicket}/print', [KitchenDisplayController::class, 'printTicket'])->middleware('permission:kitchen.print')->name('tickets.print');

    Route::middleware('permission:order.serve')->prefix('serve')->name('serve.')->group(function () {
        Route::get('/', [ServeController::class, 'index'])->name('index');
        Route::get('/board', [ServeController::class, 'board'])->name('board');
        Route::post('/orders/{order}/served', [ServeController::class, 'markServed'])->name('served');
    });
});


/*
    |--------------------------------------------------------------------------
    | Phân hệ Kho & Định lượng (Module 07)
    |--------------------------------------------------------------------------
    */
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::view('/ingredients', 'inventory.ingredients')->name('ingredients');
        Route::view('/suppliers', 'inventory.suppliers')->name('suppliers');
        Route::view('/recipes', 'inventory.recipes')->name('recipes');
        Route::view('/stock-movements', 'inventory.stock-movements')->name('stock-movements');
        Route::view('/purchase-orders', 'inventory.purchase-orders')->name('purchase-orders');
        Route::view('/adjustments', 'inventory.adjustments')->name('adjustments');
    });
