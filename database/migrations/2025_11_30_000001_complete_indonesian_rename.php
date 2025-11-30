<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Complete orders table rename (remaining columns)
        if (Schema::hasColumn('orders', 'order_status')) {
            DB::statement("ALTER TABLE `orders` CHANGE `order_status` `status_pesanan` ENUM('baru','diproses','selesai') NOT NULL DEFAULT 'baru'");
        }
        if (Schema::hasColumn('orders', 'created_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->renameColumn('created_at', 'tanggal_dibuat');
                $table->renameColumn('updated_at', 'tanggal_diubah');
            });
        }

        // 2. Rename order_items columns
        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->renameColumn('id', 'id_itempesanan');
                $table->renameColumn('order_id', 'id_pesanan');
                $table->renameColumn('menu_id', 'id_menu');
                $table->renameColumn('qty', 'quantity');
                $table->renameColumn('price', 'harga_itempesanan');
                $table->renameColumn('created_at', 'tanggal_dibuat');
                $table->renameColumn('updated_at', 'tanggal_diubah');
            });
        }

        // 3. Rename payments columns
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->renameColumn('id', 'id_pembayaran');
                $table->renameColumn('order_id', 'id_pesanan');
                $table->renameColumn('amount', 'jumlah_pembayaran');
                $table->renameColumn('snap_token', 'token_pembayaran');
                $table->renameColumn('qris_order_id', 'id_transaksi_qris');
                $table->renameColumn('paid_at', 'waktu_dibayar');
                $table->renameColumn('created_at', 'tanggal_dibuat');
                $table->renameColumn('updated_at', 'tanggal_diubah');
            });

            // Rename enum columns using raw SQL
            DB::statement("ALTER TABLE `payments` CHANGE `method` `metode_pembayaran` ENUM('cash','qris') NOT NULL DEFAULT 'cash'");
            DB::statement("ALTER TABLE `payments` CHANGE `status` `status_pembayaran` ENUM('belum_bayar','pending','dibayar','gagal') NOT NULL DEFAULT 'pending'");
        }

        // 4. Rename tables (only if old name exists)
        if (Schema::hasTable('users') && !Schema::hasTable('pengguna')) {
            Schema::rename('users', 'pengguna');
        }
        if (Schema::hasTable('categories') && !Schema::hasTable('kategori')) {
            Schema::rename('categories', 'kategori');
        }
        if (Schema::hasTable('menus') && !Schema::hasTable('menu')) {
            Schema::rename('menus', 'menu');
        }
        if (Schema::hasTable('orders') && !Schema::hasTable('pesanan')) {
            Schema::rename('orders', 'pesanan');
        }
        if (Schema::hasTable('order_items') && !Schema::hasTable('itempesanan')) {
            Schema::rename('order_items', 'itempesanan');
        }
        if (Schema::hasTable('payments') && !Schema::hasTable('pembayaran')) {
            Schema::rename('payments', 'pembayaran');
        }

        // 5. Re-add foreign keys with new names
        try {
            Schema::table('menu', function (Blueprint $table) {
                $table->foreign('id_kategori')
                    ->references('id_kategori')
                    ->on('kategori')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        } catch (\Exception $e) {
            // FK might already exist
        }

        try {
            Schema::table('pesanan', function (Blueprint $table) {
                $table->foreign('id_pelanggan')
                    ->references('id_pelanggan')
                    ->on('pelanggan')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            });
        } catch (\Exception $e) {
            // FK might already exist
        }

        try {
            Schema::table('itempesanan', function (Blueprint $table) {
                $table->foreign('id_pesanan')
                    ->references('id_pesanan')
                    ->on('pesanan')
                    ->cascadeOnDelete();

                $table->foreign('id_menu')
                    ->references('id_menu')
                    ->on('menu')
                    ->restrictOnDelete();
            });
        } catch (\Exception $e) {
            // FK might already exist
        }

        try {
            Schema::table('pembayaran', function (Blueprint $table) {
                $table->foreign('id_pesanan')
                    ->references('id_pesanan')
                    ->on('pesanan')
                    ->cascadeOnDelete();
            });
        } catch (\Exception $e) {
            // FK might already exist
        }
    }

    public function down(): void
    {
        // Drop foreign keys
        try {
            Schema::table('pembayaran', function (Blueprint $table) {
                $table->dropForeign(['id_pesanan']);
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('itempesanan', function (Blueprint $table) {
                $table->dropForeign(['id_pesanan']);
                $table->dropForeign(['id_menu']);
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('pesanan', function (Blueprint $table) {
                $table->dropForeign(['id_pelanggan']);
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('menu', function (Blueprint $table) {
                $table->dropForeign(['id_kategori']);
            });
        } catch (\Exception $e) {}

        // Rename tables back
        if (Schema::hasTable('pengguna')) Schema::rename('pengguna', 'users');
        if (Schema::hasTable('kategori')) Schema::rename('kategori', 'categories');
        if (Schema::hasTable('menu')) Schema::rename('menu', 'menus');
        if (Schema::hasTable('pesanan')) Schema::rename('pesanan', 'orders');
        if (Schema::hasTable('itempesanan')) Schema::rename('itempesanan', 'order_items');
        if (Schema::hasTable('pembayaran')) Schema::rename('pembayaran', 'payments');

        // Note: Column renames in down() are complex and might need manual intervention
        // This is a one-way migration primarily
    }
};
