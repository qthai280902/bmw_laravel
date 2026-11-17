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
        Schema::table('appointments', function (Blueprint $table): void {
            $table->unsignedBigInteger('ai_conversation_id')->nullable()->after('ai_visitor_id')->index();
            $table->text('ai_chat_summary')->nullable()->after('ai_conversation_id');
        });

        Schema::table('ai_chat_sessions', function (Blueprint $table): void {
            $table->unsignedBigInteger('reset_after_message_id')->nullable()->after('last_message_preview');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex(['ai_conversation_id']);
            $table->dropColumn(['ai_conversation_id', 'ai_chat_summary']);
        });

        Schema::table('ai_chat_sessions', function (Blueprint $table): void {
            $table->dropColumn('reset_after_message_id');
        });
    }
};
