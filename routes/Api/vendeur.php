<?php

use App\Http\Controllers\VendeurController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/vendeur')->middleware('auth:sanctum')->group(function () {
    Route::post('/shop', [VendeurController::class, 'createShop']);
    Route::put('/profile', [VendeurController::class, 'updateProfile']);
    Route::get('/dashboard', [VendeurController::class, 'getDashboard']);
    Route::get('/profile/personal', [VendeurController::class, 'getPersonalInfo']);
    Route::get('/profile/shop', [VendeurController::class, 'getShopInfo']);
    Route::post('/profile/shop', [VendeurController::class, 'updateShopInfo']);
    
    // Category management
    Route::get('/categories', [VendeurController::class, 'getCategories']);
    Route::post('/categories', [VendeurController::class, 'createCategory']);
    Route::put('/categories/{id}', [VendeurController::class, 'updateCategory']);
    Route::delete('/categories/{id}', [VendeurController::class, 'deleteCategory']);

    // Product management
    Route::get('/products', [VendeurController::class, 'getProducts']);
    Route::post('/products', [VendeurController::class, 'createProduct']);
    Route::put('/products/{id}', [VendeurController::class, 'updateProduct']);
    Route::delete('/products/{id}', [VendeurController::class, 'deleteProduct']);

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
