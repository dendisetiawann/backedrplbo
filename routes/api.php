<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\MidtransController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\XenditController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->group(function () {
    Route::get('/menus', [OrderController::class, 'publicMenus']);
    Route::post('/orders', [OrderController::class, 'publicStore']);
    Route::get('/orders/{order}', [OrderController::class, 'publicShow']);
    Route::post('/orders/{order}/mark-paid', [OrderController::class, 'publicMarkPaid']);
    Route::post('/payment/notify', [MidtransController::class, 'handleNotification']);
    Route::post('/payment/xendit-notify', [XenditController::class, 'handleCallback']);
});

Route::prefix('admin')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);

        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
        Route::put('/orders/{order}/payment-status', [OrderController::class, 'updatePaymentStatus']);

        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('menus', MenuController::class);
    });
});

