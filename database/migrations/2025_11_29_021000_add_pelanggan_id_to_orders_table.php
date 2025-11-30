<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'pelanggan_id')) {
                $table->foreignId('pelanggan_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('pelanggan')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }
        });

        $customers = DB::table('orders')
            ->select('customer_name', 'table_number', DB::raw('MIN(customer_note) as customer_note'), DB::raw('MIN(created_at) as created_at'), DB::raw('MAX(updated_at) as updated_at'))
            ->groupBy('customer_name', 'table_number')
            ->get();

        foreach ($customers as $customer) {
            $existing = DB::table('pelanggan')
                ->where('name', $customer->customer_name)
                ->where('table_number', $customer->table_number)
                ->first();

            if ($existing) {
                $pelangganId = $existing->id;
                if (! empty($customer->customer_note) && $existing->customer_note !== $customer->customer_note) {
                    DB::table('pelanggan')
                        ->where('id', $existing->id)
                        ->update([
                            'customer_note' => $customer->customer_note,
                            'updated_at' => now(),
                        ]);
                }
            } else {
                $pelangganId = DB::table('pelanggan')->insertGetId([
                    'name' => $customer->customer_name,
                    'table_number' => $customer->table_number,
                    'customer_note' => $customer->customer_note,
                    'created_at' => $customer->created_at,
                    'updated_at' => $customer->updated_at,
                ]);
            }

            DB::table('orders')
                ->where('customer_name', $customer->customer_name)
                ->where('table_number', $customer->table_number)
                ->update(['pelanggan_id' => $pelangganId]);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'pelanggan_id')) {
                $table->dropForeign(['pelanggan_id']);
                $table->dropColumn('pelanggan_id');
            }
        });
    }
};
