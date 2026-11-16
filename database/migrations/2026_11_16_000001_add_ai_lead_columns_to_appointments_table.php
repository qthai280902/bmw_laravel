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
            $table->string('lead_priority', 20)->nullable()->after('ai_visitor_id')->index();
            $table->string('lead_intent', 50)->nullable()->after('lead_priority');
            $table->string('ai_lead_note', 255)->nullable()->after('lead_intent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex(['lead_priority']);
            $table->dropColumn(['lead_priority', 'lead_intent', 'ai_lead_note']);
        });
    }
};
