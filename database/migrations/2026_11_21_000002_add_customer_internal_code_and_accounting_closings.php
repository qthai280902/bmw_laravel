<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'is_super_admin')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('is_super_admin')->default(false)->after('role');
            });

            DB::table('users')
                ->whereRaw('LOWER(email) = ?', ['thai@bmw.com'])
                ->update(['is_super_admin' => true]);
        }

        if (Schema::hasTable('appointments') && ! Schema::hasColumn('appointments', 'customer_internal_code')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->string('customer_internal_code', 32)->nullable()->after('daily_sequence_code');
                $table->index('customer_internal_code', 'appointments_customer_internal_code_idx');
            });
        }

        if (Schema::hasTable('accessory_orders') && ! Schema::hasColumn('accessory_orders', 'customer_internal_code')) {
            Schema::table('accessory_orders', function (Blueprint $table): void {
                $table->string('customer_internal_code', 32)->nullable()->after('daily_sequence_code');
                $table->index('customer_internal_code', 'accessory_orders_customer_internal_code_idx');
            });
        }

        if (! Schema::hasTable('accounting_daily_closings')) {
            Schema::create('accounting_daily_closings', function (Blueprint $table): void {
                $table->id();
                $table->date('closing_date')->unique();
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('closed_at')->nullable();
                $table->unsignedInteger('appointments_count')->default(0);
                $table->unsignedInteger('services_count')->default(0);
                $table->unsignedInteger('orders_count')->default(0);
                $table->unsignedBigInteger('completed_revenue')->default(0);
                $table->unsignedBigInteger('committed_value')->default(0);
                $table->unsignedBigInteger('cancelled_value')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_daily_closings');

        if (Schema::hasTable('accessory_orders') && Schema::hasColumn('accessory_orders', 'customer_internal_code')) {
            Schema::table('accessory_orders', function (Blueprint $table): void {
                $table->dropIndex('accessory_orders_customer_internal_code_idx');
                $table->dropColumn('customer_internal_code');
            });
        }

        if (Schema::hasTable('appointments') && Schema::hasColumn('appointments', 'customer_internal_code')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->dropIndex('appointments_customer_internal_code_idx');
                $table->dropColumn('customer_internal_code');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_super_admin')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('is_super_admin');
            });
        }
    }
};
