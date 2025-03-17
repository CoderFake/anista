<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\PaymentController;

Route::group(['prefix' => 'payment'], function () {
    Route::get('process/{orderId}/{method}', [PaymentController::class, 'processPayment'])->name('payment.process');
    
    Route::group(['prefix' => 'callback'], function () {
        Route::any('vnpay', [PaymentController::class, 'handlePaymentCallback', 'vnpay'])->name('payment.callback.vnpay');
        Route::any('momo', [PaymentController::class, 'handlePaymentCallback', 'momo'])->name('payment.callback.momo');
        Route::any('paypal', [PaymentController::class, 'handlePaymentCallback', 'paypal'])->name('payment.callback.paypal');
        Route::any('zalopay', [PaymentController::class, 'handlePaymentCallback', 'zalopay'])->name('payment.callback.zalopay');
    });
});