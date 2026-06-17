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
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('ai_visitor_id', 80)->nullable()->after('showroom')->index();
        });

        Schema::table('accessory_orders', function (Blueprint $table) {
            $table->string('ai_visitor_id', 80)->nullable()->after('customer_email')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('ai_visitor_id');
        });

        Schema::table('accessory_orders', function (Blueprint $table) {
            $table->dropColumn('ai_visitor_id');
        });
    }
};
