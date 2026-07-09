<?php

use App\Http\Controllers\VendeurController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/vendeur')->middleware('auth:sanctum')->group(function () {
    Route::post('/shop', [VendeurController::class, 'createShop']);
    Route::put('/profile', [VendeurController::class, 'updateProfile']);
    
    // Category management
    Route::post('/categories', [VendeurController::class, 'createCategory']);
    Route::put('/categories/{id}', [VendeurController::class, 'updateCategory']);
    Route::delete('/categories/{id}', [VendeurController::class, 'deleteCategory']);

    // Product management
    Route::post('/products', [VendeurController::class, 'createProduct']);
    Route::put('/products/{id}', [VendeurController::class, 'updateProduct']);
    Route::delete('/products/{id}', [VendeurController::class, 'deleteProduct']);

    // Order management
    Route::get('/orders', [VendeurController::class, 'getMyOrders']);
    Route::post('/orders/{id}/accept', [VendeurController::class, 'acceptOrder']);
    Route::post('/orders/{id}/refuse', [VendeurController::class, 'refuseOrder']);
    Route::put('/orders/{id}/status', [VendeurController::class, 'updateOrderStatus']);

    // Wallet & Withdrawals
    Route::get('/wallet', [VendeurController::class, 'getWallet']);
    Route::post('/wallet/withdraw', [VendeurController::class, 'requestWithdrawal']);
});
