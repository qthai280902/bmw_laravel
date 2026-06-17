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
        Schema::create('ai_chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_id', 80)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->string('user_agent_hash', 64)->nullable();
            $table->string('display_name')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 30)->nullable();
            $table->string('linked_source_type', 40)->nullable();
            $table->unsignedBigInteger('linked_source_id')->nullable();
            $table->string('link_confidence', 40)->nullable();
            $table->timestamp('first_seen_at')->nullable()->index();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->unsignedInteger('message_count')->default(0);
            $table->string('last_message_preview', 300)->nullable();
            $table->string('last_intent', 80)->nullable()->index();
            $table->string('status', 40)->default('new')->index();
            $table->timestamps();

            $table->index(['visitor_id', 'last_seen_at']);
            $table->index(['ip_hash', 'last_seen_at']);
            $table->index(['linked_source_type', 'linked_source_id'], 'ai_chat_sessions_linked_source_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chat_sessions');
    }
};
