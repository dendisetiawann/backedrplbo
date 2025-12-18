<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Pengguna (users)
        Schema::create('pengguna', function (Blueprint $table) {
            $table->increments('id_pengguna');
            $table->string('nama_pengguna');
            $table->string('username')->unique();
            $table->string('password');
        });

        // 2. Tabel Cache
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        // 3. Tabel Jobs
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        // 4. Tabel Personal Access Tokens
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        // 5. Tabel Kategori
        Schema::create('kategori', function (Blueprint $table) {
            $table->increments('id_kategori');
            $table->string('nama_kategori');
            $table->integer('jumlah_menu')->default(0);
            $table->softDeletes('tanggal_dihapus');
        });

        // 6. Tabel Menu
        Schema::create('menu', function (Blueprint $table) {
            $table->increments('id_menu');
            $table->unsignedInteger('id_kategori');
            $table->string('nama_menu');
            $table->text('deskripsi_menu')->nullable();
            $table->unsignedInteger('harga_menu');
            $table->string('foto_menu')->nullable();
            $table->boolean('status_visibilitas')->default(true);
            $table->softDeletes('tanggal_dihapus');

            $table->foreign('id_kategori')
                ->references('id_kategori')
                ->on('kategori')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        // 7. Tabel Pelanggan
        Schema::create('pelanggan', function (Blueprint $table) {
            $table->increments('id_pelanggan');
            $table->string('nama_pelanggan');
            $table->string('nomor_meja');
            $table->text('catatan_pelanggan')->nullable();
        });

        // 8. Tabel Pesanan (orders)
        Schema::create('pesanan', function (Blueprint $table) {
            $table->increments('id_pesanan');
            $table->string('nomor_pesanan');
            $table->unsignedInteger('id_pelanggan')->nullable();
            $table->unsignedInteger('total_harga');
            $table->enum('status_pesanan', ['baru', 'diproses', 'selesai'])->default('baru');
            $table->timestamp('tanggal_dibuat')->useCurrent();

            $table->foreign('id_pelanggan')
                ->references('id_pelanggan')
                ->on('pelanggan')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        // 9. Tabel Item Pesanan (order_items)
        Schema::create('itempesanan', function (Blueprint $table) {
            $table->increments('id_itempesanan');
            $table->unsignedInteger('id_pesanan');
            $table->unsignedInteger('id_menu');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('harga_itempesanan');
            $table->unsignedInteger('subtotal');
            $table->timestamp('tanggal_dibuat')->useCurrent();

            $table->foreign('id_pesanan')
                ->references('id_pesanan')
                ->on('pesanan')
                ->cascadeOnDelete();

            $table->foreign('id_menu')
                ->references('id_menu')
                ->on('menu')
                ->restrictOnDelete();
        });

        // 10. Tabel Pembayaran (payments)
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->increments('id_pembayaran');
            $table->unsignedInteger('id_pesanan');
            $table->enum('metode_pembayaran', ['cash', 'qris'])->default('cash');
            $table->enum('status_pembayaran', ['belum_bayar', 'pending', 'dibayar', 'gagal'])->default('belum_bayar');
            $table->unsignedInteger('jumlah_pembayaran');
            $table->string('token_pembayaran')->nullable();
            $table->string('id_transaksi_qris')->nullable()->unique();
            $table->timestamp('waktu_dibayar')->nullable();

            $table->foreign('id_pesanan')
                ->references('id_pesanan')
                ->on('pesanan')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
        Schema::dropIfExists('itempesanan');
        Schema::dropIfExists('pesanan');
        Schema::dropIfExists('pelanggan');
        Schema::dropIfExists('menu');
        Schema::dropIfExists('kategori');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('pengguna');
    }
};
