<?php

namespace App\Providers;

use App\Models\Kategori;
use App\Models\Menu;
use App\Models\Pesanan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Custom route model bindings
        Route::bind('pesanan', function ($value) {
            return Pesanan::where('id_pesanan', $value)->firstOrFail();
        });

        Route::bind('kategori', function ($value) {
            return Kategori::where('id_kategori', $value)->firstOrFail();
        });

        Route::bind('menu', function ($value) {
            return Menu::where('id_menu', $value)->firstOrFail();
        });
    }
}
