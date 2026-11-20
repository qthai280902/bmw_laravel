<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->string('customer_order_code', 40)->nullable()->unique()->after('id');
            $table->string('daily_sequence_code', 24)->nullable()->unique()->after('customer_order_code');
            $table->foreignId('assigned_to')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->index(['assigned_to', 'status'], 'appointments_assigned_status_index');
        });

        Schema::table('accessory_orders', function (Blueprint $table): void {
            $table->string('customer_order_code', 40)->nullable()->unique()->after('id');
            $table->string('daily_sequence_code', 24)->nullable()->unique()->after('customer_order_code');
            $table->foreignId('assigned_to')->nullable()->after('product_id')->constrained('users')->nullOnDelete();
            $table->index(['assigned_to', 'status'], 'accessory_orders_assigned_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex('appointments_assigned_status_index');
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropUnique('appointments_daily_sequence_code_unique');
            $table->dropUnique('appointments_customer_order_code_unique');
            $table->dropColumn(['customer_order_code', 'daily_sequence_code']);
        });

        Schema::table('accessory_orders', function (Blueprint $table): void {
            $table->dropIndex('accessory_orders_assigned_status_index');
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropUnique('accessory_orders_daily_sequence_code_unique');
            $table->dropUnique('accessory_orders_customer_order_code_unique');
            $table->dropColumn(['customer_order_code', 'daily_sequence_code']);
        });
    }
};
