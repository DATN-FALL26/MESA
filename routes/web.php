<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\PermissionController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\RoleController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Kitchen\KitchenDisplayController;
use App\Http\Controllers\Kitchen\ServeController;
use App\Http\Controllers\Menu\BranchMenuController;
use App\Http\Controllers\Menu\CategoryController;
use App\Http\Controllers\Menu\ItemVariantController;
use App\Http\Controllers\Menu\MenuItemController;
use App\Http\Controllers\Menu\ModifierController;
use App\Http\Controllers\Menu\ModifierGroupController;
use App\Http\Controllers\PublicDashboardController;
use App\Http\Controllers\table\TableController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\OrderController;
// use Symfony\Component\Routing\Router;


Route::middleware(['auth', 'permission:dashboard.view'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reports', [DashboardController::class, 'reports'])->name('reports');
    Route::get('/api/revenue', [DashboardController::class, 'getRevenueApi'])->name('api.revenue');

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
    return redirect()->route('dashboard');
});

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
| Chưa đăng nhập
*/
Route::middleware('guest')->group(function () {
    // Hiển thị trang đăng nhập
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

    // Xử lý đăng nhập
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
    Route::get('/forgot-password/result', [ForgotPasswordController::class, 'showResetResult'])
        ->name('password.reset.result');

});
    // Đăng nhập thành công từ link Url , hoặc Qr code
Route::get('/dashboard', [PublicDashboardController::class, 'show'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
| Đã đăng nhập
*/
Route::middleware('auth')->group(function () {
    Route::get('/tables', [TableController::class, 'index'])
        ->middleware('permission:table.view')
        ->name('tables.index');

    // Đăng xuất hệ thống
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    /*
    |--------------------------------------------------------------------------
    | Hồ sơ cá nhân
    |--------------------------------------------------------------------------
    */
    // Xem hồ sơ
    Route::get('/profile', [ProfileController::class, 'show'])
        ->middleware('permission:profile.view')
        ->name('profile');

    // Cập nhật thông tin cá nhân
    Route::put('/profile', [ProfileController::class, 'update'])
        ->middleware('permission:profile.update')
        ->name('profile.update');

    // Đổi mật khẩu
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])
        ->middleware('permission:profile.update')
        ->name('profile.password.update');

    /*
    |--------------------------------------------------------------------------
    | Quản lý User
    |--------------------------------------------------------------------------
    */
    // Danh sách tài khoản
    Route::middleware('permission:users.view')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
    });
    // Tạo tài khoản; quyền được kiểm tra trong controller để hỗ trợ vai trò quản trị viên.
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');

    // Cập nhật tài khoản
    Route::middleware('permission:users.update')->group(function () {
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    });

    /*
    |--------------------------------------------------------------------------
    | Quản lý Role
    |--------------------------------------------------------------------------
    */
    // Danh sách vai trò
    Route::middleware('permission:roles.view')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    });

    // Tạo vai trò
    Route::middleware('permission:roles.create')->group(function () {
        Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    });

    // Cập nhật vai trò
    Route::middleware('permission:roles.update')->group(function () {
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });

    // Xóa vai trò
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('permission:roles.delete')
        ->name('roles.destroy');

    /*
    |--------------------------------------------------------------------------
    | Quản lý Permission
    |--------------------------------------------------------------------------
    */
    // Danh sách quyền
    Route::middleware('permission:permissions.view')->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index'])
            ->name('permissions.index');
    });

    // Tạo quyền
    Route::middleware('permission:permissions.create')->group(function () {
        Route::get('/permissions/create', [PermissionController::class, 'create'])
            ->name('permissions.create');

        Route::post('/permissions', [PermissionController::class, 'store'])
            ->name('permissions.store');
    });

    // Cập nhật quyền
    Route::middleware('permission:permissions.update')->group(function () {
        Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])
            ->name('permissions.edit');

        Route::put('/permissions/{permission}', [PermissionController::class, 'update'])
            ->name('permissions.update');
    });

    // Xóa quyền
    Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])
        ->middleware('permission:permissions.delete')
        ->name('permissions.destroy');
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
        ->middleware('permission:order.batch.release')
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
    // Màn hình KDS và phần bảng phiếu bếp tự làm mới
    Route::middleware('permission:kitchen.view')->group(function () {
        Route::get('/', [KitchenDisplayController::class, 'index'])->name('index');
        Route::get('/board', [KitchenDisplayController::class, 'board'])->name('board');
    });

    // Đổi trạng thái món / cả phiếu: Mới → Đang nấu → Hoàn thành
    Route::middleware('permission:kitchen.update_status')->group(function () {
        Route::patch('/ticket-items/{kitchenTicketItem}/advance', [KitchenDisplayController::class, 'advanceItem'])
            ->name('ticket-items.advance');
        Route::patch('/tickets/{kitchenTicket}/advance', [KitchenDisplayController::class, 'advanceTicket'])
            ->name('tickets.advance');
    });

    // In lại phiếu bếp (INV-17)
    Route::post('/tickets/{kitchenTicket}/print', [KitchenDisplayController::class, 'printTicket'])
        ->middleware('permission:kitchen.print')
        ->name('tickets.print');

    // Màn hình phục vụ nhận món xong (INV-18)
    Route::middleware('permission:order.serve')->prefix('serve')->name('serve.')->group(function () {
        Route::get('/', [ServeController::class, 'index'])->name('index');
        Route::get('/board', [ServeController::class, 'board'])->name('board');
        Route::post('/orders/{order}/served', [ServeController::class, 'markServed'])->name('served');
    });
});


