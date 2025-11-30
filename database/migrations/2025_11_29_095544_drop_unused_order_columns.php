<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'note')) {
                $table->dropColumn('note');
            }
        });

        if (Schema::hasColumn('orders', 'pelanggan_id')) {
            DB::table('orders')
                ->whereNull('pelanggan_id')
                ->orderBy('id')
                ->chunkById(100, function ($orders) {
                    foreach ($orders as $order) {
                        $name = $order->customer_name ?: 'Pelanggan Tanpa Nama';
                        $tableNumber = $order->table_number ?: '-';

                        $pelangganId = DB::table('pelanggan')
                            ->where('name', $name)
                            ->where('table_number', $tableNumber)
                            ->value('id');

                        if (! $pelangganId) {
                            $pelangganId = DB::table('pelanggan')->insertGetId([
                                'name' => $name,
                                'table_number' => $tableNumber,
                                'customer_note' => $order->customer_note,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }

                        DB::table('orders')
                            ->where('id', $order->id)
                            ->update(['pelanggan_id' => $pelangganId]);
                    }
                });

            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['pelanggan_id']);
            });

            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('pelanggan_id')->nullable(false)->change();
            });

            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('pelanggan_id')
                    ->references('id')
                    ->on('pelanggan')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'customer_name')) {
                $table->dropColumn('customer_name');
            }
            if (Schema::hasColumn('orders', 'table_number')) {
                $table->dropColumn('table_number');
            }
            if (Schema::hasColumn('orders', 'customer_note')) {
                $table->dropColumn('customer_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'note')) {
                $table->text('note')->nullable()->after('subtotal');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['pelanggan_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'customer_name')) {
                $table->string('customer_name')->after('pelanggan_id');
            }
            if (! Schema::hasColumn('orders', 'table_number')) {
                $table->string('table_number')->after('customer_name');
            }
            if (! Schema::hasColumn('orders', 'customer_note')) {
                $table->text('customer_note')->nullable()->after('table_number');
            }

            if (Schema::hasColumn('orders', 'pelanggan_id')) {
                $table->foreignId('pelanggan_id')->nullable()->change();
            }
        });

        DB::table('orders')
            ->whereNull('customer_name')
            ->orWhereNull('table_number')
            ->chunkById(100, function ($orders) {
                foreach ($orders as $order) {
                    $pelanggan = DB::table('pelanggan')->find($order->pelanggan_id);
                    if (! $pelanggan) {
                        continue;
                    }

                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update([
                            'customer_name' => $pelanggan->name,
                            'table_number' => $pelanggan->table_number,
                            'customer_note' => $pelanggan->customer_note,
                        ]);
                }
            });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('pelanggan_id')
                ->references('id')
                ->on('pelanggan')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }
};
