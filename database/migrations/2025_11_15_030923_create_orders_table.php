<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('table_number');
            $table->unsignedBigInteger('total_amount');
            $table->enum('payment_method', ['cash', 'qris']);
            $table->enum('payment_status', ['belum_bayar', 'pending', 'dibayar', 'gagal'])->default('belum_bayar');
            $table->enum('order_status', ['baru', 'diproses', 'selesai'])->default('baru');
            $table->string('snap_token')->nullable();
            $table->string('midtrans_order_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
