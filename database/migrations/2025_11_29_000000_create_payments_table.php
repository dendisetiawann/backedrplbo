<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('method', ['cash', 'qris']);
            $table->enum('status', ['belum_bayar', 'pending', 'dibayar', 'gagal'])->default('belum_bayar');
            $table->unsignedBigInteger('amount');
            $table->string('snap_token')->nullable();
            $table->string('qris_order_id')->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        if (Schema::hasColumn('orders', 'payment_method')) {
            $orders = DB::table('orders')->select(
                'id',
                'total_amount',
                'payment_method',
                'payment_status',
                'snap_token',
                'qris_order_id',
                'created_at',
                'updated_at'
            )->get();

            foreach ($orders as $order) {
                DB::table('payments')->insert([
                    'order_id' => $order->id,
                    'method' => $order->payment_method ?? 'cash',
                    'status' => $order->payment_status ?? 'belum_bayar',
                    'amount' => $order->total_amount ?? 0,
                    'snap_token' => $order->snap_token,
                    'qris_order_id' => $order->qris_order_id ?? $order->midtrans_order_id,
                    'paid_at' => ($order->payment_status ?? null) === 'dibayar'
                        ? ($order->updated_at ?? now())
                        : null,
                    'created_at' => $order->created_at ?? now(),
                    'updated_at' => $order->updated_at ?? now(),
                ]);
            }

            $columnsToDrop = array_values(array_filter([
                Schema::hasColumn('orders', 'payment_method') ? 'payment_method' : null,
                Schema::hasColumn('orders', 'payment_status') ? 'payment_status' : null,
                Schema::hasColumn('orders', 'snap_token') ? 'snap_token' : null,
                Schema::hasColumn('orders', 'qris_order_id') ? 'qris_order_id' : (Schema::hasColumn('orders', 'midtrans_order_id') ? 'midtrans_order_id' : null),
            ]));

            if (! empty($columnsToDrop)) {
                Schema::table('orders', function (Blueprint $table) use ($columnsToDrop) {
                    $table->dropColumn($columnsToDrop);
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'payment_method')) {
                $table->enum('payment_method', ['cash', 'qris'])->default('cash')->after('total_amount');
            }

            if (! Schema::hasColumn('orders', 'payment_status')) {
                $table->enum('payment_status', ['belum_bayar', 'pending', 'dibayar', 'gagal'])->default('belum_bayar')->after('payment_method');
            }

            if (! Schema::hasColumn('orders', 'snap_token')) {
                $table->string('snap_token')->nullable()->after('order_status');
            }

            if (! Schema::hasColumn('orders', 'qris_order_id')) {
                $table->string('qris_order_id')->nullable()->unique()->after('snap_token');
            }
        });

        Schema::dropIfExists('payments');
    }
};
