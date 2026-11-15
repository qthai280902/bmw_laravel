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
        Schema::table('products', function (Blueprint $table): void {
            $table->json('embedding')->nullable()->after('description');
            $table->string('embedding_hash', 64)->nullable()->after('embedding');
        });

        Schema::table('articles', function (Blueprint $table): void {
            $table->json('embedding')->nullable()->after('seo_description');
            $table->string('embedding_hash', 64)->nullable()->after('embedding');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['embedding', 'embedding_hash']);
        });

        Schema::table('articles', function (Blueprint $table): void {
            $table->dropColumn(['embedding', 'embedding_hash']);
        });
    }
};
