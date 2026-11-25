<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\PermissionController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\RoleController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Kitchen\KitchenDailyCheckinController;
use App\Http\Controllers\Kitchen\KitchenDisplayController;
use App\Http\Controllers\Kitchen\ServeController;
use App\Http\Controllers\Menu\BranchMenuController;
use App\Http\Controllers\Menu\CategoryController;
use App\Http\Controllers\Menu\ItemVariantController;
use App\Http\Controllers\Menu\MenuItemController;
use App\Http\Controllers\Menu\ModifierController;
use App\Http\Controllers\Menu\ModifierGroupController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\PublicDashboardController;
use App\Http\Controllers\table\TableController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:dashboard.view'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/orders/{id}/detail', [DashboardController::class, 'orderDetail'])->name('dashboard.order-detail');
    Route::get('/reports', [DashboardController::class, 'reports'])->name('reports');
    Route::get('/reports/export-excel', [DashboardController::class, 'exportExcel'])->name('reports.export');
    Route::get('/api/revenue', [DashboardController::class, 'getRevenueApi'])->name('api.revenue');
    Route::get('/dashboard/api-payment-stats', [DashboardController::class, 'getPaymentMethodStats'])->name('dashboard.payment_stats');
    Route::get('/dashboard/api-hourly-traffic', [DashboardController::class, 'getHourlyTrafficStats'])->name('dashboard.hourly_traffic');
    Route::get('/reports/storage-status', [DashboardController::class, 'getFoodStorageReport'])->name('reports.storage');
});

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

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
    Route::get('/forgot-password/result', [ForgotPasswordController::class, 'showResetResult'])
        ->name('password.reset.result');
});

Route::get('/dashboard', [PublicDashboardController::class, 'show'])->name('dashboard');
Route::get('/public-dashboard', [PublicDashboardController::class, 'show'])->name('public.dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/tables', [TableController::class, 'index'])
        ->middleware('permission:table.view')
        ->name('tables.index');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/profile', [ProfileController::class, 'show'])
        ->middleware('permission:profile.view')
        ->name('profile');

    Route::put('/profile', [ProfileController::class, 'update'])
        ->middleware('permission:profile.update')
        ->name('profile.update');

    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])
        ->middleware('permission:profile.update')
        ->name('profile.password.update');

    Route::middleware('permission:users.view')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
    });
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');

    Route::middleware('permission:users.update')->group(function () {
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    });

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

    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('permission:roles.delete')
        ->name('roles.destroy');

    Route::middleware('permission:permissions.view')->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index'])
            ->name('permissions.index');
    });

    Route::middleware('permission:permissions.create')->group(function () {
        Route::get('/permissions/create', [PermissionController::class, 'create'])
            ->name('permissions.create');

        Route::post('/permissions', [PermissionController::class, 'store'])
            ->name('permissions.store');
    });

    Route::middleware('permission:permissions.update')->group(function () {
        Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])
            ->name('permissions.edit');

        Route::put('/permissions/{permission}', [PermissionController::class, 'update'])
            ->name('permissions.update');
    });

    Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])
        ->middleware('permission:permissions.delete')
        ->name('permissions.destroy');
});

Route::middleware('auth')->prefix('orders')->name('orders.')->group(function () {
    Route::get('/session/{sessionId}', [OrderController::class, 'showSession'])
        ->middleware('permission:order.view')
        ->name('session');

    Route::post('/session/{sessionId}/start', [OrderController::class, 'startOrder'])
        ->middleware('permission:order.create')
        ->name('start');

    Route::post('/{orderId}/items/{menuItemId}', [OrderController::class, 'addItem'])
        ->middleware('permission:order.update')
        ->name('items.add');

    Route::post('/{orderId}/items/{orderItemId}/decrease', [OrderController::class, 'decreaseItem'])
        ->middleware('permission:order.update')
        ->name('items.decrease');

    Route::post('/{orderId}/items/{orderItemId}/note', [OrderController::class, 'updateItemNote'])
        ->middleware('permission:order.update')
        ->name('items.note');

    Route::post('/{orderId}/send-kitchen', [OrderController::class, 'sendToKitchen'])
        ->middleware('permission:order.batch.release,order.update,order.create')
        ->name('send-kitchen');

    Route::post('/{orderId}/update-kitchen', [OrderController::class, 'updateKitchen'])
        ->middleware('permission:order.update')
        ->name('update-kitchen');
});

Route::middleware('auth')->prefix('kitchen')->name('kitchen.')->group(function () {
    Route::middleware('permission:kitchen.view')->group(function () {
        Route::get('/', [KitchenDisplayController::class, 'index'])->name('index');
        Route::get('/board', [KitchenDisplayController::class, 'board'])->name('board');
        Route::post('/daily-checkin', [KitchenDailyCheckinController::class, 'store'])->name('daily-checkin.store');
    });

    Route::middleware('permission:kitchen.update_status')->group(function () {
        Route::patch('/ticket-items/{kitchenTicketItem}/advance', [KitchenDisplayController::class, 'advanceItem'])
            ->name('ticket-items.advance');
        Route::patch('/tickets/{kitchenTicket}/advance', [KitchenDisplayController::class, 'advanceTicket'])
            ->name('tickets.advance');
    });

    Route::post('/tickets/{kitchenTicket}/print', [KitchenDisplayController::class, 'printTicket'])
        ->middleware('permission:kitchen.print')
        ->name('tickets.print');

    Route::middleware('permission:order.serve')->prefix('serve')->name('serve.')->group(function () {
        Route::get('/', [ServeController::class, 'index'])->name('index');
        Route::get('/board', [ServeController::class, 'board'])->name('board');
        Route::post('/orders/{order}/served', [ServeController::class, 'markServed'])->name('served');
    });
});

