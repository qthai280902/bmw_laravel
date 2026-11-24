<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_installment_plans', function (Blueprint $table): void {
            if (! Schema::hasColumn('customer_installment_plans', 'vehicle_price')) {
                $table->unsignedBigInteger('vehicle_price')->nullable()->after('bank_name');
            }

            if (! Schema::hasColumn('customer_installment_plans', 'down_payment')) {
                $table->unsignedBigInteger('down_payment')->nullable()->after('vehicle_price');
            }

            if (! Schema::hasColumn('customer_installment_plans', 'interest_method')) {
                $table->string('interest_method', 32)->default('reducing_balance')->after('interest_rate');
            }

            if (! Schema::hasColumn('customer_installment_plans', 'start_date')) {
                $table->date('start_date')->nullable()->after('paid_installments');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_installment_plans', function (Blueprint $table): void {
            $dropColumns = array_values(array_filter(
                ['vehicle_price', 'down_payment', 'interest_method', 'start_date'],
                static fn (string $column): bool => Schema::hasColumn('customer_installment_plans', $column)
            ));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
