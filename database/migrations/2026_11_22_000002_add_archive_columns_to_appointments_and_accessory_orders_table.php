<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable()->index()->after('followup_email_sent_at');
            $table->foreignId('archived_by')
                ->nullable()
                ->after('archived_at')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('accessory_orders', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable()->index()->after('confirmed_at');
            $table->foreignId('archived_by')
                ->nullable()
                ->after('archived_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('archived_by');
            $table->dropIndex(['archived_at']);
            $table->dropColumn('archived_at');
        });

        Schema::table('accessory_orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('archived_by');
            $table->dropIndex(['archived_at']);
            $table->dropColumn('archived_at');
        });
    }
};
