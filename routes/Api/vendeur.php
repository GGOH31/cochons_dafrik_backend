<?php

use App\Http\Controllers\VendeurController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/vendeur')->middleware('auth:sanctum')->group(function () {
    Route::post('/restaurant', [VendeurController::class, 'createShop']);
    Route::put('/profile', [VendeurController::class, 'updateProfile']);
    Route::get('/dashboard', [VendeurController::class, 'getDashboard']);
    Route::get('/profile/personal', [VendeurController::class, 'getPersonalInfo']);
    Route::get('/profile/restaurant', [VendeurController::class, 'getShopInfo']);
    Route::post('/profile/restaurant', [VendeurController::class, 'updateShopInfo']);
    
    // Dish management
    Route::get('/dishes', [VendeurController::class, 'getDishes']);
    Route::post('/dishes', [VendeurController::class, 'createProduct']);
    Route::put('/dishes/{id}', [VendeurController::class, 'updateProduct']);
    Route::delete('/dishes/{id}', [VendeurController::class, 'deleteProduct']);

    // Accompaniment management
    Route::get('/accompaniments', [VendeurController::class, 'getAccompaniments']);
    Route::post('/accompaniments', [VendeurController::class, 'createAccompaniment']);
    Route::put('/accompaniments/{id}', [VendeurController::class, 'updateAccompaniment']);
    Route::delete('/accompaniments/{id}', [VendeurController::class, 'deleteAccompaniment']);

    // Promotion management
    Route::get('/promotions', [VendeurController::class, 'getPromotions']);
    Route::post('/promotions', [VendeurController::class, 'createPromotion']);
    Route::put('/promotions/{id}', [VendeurController::class, 'updatePromotion']);
    Route::delete('/promotions/{id}', [VendeurController::class, 'deletePromotion']);

    // Order management
    Route::get('/orders', [VendeurController::class, 'getMyOrders']);
    Route::get('/orders/{id}', [VendeurController::class, 'getOrderDetails']);
    Route::post('/orders/{id}/accept', [VendeurController::class, 'acceptOrder']);
    Route::post('/orders/{id}/refuse', [VendeurController::class, 'refuseOrder']);
    Route::put('/orders/{id}/status', [VendeurController::class, 'updateOrderStatus']);

    // Wallet & Withdrawals
    Route::get('/wallet', [VendeurController::class, 'getWallet']);
    Route::post('/wallet/withdraw', [VendeurController::class, 'requestWithdrawal']);
});
