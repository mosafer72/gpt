<?php

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MessageExportController;
use App\Http\Controllers\Api\ModelController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\UsageController;
use App\Http\Controllers\Api\TokenWalletController;
use Illuminate\Http\Request;

// روت‌های عمومی (بدون احراز هویت)
Route::post('/auth/send-login-code', [AuthController::class, 'sendLoginCode']);
Route::post('/auth/send-register-code', [AuthController::class, 'sendRegisterCode']);

Route::post('auth/verify-code', [AuthController::class, 'verifyCode']);
Route::post('auth/resend-code', [AuthController::class, 'resendCode']);

// Route::apiResource('/chats', ChatController::class);
// روت‌های محافظت‌شده
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('/chats', ChatController::class);
    Route::get('/chats/{chat}/messages', [ChatController::class, 'messages']);


    Route::post('/chats/stream', [ChatController::class, 'store'])

        ->middleware('auth:sanctum');
    Route::get('/plans', [SubscriptionController::class, 'plans']);
    Route::get('/my-subscription', [SubscriptionController::class, 'mySubscription']);
    Route::post('/calculate-upgrade', [SubscriptionController::class, 'calculateUpgrade']);
    Route::post('/initiate-payment', [SubscriptionController::class, 'initiatePayment']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('/user/status', [AuthController::class, 'status']);


    Route::get('/usage', [UsageController::class, 'index']);
    Route::get('/usage/today', [UsageController::class, 'today']);
    Route::get('/usage/summary', [UsageController::class, 'summary']);
    Route::get('/usage/{id}', [UsageController::class, 'show']);
    Route::get('/messages/{message}/export', [MessageExportController::class, 'export']);
    Route::get(
        '/models',
        [ModelController::class, 'index']
    );
    Route::get(
        '/token-wallet',
        [TokenWalletController::class, 'show']
    );

});

// Callback درگاه پرداخت (بدون middleware auth)
Route::get('/payment/callback', [SubscriptionController::class, 'paymentCallback'])->name('payment.callback');


