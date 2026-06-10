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
}
