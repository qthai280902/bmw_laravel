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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete()->index();
            $table->string('name');
            $table->string('slug')->unique()->index();
            $table->string('type')->index(); // car, motorbike (VehicleType Enum)
            $table->unsignedBigInteger('price')->index();
            $table->unsignedBigInteger('deposit_amount');
            $table->integer('stock')->default(0);
            $table->json('specifications');
            $table->text('description')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