Route::middleware('auth')->prefix('menu')->name('menu.')->group(function () {
    Route::middleware('permission:menu.view')->group(function () {
        Route::get('/items', [MenuItemController::class, 'index'])->name('items.index');
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/modifier-groups', [ModifierGroupController::class, 'index'])->name('modifier-groups.index');
        Route::get('/availability', [BranchMenuController::class, 'index'])->name('availability.index');
    });

    Route::middleware('permission:menu.availability.update,menu.manage')->group(function () {
        Route::patch('/availability/{branch}/{menuItem}', [BranchMenuController::class, 'updateAvailability'])
            ->name('availability.update');
        Route::post('/availability/{branch}/reset', [BranchMenuController::class, 'resetAvailability'])
            ->name('availability.reset');
    });

    Route::middleware('permission:menu.manage')->group(function () {
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/items/create', [MenuItemController::class, 'create'])->name('items.create');
        Route::post('/items', [MenuItemController::class, 'store'])->name('items.store');
        Route::get('/items/{menuItem}/edit', [MenuItemController::class, 'edit'])->name('items.edit');
        Route::put('/items/{menuItem}', [MenuItemController::class, 'update'])->name('items.update');
        Route::patch('/items/{menuItem}/toggle', [MenuItemController::class, 'toggle'])->name('items.toggle');
        Route::delete('/items/{menuItem}', [MenuItemController::class, 'destroy'])->name('items.destroy');
        Route::put('/items/{menuItem}/modifier-groups', [MenuItemController::class, 'syncModifierGroups'])
            ->name('items.modifier-groups.sync');

        Route::post('/items/{menuItem}/variants', [ItemVariantController::class, 'store'])->name('variants.store');
        Route::put('/variants/{variant}', [ItemVariantController::class, 'update'])->name('variants.update');
        Route::delete('/variants/{variant}', [ItemVariantController::class, 'destroy'])->name('variants.destroy');

        Route::post('/modifier-groups', [ModifierGroupController::class, 'store'])->name('modifier-groups.store');
        Route::get('/modifier-groups/{modifierGroup}/edit', [ModifierGroupController::class, 'edit'])->name('modifier-groups.edit');
        Route::put('/modifier-groups/{modifierGroup}', [ModifierGroupController::class, 'update'])->name('modifier-groups.update');
        Route::delete('/modifier-groups/{modifierGroup}', [ModifierGroupController::class, 'destroy'])->name('modifier-groups.destroy');
        Route::post('/modifier-groups/{modifierGroup}/modifiers', [ModifierController::class, 'store'])->name('modifiers.store');
        Route::put('/modifiers/{modifier}', [ModifierController::class, 'update'])->name('modifiers.update');
        Route::delete('/modifiers/{modifier}', [ModifierController::class, 'destroy'])->name('modifiers.destroy');

        Route::put('/availability/{branch}/{menuItem}/price', [BranchMenuController::class, 'updatePrice'])
            ->name('availability.price');
    });
});

Route::prefix('inventory')->name('inventory.')->group(function () {
    Route::view('/ingredients', 'inventory.ingredients')->name('ingredients');
    Route::view('/suppliers', 'inventory.suppliers')->name('suppliers');
    Route::view('/recipes', 'inventory.recipes')->name('recipes');
    Route::get('/stock-movements', function () {
        $branches = \App\Models\Branch::where('status', \App\Enums\BranchStatus::ACTIVE)->orderBy('id')->get();
        $warehouses = \App\Models\Warehouse::where('is_active', true)->with('branch')->orderBy('branch_id')->get();
        $ingredients = \App\Models\Ingredient::where('is_active', true)->orderBy('name')->get();
        return view('inventory.stock-movements', compact('branches', 'warehouses', 'ingredients'));
    })->name('stock-movements');
    Route::get('/purchase-orders', function () {
        $branches = \App\Models\Branch::where('status', \App\Enums\BranchStatus::ACTIVE)->orderBy('id')->get();
        $warehouses = \App\Models\Warehouse::where('is_active', true)->with('branch')->orderBy('id')->get();
        return view('inventory.purchase-orders', compact('branches', 'warehouses'));
    })->name('purchase-orders');
    Route::get('/adjustments', function () {
        $branches = \App\Models\Branch::where('status', \App\Enums\BranchStatus::ACTIVE)->orderBy('id')->get();
        $warehouses = \App\Models\Warehouse::where('is_active', true)->with('branch')->orderBy('id')->get();
        return view('inventory.adjustments', compact('branches', 'warehouses'));
    })->name('adjustments');
});

Route::middleware('auth')->prefix('payments')->name('payments.')->group(function () {
    Route::get('/orders/{order}', [PaymentController::class, 'show'])
        ->middleware('permission:payment.request')
        ->name('show');

    Route::middleware('permission:payment.request')->group(function () {
        Route::post('/orders/{order}/start', [PaymentController::class, 'start'])->name('start');
        Route::get('/orders/{order}/cash', [PaymentController::class, 'cash'])->name('cash');
        Route::post('/orders/{order}/cash', [PaymentController::class, 'payCash'])->name('cash.pay');
        Route::get('/{payment}/qr', [PaymentController::class, 'qr'])->name('qr');
        Route::post('/{payment}/qr/confirm', [PaymentController::class, 'confirmQr'])->name('qr.confirm');
        Route::post('/{payment}/qr/fail', [PaymentController::class, 'failQr'])->name('qr.fail');
    });
});
