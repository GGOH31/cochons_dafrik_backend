<?php

use App\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Route;

// Public catalog browsing — no authentication required, so guests can look around
// before creating an account. Ordering/checkout still require auth (see below).
Route::prefix('v1/client')->group(function () {
    Route::get('/restaurants', [ClientController::class, 'searchRestaurants']);
    Route::get('/payment-methods', [ClientController::class, 'getPaymentMethods']);
    Route::get('/dishes/search', [ClientController::class, 'searchDishes']);
    Route::get('/restaurants/{shopId}/dishes', [ClientController::class, 'getShopDishes']);
});

Route::prefix('v1/client')->middleware('auth:sanctum')->group(function () {
    Route::get('/profile/personal', [ClientController::class, 'getPersonalInfo']);
    Route::get('/addresses', [ClientController::class, 'getAddresses']);
    Route::post('/addresses', [ClientController::class, 'saveAddress']);
    Route::put('/addresses/{id}', [ClientController::class, 'updateAddress']);

    // Order routes
    Route::get('/orders', [ClientController::class, 'getMyOrders']);
    Route::get('/orders/{id}', [ClientController::class, 'getOrderDetails']);
    Route::post('/orders', [ClientController::class, 'createOrder']);
    Route::post('/orders/{id}/pay', [ClientController::class, 'payOrder']);
    Route::post('/orders/{id}/pay/cinetpay', [ClientController::class, 'payOrderCinetPay']);
    Route::post('/orders/{id}/pay/cinetpay/verify', [ClientController::class, 'verifyCinetPayPayment']);
    Route::post('/orders/{id}/confirm', [ClientController::class, 'confirmReception']);
    Route::post('/orders/{id}/review', [ClientController::class, 'submitReview']);
    Route::post('/orders/{id}/reorder', [ClientController::class, 'reorder']);
});
