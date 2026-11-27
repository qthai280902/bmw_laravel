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
        Schema::table('customer_installment_plans', function (Blueprint $table) {
            $table->unsignedSmallInteger('fixed_rate_months')->default(12)->after('interest_rate');
            $table->unsignedSmallInteger('floating_rate_adjustment_months')->default(3)->after('fixed_rate_months');
            $table->decimal('floating_interest_rate', 5, 2)->nullable()->after('floating_rate_adjustment_months');
            $table->string('early_settlement_fee_note', 255)->nullable()->after('floating_interest_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_installment_plans', function (Blueprint $table) {
            $table->dropColumn([
                'fixed_rate_months',
                'floating_rate_adjustment_months',
                'floating_interest_rate',
                'early_settlement_fee_note',
            ]);
        });
    }
};
