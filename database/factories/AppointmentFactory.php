<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Models\Appointment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'guest_name' => null,
            'guest_email' => null,
            'guest_phone' => null,
            'product_id' => Product::factory(),
            'type' => $this->faker->randomElement(AppointmentType::cases()),
            'appointment_date' => $this->faker->dateTimeBetween('-7 days', '+14 days'),
            'status' => $this->faker->randomElement(AppointmentStatus::cases()),
            'notes' => $this->faker->optional()->sentence(),
            'meta_data' => null,
            'showroom' => $this->faker->optional()->city(),
        ];
    }

    public function guest(): static
    {
        return $this->state(fn (): array => [
            'user_id' => null,
            'guest_name' => fake()->name(),
            'guest_email' => fake()->safeEmail(),
            'guest_phone' => fake()->phoneNumber(),
        ]);
    }

    public function hotLead(): static
    {
        return $this->state(fn (): array => [
            'lead_priority' => Appointment::LEAD_PRIORITY_HOT,
            'lead_intent' => 'ready_to_buy',
            'ai_lead_note' => 'Khách hàng có nhu cầu chốt cọc trong tuần này.',
        ]);
    }

    public function assignedTo(User|int $user): static
    {
        return $this->state(fn (): array => [
            'assigned_to' => $user instanceof User ? $user->id : $user,
        ]);
    }

    public function rescheduledByGuest(): static
    {
        return $this->state(fn (): array => [
            'status' => AppointmentStatus::Pending,
            'meta_data' => [
                'reschedule_pending_review' => true,
                'reschedule_reason' => 'Khách hàng yêu cầu dời lịch sang khung giờ mới.',
                'reschedule_requested_at' => now()->toIso8601String(),
            ],
        ]);
    }
}
