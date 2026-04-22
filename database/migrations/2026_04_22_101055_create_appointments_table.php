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
            $header->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $header->string('guest_name')->nullable();
            $header->string('guest_email')->nullable();
            $header->string('guest_phone')->nullable();
            $header->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $header->string('type')->default(AppointmentType::TestDrive->value);
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
