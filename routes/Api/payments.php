<?php

use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/payments/cinetpay')->group(function () {
    Route::post('/notify', [PaymentWebhookController::class, 'cinetpayNotify'])->name('cinetpay.notify');
    Route::get('/success', [PaymentWebhookController::class, 'success'])->name('cinetpay.success');
    Route::get('/failed', [PaymentWebhookController::class, 'failed'])->name('cinetpay.failed');
});
