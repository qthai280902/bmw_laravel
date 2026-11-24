<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_email_logs')) {
            return;
        }

        Schema::create('customer_email_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('customer_phone', 40)->nullable()->index();
            $table->string('customer_email', 190)->index();
            $table->string('customer_name', 150)->nullable();
            $table->string('mail_type', 48)->index();
            $table->string('template_code', 100)->nullable();
            $table->string('subject', 255);
            $table->text('body_excerpt')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->index();
            $table->json('meta_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_email_logs');
    }
};
