<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 24)->nullable()->after('email');
            }

            if (! Schema::hasColumn('users', 'job_title')) {
                $table->string('job_title', 100)->nullable()->after('role');
            }

            if (! Schema::hasColumn('users', 'department')) {
                $table->string('department', 100)->nullable()->after('job_title');
            }

            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_super_admin')->index();
            }

            if (! Schema::hasColumn('users', 'notes')) {
                $table->text('notes')->nullable()->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropIndex(['is_active']);
            }

            $dropColumns = array_values(array_filter(
                ['phone', 'job_title', 'department', 'is_active', 'notes'],
                static fn (string $column): bool => Schema::hasColumn('users', $column)
            ));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
