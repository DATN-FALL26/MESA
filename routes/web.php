<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Trang chủ công khai cho mọi người
Route::get('/', function () {
    return view('welcome');
});

// Trang Dashboard chung (dành cho người đã đăng nhập và xác thực email)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


// ==========================================
// 1. KHU VỰC DÀNH RIÊNG CHO CEO
// ==========================================
Route::middleware(['auth', 'role:ceo'])->prefix('ceo')->name('ceo.')->group(function () {
    Route::get('/dashboard', function () {
        return view('ceo.dashboard');
    })->name('dashboard');
});


// ==========================================
// 2. KHU VỰC DÀNH RIÊNG CHO MANAGER (QUẢN LÝ)
// ==========================================
Route::middleware(['auth', 'role:manager'])->prefix('manager')->name('manager.')->group(function () {
    Route::get('/dashboard', function () {
        return view('manager.dashboard');
    })->name('dashboard');
});


// ==========================================
// 3. KHU VỰC DÀNH CHO ĐẦU BẾP (CHEF / KITCHEN)
// ==========================================
Route::middleware(['auth', 'role:chef,manager,ceo'])->prefix('kitchen')->name('kitchen.')->group(function () {
    Route::get('/orders', function () {
        return view('kitchen.orders');
    })->name('orders');
});


// ==========================================
// 4. KHU VỰC DÀNH CHO NHÂN VIÊN KHO HÀNG (WAREHOUSE)
// ==========================================
Route::middleware(['auth', 'role:warehouse,manager,ceo'])->prefix('warehouse')->name('warehouse.')->group(function () {
    Route::get('/inventory', function () {
        return view('warehouse.inventory');
    })->name('inventory');
});


// ==========================================
// 5. KHU VỰC DÀNH CHO NHÂN VIÊN PHỤC VỤ (STAFF)
// ==========================================
Route::middleware(['auth', 'role:staff,manager,ceo'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/tables', function () {
        return view('staff.tables');
    })->name('tables');
});


// ==========================================
// 6. KHU VỰC QUẢN TRỊ HỆ THỐNG (CHỈ CEO VÀ MANAGER MỚI ĐƯỢC VÀO)
// ==========================================
Route::middleware(['auth', 'role:ceo,manager'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::put('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.updateRole');
});


// ==========================================
// 7. KHU VỰC CÁ NHÂN (AI ĐĂNG NHẬP RỒI CŨNG DÙNG ĐƯỢC)
// ==========================================
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Nạp các route đăng nhập, đăng ký mặc định của Laravel Breeze
require __DIR__.'/auth.php';