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
    Route::get('/orders/{pesanan}', [OrderController::class, 'publicShow']);
    Route::post('/orders/{pesanan}/mark-paid', [OrderController::class, 'publicMarkPaid']);
    Route::post('/payment/notify', [MidtransController::class, 'handleNotification']);
    Route::post('/payment/xendit-notify', [XenditController::class, 'handleCallback']);
});

Route::prefix('admin')->group(function () {
    Route::post('/login', [AuthController::class, 'mengidentifikasiUser']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);

        Route::get('/kelolapesanan', [OrderController::class, 'index']);
        Route::get('/kelolapesanan/{pesanan}', [OrderController::class, 'show']);
        Route::put('/kelolapesanan/{pesanan}/status', [OrderController::class, 'updateStatus']);
        Route::put('/kelolapesanan/{pesanan}/payment-status', [OrderController::class, 'updatePaymentStatus']);

        Route::apiResource('kelolakategori', CategoryController::class)
            ->parameters(['kelolakategori' => 'kategori']);
        Route::apiResource('kelolamenu', MenuController::class)
            ->parameters(['kelolamenu' => 'menu']);
    });
});

