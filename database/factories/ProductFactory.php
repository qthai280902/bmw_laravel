<?php

namespace Database\Factories;

use App\Enums\VehicleType;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);
        $price = $this->faker->numberBetween(1000000000, 5000000000); // 1B - 5B VNĐ

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => $this->faker->randomElement(VehicleType::cases()),
            'price' => $price,
            'deposit_amount' => (int) ($price * 0.05), // 5% deposit
            'stock' => $this->faker->numberBetween(0, 5),
            'specifications' => [
                'Engine' => 'V8 Twin-Power Turbo',
                'Power' => '600 HP',
                'Acceleration' => '3.4s (0-100 km/h)',
            ],
            'description' => $this->faker->paragraph(),
            'is_featured' => $this->faker->boolean(20),
            'is_active' => true,
        ];
    }
}
