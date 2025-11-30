<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'midtrans_order_id')) {
                $table->dropUnique('payments_midtrans_order_id_unique');

                if (Schema::hasColumn('payments', 'qris_order_id')) {
                    $table->dropColumn('qris_order_id');
                }

                $table->renameColumn('midtrans_order_id', 'qris_order_id');
                $table->unique('qris_order_id', 'payments_qris_order_id_unique');
            } elseif (! Schema::hasColumn('payments', 'qris_order_id')) {
                $table->string('qris_order_id')->nullable()->after('snap_token');
                $table->unique('qris_order_id', 'payments_qris_order_id_unique');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'qris_order_id')) {
                $table->dropUnique('payments_qris_order_id_unique');

                if (Schema::hasColumn('payments', 'midtrans_order_id')) {
                    $table->dropColumn('midtrans_order_id');
                }

                $table->renameColumn('qris_order_id', 'midtrans_order_id');
                $table->unique('midtrans_order_id', 'payments_midtrans_order_id_unique');
            } elseif (! Schema::hasColumn('payments', 'midtrans_order_id')) {
                $table->string('midtrans_order_id')->nullable()->after('snap_token');
                $table->unique('midtrans_order_id', 'payments_midtrans_order_id_unique');
            }
        });
    }
};
