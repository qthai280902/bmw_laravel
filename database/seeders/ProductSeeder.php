<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Product;
use App\Enums\VehicleType;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bmw = Brand::where('slug', 'bmw')->first();

        if (!$bmw) {
            return;
        }

        // 1. BMW M5
        $m5 = Product::updateOrCreate(
            ['slug' => 'bmw-m5-competition'],
            [
                'brand_id' => $bmw->id,
                'name' => 'BMW M5 Competition',
                'type' => VehicleType::CAR,
                'price' => 5990000000,
                'deposit_amount' => 50000000,
                'stock' => 5,
                'specifications' => [
                    'engine' => '4.4L V8 with M TwinPower Turbo',
                    'horsepower' => '625 hp',
                    'torque' => '750 Nm',
                    'acceleration' => '3.3s (0-100 km/h)',
                    'transmission' => '8-speed M Steptronic',
                    'fuel_type' => 'Petrol',
                ],
                'description' => 'Mẫu sedan hiệu năng cao biểu tượng của BMW M Division.',
                'is_featured' => true,
            ]
        );

        $m5->images()->updateOrCreate(
            ['path' => 'products/m5-hero.jpg'],
            ['is_primary' => true, 'sort_order' => 0]
        );

        // 2. BMW S1000RR
        $s1000rr = Product::updateOrCreate(
            ['slug' => 'bmw-s1000rr-2026'],
            [
                'brand_id' => $bmw->id,
                'name' => 'BMW S1000RR 2026',
                'type' => VehicleType::MOTORBIKE,
                'price' => 1090000000,
                'deposit_amount' => 20000000,
                'stock' => 10,
                'specifications' => [
                    'engine' => '999cc, Water/oil-cooled 4-cylinder',
                    'horsepower' => '205 hp at 13,000 rpm',
                    'torque' => '113 Nm at 11,000 rpm',
                    'top_speed' => '> 299 km/h',
                    'weight' => '197 kg (fully fueled)',
                    'seat_height' => '824 mm',
                ],
                'description' => 'Siêu môtô hàng đầu thế giới với công nghệ xe đua.',
                'is_featured' => true,
            ]
        );

        $s1000rr->images()->updateOrCreate(
            ['path' => 'products/s1000rr-hero.jpg'],
            ['is_primary' => true, 'sort_order' => 0]
        );
    }
}
