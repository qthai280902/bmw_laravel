<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('accessory_orders', 'meta_data')) {
            Schema::table('accessory_orders', function (Blueprint $table): void {
                $table->json('meta_data')->nullable()->after('admin_notes');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('accessory_orders', 'meta_data')) {
            Schema::table('accessory_orders', function (Blueprint $table): void {
                $table->dropColumn('meta_data');
            });
        }
    }
};
