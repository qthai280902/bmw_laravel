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
            if (! Schema::hasColumn('appointments', 'confirmation_email_sent_at')) {
                $table->timestamp('confirmation_email_sent_at')->nullable()->after('ai_lead_note');
            }

            if (! Schema::hasColumn('appointments', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('confirmation_email_sent_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('appointments', 'confirmation_email_sent_at') ? 'confirmation_email_sent_at' : null,
                Schema::hasColumn('appointments', 'reminder_sent_at') ? 'reminder_sent_at' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
