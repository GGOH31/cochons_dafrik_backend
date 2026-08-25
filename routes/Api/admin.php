<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Restaurants
    Route::get('/restaurants', [AdminController::class, 'getRestaurants']);
    Route::get('/restaurants/{id}', [AdminController::class, 'getRestaurant']);
    Route::post('/restaurants/{id}/validate', [AdminController::class, 'validateShop']);
    Route::put('/restaurants/{shopId}/commission', [AdminController::class, 'updateShopCommission']);

    // Users (clients & vendeurs)
    Route::get('/users', [AdminController::class, 'getUsers']);
    Route::get('/users/{id}', [AdminController::class, 'getUser']);
    Route::put('/users/{id}/status', [AdminController::class, 'updateUserStatus']);

    // Orders supervision
    Route::get('/orders', [AdminController::class, 'getOrders']);
    Route::get('/orders/{id}', [AdminController::class, 'getOrder']);

    // Withdrawals
    Route::get('/withdrawals', [AdminController::class, 'getWithdrawals']);
    Route::post('/withdrawals/{id}/process', [AdminController::class, 'processWithdrawal']);

    // Platform settings
    Route::get('/settings', [AdminController::class, 'getPlatformSettings']);
    Route::put('/settings/{key}', [AdminController::class, 'updatePlatformSetting']);

    // Stats & Escrow supervision
    Route::get('/stats', [AdminController::class, 'getDashboardStats']);
    Route::get('/escrows', [AdminController::class, 'getEscrows']);
    Route::get('/reports/restaurants/{restaurantId}', [AdminController::class, 'getRestaurantReport']);

    // Disputes
    Route::get('/disputes', [AdminController::class, 'getDisputes']);
    Route::post('/disputes/{id}/resolve', [AdminController::class, 'resolveDispute']);
});
