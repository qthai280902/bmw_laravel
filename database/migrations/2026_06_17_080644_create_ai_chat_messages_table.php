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
        Schema::create('ai_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_chat_session_id')->constrained()->cascadeOnDelete();
            $table->string('role', 25)->index();
            $table->text('content');
            $table->string('content_preview', 300)->nullable();
            $table->text('page_url')->nullable();
            $table->string('page_path', 500)->nullable();
            $table->text('referrer')->nullable();
            $table->string('provider', 80)->nullable();
            $table->string('model', 120)->nullable();
            $table->string('response_reason', 80)->nullable()->index();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->timestamps();

            $table->index(['ai_chat_session_id', 'created_at'], 'ai_chat_messages_session_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chat_messages');
    }
};
