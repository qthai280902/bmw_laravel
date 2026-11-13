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
        Schema::table('ai_chat_sessions', function (Blueprint $table): void {
            $table->foreignId('context_product_id')
                ->nullable()
                ->after('last_intent')
                ->constrained('products')
                ->nullOnDelete();
            $table->string('context_product_name', 255)
                ->nullable()
                ->after('context_product_id');
            $table->string('context_product_slug', 255)
                ->nullable()
                ->after('context_product_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_chat_sessions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('context_product_id');
            $table->dropColumn(['context_product_name', 'context_product_slug']);
        });
    }
};
