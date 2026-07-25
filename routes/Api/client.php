<?php

use App\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/client')->middleware('auth:sanctum')->group(function () {
    Route::get('/profile/personal', [ClientController::class, 'getPersonalInfo']);
    Route::get('/addresses', [ClientController::class, 'getAddresses']);
    Route::post('/addresses', [ClientController::class, 'saveAddress']);
    Route::put('/addresses/{id}', [ClientController::class, 'updateAddress']);
    Route::get('/restaurants', [ClientController::class, 'searchRestaurants']);
    Route::get('/payment-methods', [ClientController::class, 'getPaymentMethods']);
    Route::get('/dishes/search', [ClientController::class, 'searchDishes']);
    Route::get('/restaurants/{shopId}/dishes', [ClientController::class, 'getShopDishes']);
    
    // Order routes
    Route::get('/orders', [ClientController::class, 'getMyOrders']);
    Route::get('/orders/{id}', [ClientController::class, 'getOrderDetails']);
    Route::post('/orders', [ClientController::class, 'createOrder']);
    Route::post('/orders/{id}/pay', [ClientController::class, 'payOrder']);
    Route::post('/orders/{id}/confirm', [ClientController::class, 'confirmReception']);
    Route::post('/orders/{id}/review', [ClientController::class, 'submitReview']);
    Route::post('/orders/{id}/reorder', [ClientController::class, 'reorder']);
});
