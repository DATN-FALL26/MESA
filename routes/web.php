<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PermissionController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\RoleController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\CustomerOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Root
|--------------------------------------------------------------------------
| Điều hướng mặc định khi truy cập hệ thống
*/
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
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

    // Đăng ký tài khoản khách hàng với vai trò mặc định
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::get('/dashboard', [CustomerOrderController::class, 'dashboard'])->name('dashboard');
Route::get('/customer/table/{diningTable}', [CustomerOrderController::class, 'table'])
    ->middleware('signed')
    ->name('customer.table');
Route::post('/customer/table/{diningTable}/orders', [CustomerOrderController::class, 'store'])
    ->middleware(['signed', 'throttle:10,1'])
    ->name('customer.orders.store');

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
    // Tọa Qr link cho bàn ăn do nhân viên nhà hàng tạo
    Route::get('/customer/table/{diningTable}/qr-link', [CustomerOrderController::class, 'qrUrl'])
        ->middleware('permission:table.view')
        ->name('customer.table.qr-link');

    // Tạo tài khoản
    Route::middleware('permission:users.create')->group(function () {
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
    });

    // Cập nhật tài khoản
    Route::middleware('permission:users.update')->group(function () {
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    });

    // Xóa tài khoản
    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:users.delete')
        ->name('users.destroy');

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