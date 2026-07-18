<?php

use App\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/client')->middleware('auth:sanctum')->group(function () {
    Route::get('/profile/personal', [ClientController::class, 'getPersonalInfo']);
    Route::get('/addresses', [ClientController::class, 'getAddresses']);
    Route::post('/addresses', [ClientController::class, 'saveAddress']);
    Route::put('/addresses/{id}', [ClientController::class, 'updateAddress']);
    Route::get('/shops', [ClientController::class, 'searchShops']);
    Route::get('/products/search', [ClientController::class, 'searchProducts']);
    Route::get('/shops/{shopId}/products', [ClientController::class, 'getShopProducts']);
    
    // Order routes
    Route::get('/orders', [ClientController::class, 'getMyOrders']);
    Route::post('/orders', [ClientController::class, 'createOrder']);
    Route::post('/orders/{id}/pay', [ClientController::class, 'payOrder']);
    Route::post('/orders/{id}/confirm', [ClientController::class, 'confirmReception']);
    Route::post('/orders/{id}/review', [ClientController::class, 'submitReview']);
    Route::post('/orders/{id}/reorder', [ClientController::class, 'reorder']);
});
