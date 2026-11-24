<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 120)->unique();
            $table->string('name', 180);
            $table->string('category', 50)->default('promotion')->index();
            $table->string('subject', 220);
            $table->string('eyebrow', 120)->nullable();
            $table->string('heading', 220);
            $table->longText('body_content');
            $table->string('highlight_title', 180)->nullable();
            $table->text('highlight_content')->nullable();
            $table->string('cta_label', 120)->nullable();
            $table->string('cta_url', 255)->nullable();
            $table->string('accent_color', 20)->default('#1C69D4');
            $table->string('status', 20)->default('active')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
