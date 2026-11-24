<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_installment_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('customer_phone', 40)->index();
            $table->string('customer_email', 190)->nullable()->index();
            $table->string('customer_name', 150);
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->string('bank_name', 150);
            $table->unsignedBigInteger('loan_amount');
            $table->unsignedBigInteger('monthly_payment');
            $table->decimal('interest_rate', 5, 2)->default(7.90);
            $table->unsignedSmallInteger('term_months')->default(60);
            $table->unsignedSmallInteger('paid_installments')->default(0);
            $table->date('next_due_date')->nullable()->index();
            $table->string('status', 30)->default('active')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_installment_plans');
    }
};
