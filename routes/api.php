<?php

use App\Http\Controllers\TableController;
use Illuminate\Support\Facades\Route;

Route::get('/tables/status', [TableController::class, 'status'])->name('api.tables.status');
Route::post('/tables', [TableController::class, 'store']);
Route::put('/tables/{id}', [TableController::class, 'update']);
Route::delete('/tables/{id}', [TableController::class, 'destroy']);
