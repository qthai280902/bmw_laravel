<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => OrderStatus::PendingPayment,
            'total_deposit_amount' => $this->faker->numberBetween(50000000, 200000000),
            'expires_at' => now()->addHours(24),
            'notes' => $this->faker->sentence(),
        ];
    }
}