/*
|--------------------------------------------------------------------------
| MOD03 - Quản lý Thực đơn
|--------------------------------------------------------------------------
| menu.view: xem | menu.manage: thêm/sửa/xóa món, giá | menu.availability.update: báo hết/còn
*/
Route::middleware('auth')->prefix('menu')->name('menu.')->group(function () {
    // Xem danh sách
    Route::middleware('permission:menu.view')->group(function () {
        Route::get('/items', [MenuItemController::class, 'index'])->name('items.index');
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/modifier-groups', [ModifierGroupController::class, 'index'])->name('modifier-groups.index');
        Route::get('/availability', [BranchMenuController::class, 'index'])->name('availability.index');
    });

    // Báo hết / còn món trong ngày
    Route::middleware('permission:menu.availability.update,menu.manage')->group(function () {
        Route::patch('/availability/{branch}/{menuItem}', [BranchMenuController::class, 'updateAvailability'])
            ->name('availability.update');
        Route::post('/availability/{branch}/reset', [BranchMenuController::class, 'resetAvailability'])
            ->name('availability.reset');
    });

    // Quản trị thực đơn
    Route::middleware('permission:menu.manage')->group(function () {
        // Danh mục
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        // Món ăn
        Route::get('/items/create', [MenuItemController::class, 'create'])->name('items.create');
        Route::post('/items', [MenuItemController::class, 'store'])->name('items.store');
        Route::get('/items/{menuItem}/edit', [MenuItemController::class, 'edit'])->name('items.edit');
        Route::put('/items/{menuItem}', [MenuItemController::class, 'update'])->name('items.update');
        Route::patch('/items/{menuItem}/toggle', [MenuItemController::class, 'toggle'])->name('items.toggle');
        Route::delete('/items/{menuItem}', [MenuItemController::class, 'destroy'])->name('items.destroy');
        Route::put('/items/{menuItem}/modifier-groups', [MenuItemController::class, 'syncModifierGroups'])
            ->name('items.modifier-groups.sync');

        // Biến thể (size) của món
        Route::post('/items/{menuItem}/variants', [ItemVariantController::class, 'store'])->name('variants.store');
        Route::put('/variants/{variant}', [ItemVariantController::class, 'update'])->name('variants.update');
        Route::delete('/variants/{variant}', [ItemVariantController::class, 'destroy'])->name('variants.destroy');

        // Nhóm topping và topping
        Route::post('/modifier-groups', [ModifierGroupController::class, 'store'])->name('modifier-groups.store');
        Route::get('/modifier-groups/{modifierGroup}/edit', [ModifierGroupController::class, 'edit'])->name('modifier-groups.edit');
        Route::put('/modifier-groups/{modifierGroup}', [ModifierGroupController::class, 'update'])->name('modifier-groups.update');
        Route::delete('/modifier-groups/{modifierGroup}', [ModifierGroupController::class, 'destroy'])->name('modifier-groups.destroy');
        Route::post('/modifier-groups/{modifierGroup}/modifiers', [ModifierController::class, 'store'])->name('modifiers.store');
        Route::put('/modifiers/{modifier}', [ModifierController::class, 'update'])->name('modifiers.update');
        Route::delete('/modifiers/{modifier}', [ModifierController::class, 'destroy'])->name('modifiers.destroy');

        // Giá riêng theo chi nhánh
        Route::put('/availability/{branch}/{menuItem}/price', [BranchMenuController::class, 'updatePrice'])
            ->name('availability.price');
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
