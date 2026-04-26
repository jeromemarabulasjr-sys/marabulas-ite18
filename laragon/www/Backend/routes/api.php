<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ActivityTypeController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\RestockController;
use App\Http\Controllers\Api\InventoryReportController;

// Authentication routes
Route::post('/login', [AuthController::class, 'login'])->name('user.login');
Route::post('/signup', [AuthController::class, 'signup'])->name('user.signup');
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/send-verification-code', [AuthController::class, 'sendVerificationCode']);
Route::post('/profile/update', [AuthController::class, 'updateProfile']);

// Credential management routes
Route::get('/credentials', [AuthController::class, 'getCredentials']);
Route::delete('/credentials/{id}', [AuthController::class, 'removeCredential']);

// User routes
Route::get('/user', [UserController::class, 'index']);
Route::post('/user', [UserController::class, 'store']);

// Product/Inventory routes
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::post('/', [ProductController::class, 'store']);
    Route::get('/low-stock', [ProductController::class, 'lowStock']);
    Route::get('/export', [ProductController::class, 'export']);
    Route::post('/import', [ProductController::class, 'import']);
    Route::delete('/all', [ProductController::class, 'destroyAll']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::put('/{id}', [ProductController::class, 'update']);
    Route::delete('/{id}', [ProductController::class, 'destroy']);
});

// Activity log routes
Route::prefix('activity')->group(function () {
    Route::get('/', [ActivityController::class, 'index']);
    Route::post('/', [ActivityController::class, 'store']);
    Route::delete('/clear', [ActivityController::class, 'clear']);
});

// Activity types routes
Route::prefix('activity-types')->group(function () {
    Route::get('/', [ActivityTypeController::class, 'index']);
    Route::get('/{typeKey}', [ActivityTypeController::class, 'show']);
    Route::post('/', [ActivityTypeController::class, 'store']);
    Route::put('/{typeKey}', [ActivityTypeController::class, 'update']);
    Route::delete('/{typeKey}', [ActivityTypeController::class, 'destroy']);
});

// Sales routes
Route::prefix('sales')->group(function () {
    Route::get('/', [SaleController::class, 'index']);
    Route::get('/statistics', [SaleController::class, 'statistics']);
    Route::get('/monthly-statistics', [SaleController::class, 'monthlyStatistics']);
    Route::get('/product/{productId}', [SaleController::class, 'getByProduct']);
});

// Restocks routes
Route::prefix('restocks')->group(function () {
    Route::get('/', [RestockController::class, 'index']);
    Route::get('/statistics', [RestockController::class, 'statistics']);
    Route::get('/monthly-statistics', [RestockController::class, 'monthlyStatistics']);
    Route::get('/product/{productId}', [RestockController::class, 'getByProduct']);
});

// Monthly summary routes (auto-calculated in/out transactions)
Route::prefix('monthly-summary')->group(function () {
    Route::get('/', [InventoryReportController::class, 'getMonthlySummaries']);
    Route::get('/check-auto-calculate', [InventoryReportController::class, 'checkAutoCalculate']);
    Route::post('/calculate', [InventoryReportController::class, 'calculateMonthlySummary']);
    Route::get('/{year}/{month}', [InventoryReportController::class, 'getMonthlySummary']);
});

// Inventory report routes (detailed summary reports)
Route::prefix('inventory-report')->group(function () {
    Route::get('/generate', [InventoryReportController::class, 'generate']);
    Route::get('/available-months', [InventoryReportController::class, 'availableMonths']);
});
