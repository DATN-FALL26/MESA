<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

// Route trỏ tới trang Tổng quan Dashboard qua Controller
Route::get('/dashboard', [DashboardController::class, 'index']);

// Route trỏ tới trang Báo cáo chi tiết qua Controller
Route::get('/admin/reports', [DashboardController::class, 'reports']);
