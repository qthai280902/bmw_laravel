<?php

namespace Database\Seeders;

use App\Enums\VehicleType;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bmw = Brand::where('slug', 'bmw')->first();

        if (! $bmw) {
            return;
        }

        $vehicles = [
            // CARS
            [
                'slug' => 'bmw-i4-edrive40',
                'name' => 'BMW i4 eDrive40',
                'type' => VehicleType::CAR,
                'price' => 3759000000,
                'deposit_amount' => 50000000,
                'stock' => 3,
                'specifications' => [
                    'engine' => 'Electric motor',
                    'horsepower' => '340 hp',
                    'torque' => '430 Nm',
                    'range' => '590 km (WLTP)',
                    'acceleration' => '5.7s (0-100 km/h)',
                    'battery' => '83.9 kWh',
                ],
                'description' => 'Mẫu Gran Coupe thuần điện đầu tiên của BMW với tinh thần thể thao nguyên bản.',
                'image' => 'products/i4-hero.jpg',
            ],
            [
                'slug' => 'bmw-x5-xdrive40i',
                'name' => 'BMW X5 xDrive40i MSport',
                'type' => VehicleType::CAR,
                'price' => 4169000000,
                'deposit_amount' => 50000000,
                'stock' => 8,
                'specifications' => [
                    'engine' => '3.0L Inline 6-cylinder BMW TwinPower Turbo',
                    'horsepower' => '381 hp',
                    'torque' => '540 Nm',
                    'acceleration' => '5.4s (0-100 km/h)',
                    'transmission' => '8-speed Steptronic Sport',
                    'fuel_type' => 'Petrol',
                ],
                'description' => 'Mẫu SUV sang trọng biểu tượng, dẫn đầu mọi hành trình với sự linh hoạt tối đa.',
                'image' => 'products/x5-hero.jpg',
            ],
            [
                'slug' => 'bmw-7-series-735i',
                'name' => 'BMW 7 Series 735i Pure Excellence',
                'type' => VehicleType::CAR,
                'price' => 4499000000,
                'deposit_amount' => 100000000,
                'stock' => 2,
                'specifications' => [
                    'engine' => '3.0L Inline 6-cylinder BMW TwinPower Turbo',
                    'horsepower' => '286 hp',
                    'torque' => '425 Nm',
                    'acceleration' => '6.7s (0-100 km/h)',
                    'luxury_features' => 'BMW Interaction Bar, Theatre Screen 31.3"',
                ],
                'description' => 'Đỉnh cao của sự sang trọng và công nghệ tương lai.',
                'image' => 'products/735i-hero.jpg',
            ],
            [
                'slug' => 'bmw-m4-competition',
                'name' => 'BMW M4 Competition Coupé',
                'type' => VehicleType::CAR,
                'price' => 5599000000,
                'deposit_amount' => 100000000,
                'stock' => 4,
                'specifications' => [
                    'engine' => '3.0L M TwinPower Turbo inline 6-cylinder',
                    'horsepower' => '510 hp',
                    'torque' => '650 Nm',
                    'acceleration' => '3.9s (0-100 km/h)',
                    'transmission' => '8-speed M Steptronic with Drivelogic',
                ],
                'description' => 'Sức mạnh thuần túy từ đường đua kết hợp phong cách Coupé thời thượng.',
                'image' => 'products/m4-hero.jpg',
            ],
            [
                'slug' => 'bmw-z4-sdrive30i',
                'name' => 'BMW Z4 sDrive30i MSport',
                'type' => VehicleType::CAR,
                'price' => 3509000000,
                'deposit_amount' => 50000000,
                'stock' => 3,
                'specifications' => [
                    'engine' => '2.0L BMW TwinPower Turbo 4-cylinder',
                    'horsepower' => '258 hp',
                    'torque' => '400 Nm',
                    'acceleration' => '5.4s (0-100 km/h)',
                    'top_speed' => '250 km/h',
                ],
                'description' => 'Mẫu Roadster mui trần quyến rũ, hiện thân của tự do và phong cách lái khoáng đạt.',
                'image' => 'products/z4-hero.jpg',
            ],
            // MOTORBIKES
            [
                'slug' => 'bmw-r1250gs-adventure',
                'name' => 'BMW R 1250 GS Adventure',
                'type' => VehicleType::MOTORBIKE,
                'price' => 709000000,
                'deposit_amount' => 20000000,
                'stock' => 15,
                'specifications' => [
                    'engine' => '1,254 cc Air/liquid-cooled 2-cylinder boxer',
                    'horsepower' => '136 hp at 7,750 rpm',
                    'torque' => '143 Nm at 6,250 rpm',
                    'tech' => 'BMW ShiftCam Technology',
                ],
                'description' => 'Nữ hoàng của dòng địa hình đường trường, chinh phục mọi ngõ ngách thế giới.',
                'image' => 'products/r1250gs-hero.jpg',
            ],
            [
                'slug' => 'bmw-ce04-electric',
                'name' => 'BMW CE 04 Electric Scooter',
                'type' => VehicleType::MOTORBIKE,
                'price' => 549000000,
                'deposit_amount' => 20000000,
                'stock' => 5,
                'specifications' => [
                    'engine' => 'Electric motor',
                    'horsepower' => '42 hp',
                    'range' => '130 km',
                    'charging' => '1h 40min (Fast charge)',
                    'acceleration' => '2.6s (0-50 km/h)',
                ],
                'description' => 'Tương lai của di chuyển đô thị với thiết kế Avant-garde độc bản.',
                'image' => 'products/ce04-hero.jpg',
            ],
            [
                'slug' => 'bmw-g310r-abs',
                'name' => 'BMW G 310 R',
                'type' => VehicleType::MOTORBIKE,
                'price' => 159000000,
                'deposit_amount' => 10000000,
                'stock' => 20,
                'specifications' => [
                    'engine' => '313 cc Water-cooled 1-cylinder',
                    'horsepower' => '34 hp at 9,250 rpm',
                    'torque' => '28 Nm at 7,500 rpm',
                    'weight' => '158.5 kg',
                ],
                'description' => 'Chiếc Roadset gọn nhẹ, linh hoạt cho trải nghiệm lái phấn khích mỗi ngày.',
                'image' => 'products/g310r-hero.jpg',
            ],
            [
                'slug' => 'bmw-rninet-pure',
                'name' => 'BMW R nineT Pure',
                'type' => VehicleType::MOTORBIKE,
                'price' => 550000000,
                'deposit_amount' => 20000000,
                'stock' => 6,
                'specifications' => [
                    'engine' => '1,170 cc Air/oil-cooled 2-cylinder boxer',
                    'horsepower' => '109 hp at 7,250 rpm',
                    'torque' => '116 Nm at 6,000 rpm',
                    'style' => 'Heritage / Classic',
                ],
                'description' => 'Vẻ đẹp cổ điển vượt thời gian kết hợp cùng kỹ thuật hiện đại.',
                'image' => 'products/rninet-hero.jpg',
            ],
            [
                'slug' => 'bmw-m1000rr-racing',
                'name' => 'BMW M 1000 RR',
                'type' => VehicleType::MOTORBIKE,
                'price' => 1599000000,
                'deposit_amount' => 50000000,
                'stock' => 2,
                'specifications' => [
                    'engine' => '999 cc Water/oil-cooled 4-cylinder line engine',
                    'horsepower' => '212 hp at 14,500 rpm',
                    'torque' => '113 Nm at 11,000 rpm',
                    'parts' => 'M Carbon wheels, M Winglets',
                ],
                'description' => 'Cỗ máy đua thuần chủng dành cho những tín đồ của tốc độ tối thượng.',
                'image' => 'products/m1000rr-hero.jpg',
            ],
        ];

        foreach ($vehicles as $v) {
            $product = Product::updateOrCreate(
                ['slug' => $v['slug']],
                [
                    'brand_id' => $bmw->id,
                    'name' => $v['name'],
                    'type' => $v['type'],
                    'price' => $v['price'],
                    'deposit_amount' => $v['deposit_amount'],
                    'stock' => $v['stock'],
                    'specifications' => $v['specifications'],
                    'description' => $v['description'],
                    'is_featured' => true,
                ]
            );

            $product->images()->updateOrCreate(
                ['path' => $v['image']],
                ['is_primary' => true, 'sort_order' => 0]
            );
        }
    }
}
