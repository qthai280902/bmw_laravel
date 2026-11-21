<?php

namespace Database\Factories;

use App\Models\AccessoryOrder;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccessoryOrder>
 */
class AccessoryOrderFactory extends Factory
{
    protected $model = AccessoryOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory()->state([
                'type' => 'accessory',
                'price' => 3500000,
                'deposit_amount' => 0,
                'stock' => 20,
            ]),
            'assigned_to' => null,
            'customer_name' => fake()->name(),
            'customer_phone' => '09'.fake()->numerify('########'),
            'customer_address' => fake()->address(),
            'customer_email' => fake()->safeEmail(),
            'quantity' => 1,
            'notes' => fake()->optional()->sentence(),
            'status' => AccessoryOrder::STATUS_PENDING,
            'admin_notes' => null,
            'meta_data' => [],
            'confirmed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => AccessoryOrder::STATUS_COMPLETED,
            'confirmed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status' => AccessoryOrder::STATUS_CANCELLED,
        ]);
    }

    public function assignedTo(User|int $user): static
    {
        return $this->state(fn (): array => [
            'assigned_to' => $user instanceof User ? $user->id : $user,
        ]);
    }
}
