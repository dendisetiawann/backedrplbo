<?php

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\KelolaKategoriController;
use App\Http\Controllers\Api\KelolaMenuController;
use App\Http\Controllers\Api\KelolaPesananController;
use App\Http\Controllers\Api\MembuatPesananController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->group(function () {
    Route::get('/menus', [MembuatPesananController::class, 'ambilSemuaMenu']);
    Route::get('/menus/{menu}', [MembuatPesananController::class, 'publicMenuShow']);
    Route::post('/orders', [MembuatPesananController::class, 'membuatPesanan']);
    Route::get('/orders/{pesanan}', [MembuatPesananController::class, 'buatPesanan']);
    Route::post('/orders/{pesanan}/mark-paid', [MembuatPesananController::class, 'verifikasiStatusPembayaran']);
    Route::post('/payment/xendit-notify', [MembuatPesananController::class, 'handleXenditCallback']);
});

Route::prefix('admin')->group(function () {
    Route::post('/login', [LoginController::class, 'kirimDataLogin']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [LoginController::class, 'me']);

        Route::get('/kelolapesanan', [KelolaPesananController::class, 'index']);
        Route::get('/kelolapesanan/{pesanan}', [KelolaPesananController::class, 'show']);
        Route::put('/kelolapesanan/{pesanan}/status', [KelolaPesananController::class, 'updateStatusPesanan']);
        Route::put('/kelolapesanan/{pesanan}/payment-status', [KelolaPesananController::class, 'updatePaymentStatus']);

        Route::get('/kelolakategori', [KelolaKategoriController::class, 'ambilSemuaDataKategori']);
        Route::post('/kelolakategori', [KelolaKategoriController::class, 'store']);
        Route::get('/kelolakategori/{kategori}', [KelolaKategoriController::class, 'cekKategori'])->name('kelolakategori.cekKategori');
        Route::put('/kelolakategori/{kategori}', [KelolaKategoriController::class, 'update']);
        Route::delete('/kelolakategori/{kategori}', [KelolaKategoriController::class, 'destroy']);
        Route::get('/kelolamenu', [KelolaMenuController::class, 'ambilSemuaMenu']);
        Route::post('/kelolamenu', [KelolaMenuController::class, 'store']);
        Route::get('/kelolamenu/{menu}', [KelolaMenuController::class, 'cekMenu'])->name('kelolamenu.cekMenu');
        Route::put('/kelolamenu/{menu}', [KelolaMenuController::class, 'update']);
        Route::delete('/kelolamenu/{menu}', [KelolaMenuController::class, 'destroy']);
    });
});

