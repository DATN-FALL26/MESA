<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\PermissionController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\RoleController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Kitchen\KitchenDisplayController;
use App\Http\Controllers\Kitchen\ServeController;
use App\Http\Controllers\PublicDashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
// use Symfony\Component\Routing\Router;

/*
|--------------------------------------------------------------------------
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
