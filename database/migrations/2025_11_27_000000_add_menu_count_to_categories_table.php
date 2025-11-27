<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->integer('menu_count')->default(0)->after('name');
        });

        // Populate existing counts
        DB::statement('UPDATE categories SET menu_count = (SELECT COUNT(*) FROM menus WHERE menus.category_id = categories.id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('menu_count');
        });
    }
};
