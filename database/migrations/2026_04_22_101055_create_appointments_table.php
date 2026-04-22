<?php

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
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
        Schema::create('appointments', function (Blueprint $header) {
            $header->id();
            $header->foreignId('user_id')->constrained()->cascadeOnDelete();
            $header->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $header->string('type')->default(AppointmentType::Viewing->value);
            $header->dateTime('appointment_date');
            $header->string('status')->default(AppointmentStatus::Pending->value);
            $header->text('notes')->nullable();
            $header->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
