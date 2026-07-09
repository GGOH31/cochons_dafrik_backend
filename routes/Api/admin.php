<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/admin')->middleware('auth:sanctum')->group(function () {
    Route::post('/shops/{id}/validate', [AdminController::class, 'validateShop']);
    Route::put('/shops/{shopId}/commission', [AdminController::class, 'updateShopCommission']);
    Route::put('/settings/{key}', [AdminController::class, 'updatePlatformSetting']);
    
    // Stats & Escrow supervision
    Route::get('/stats', [AdminController::class, 'getDashboardStats']);
    Route::get('/escrows', [AdminController::class, 'getEscrows']);

    // Disputes
    Route::post('/disputes/{id}/resolve', [AdminController::class, 'resolveDispute']);
});
