<?php

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Http\Request;

// روت‌های عمومی (بدون احراز هویت)
Route::post('auth/send-code', [AuthController::class, 'sendVerificationCode']); // حذف ->name("login")
Route::post('auth/verify-code', [AuthController::class, 'verifyCode']);
Route::post('auth/resend-code', [AuthController::class, 'resendCode']);

// Route::apiResource('/chats', ChatController::class);
// روت‌های محافظت‌شده
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('/chats', ChatController::class);

    Route::get('/plans', [SubscriptionController::class, 'plans']);
    Route::get('/my-subscription', [SubscriptionController::class, 'mySubscription']);
    Route::post('/calculate-upgrade', [SubscriptionController::class, 'calculateUpgrade']);
    Route::post('/initiate-payment', [SubscriptionController::class, 'initiatePayment']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('/user/status', [AuthController::class, 'status']);

});

// Callback درگاه پرداخت (بدون middleware auth)
Route::get('/payment/callback', [SubscriptionController::class, 'paymentCallback'])->name('payment.callback');
