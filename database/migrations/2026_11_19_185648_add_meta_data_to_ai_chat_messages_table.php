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
        if (! Schema::hasColumn('ai_chat_messages', 'meta_data')) {
            Schema::table('ai_chat_messages', function (Blueprint $table): void {
                $table->json('meta_data')->nullable()->after('latency_ms');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('ai_chat_messages', 'meta_data')) {
            Schema::table('ai_chat_messages', function (Blueprint $table): void {
                $table->dropColumn('meta_data');
            });
        }
    }
};
