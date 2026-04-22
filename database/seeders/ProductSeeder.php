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
                    'Engine' => 'Electric motor',
                    'Horsepower' => '340 hp',
                    'Torque' => '430 Nm',
                    '0-100km/h' => '5.7 seconds',
                    'Top Speed' => '190 km/h',
                    'Drivetrain' => 'Rear-Wheel Drive (RWD)',
                    'Range' => '590 km (WLTP)',
                    'Battery Capacity' => '83.9 kWh',
                    'Fuel Consumption' => '0.0 L/100km (Electric)',
                ],
                'description' => 'Mẫu Gran Coupe thuần điện đầu tiên của BMW với tinh thần thể thao nguyên bản, kết hợp sự sang trọng bậc nhất và công nghệ tương lai.',
                'image' => 'https://images.unsplash.com/photo-1617469767053-d3b508a042a2?auto=format&fit=crop&q=80&w=1600',
            ],
            [
                'slug' => 'bmw-x5-xdrive40i',
                'name' => 'BMW X5 xDrive40i MSport',
                'type' => VehicleType::CAR,
                'price' => 4169000000,
                'deposit_amount' => 50000000,
                'stock' => 8,
                'specifications' => [
                    'Engine' => '3.0L Inline 6-cylinder BMW TwinPower Turbo',
                    'Horsepower' => '381 hp',
                    'Torque' => '540 Nm',
                    '0-100km/h' => '5.4 seconds',
                    'Top Speed' => '243 km/h',
                    'Drivetrain' => 'All-Wheel Drive (xDrive)',
                    'Transmission' => '8-speed Steptronic Sport',
                    'Fuel Consumption' => '8.5 L/100km',
                ],
                'description' => 'Mẫu SUV sang trọng biểu tượng, dẫn đầu mọi hành trình với sự linh hoạt tối đa và khả năng vận hành mạnh mẽ trên mọi địa hình.',
                'image' => 'https://images.unsplash.com/photo-1555215695-3004980ad54e?auto=format&fit=crop&q=80&w=1600',
            ],
            [
                'slug' => 'bmw-7-series-735i',
                'name' => 'BMW 7 Series 735i Pure Excellence',
                'type' => VehicleType::CAR,
                'price' => 4499000000,
                'deposit_amount' => 100000000,
                'stock' => 2,
                'specifications' => [
                    'Engine' => '3.0L Inline 6-cylinder BMW TwinPower Turbo',
                    'Horsepower' => '286 hp',
                    'Torque' => '425 Nm',
                    '0-100km/h' => '6.7 seconds',
                    'Top Speed' => '250 km/h',
                    'Drivetrain' => 'Rear-Wheel Drive (RWD)',
                    'Luxury Features' => 'BMW Interaction Bar, Theatre Screen 31.3"',
                    'Fuel Consumption' => '7.9 L/100km',
                ],
                'description' => 'Đỉnh cao của sự sang trọng và công nghệ tương lai. Một chuẩn mực mới cho dòng sedan dành cho doanh nhân.',
                'image' => 'https://images.unsplash.com/photo-1621359982464-329b35fc2580?auto=format&fit=crop&q=80&w=1600',
            ],
            [
                'slug' => 'bmw-m4-competition',
                'name' => 'BMW M4 Competition Coupé',
                'type' => VehicleType::CAR,
                'price' => 5599000000,
                'deposit_amount' => 100000000,
                'stock' => 4,
                'specifications' => [
                    'Engine' => '3.0L M TwinPower Turbo inline 6-cylinder',
                    'Horsepower' => '510 hp',
                    'Torque' => '650 Nm',
                    '0-100km/h' => '3.9 seconds',
                    'Top Speed' => '290 km/h (M Driver Bundle)',
                    'Drivetrain' => 'Rear-Wheel Drive (RWD)',
                    'Transmission' => '8-speed M Steptronic with Drivelogic',
                    'Fuel Consumption' => '10.2 L/100km',
                ],
                'description' => 'Sức mạnh thuần túy từ đường đua kết hợp phong cách Coupé thời thượng. Cỗ máy dành cho những người khao khát tốc độ.',
                'image' => 'https://images.unsplash.com/photo-1619362224246-0879103606af?auto=format&fit=crop&q=80&w=1600',
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
                    'Displacement' => '1,254 cc',
                    'Engine' => 'Air/liquid-cooled 2-cylinder boxer',
                    'Horsepower' => '136 hp at 7,750 rpm',
                    'Torque' => '143 Nm at 6,250 rpm',
                    'Seat Height' => '890 / 910 mm',
                    'Dry Weight' => '268 kg',
                    'Fuel Tank' => '30 Liters',
                    'Top Speed' => '> 200 km/h',
                ],
                'description' => 'Nữ hoàng của dòng địa hình đường trường, chinh phục mọi ngõ ngách thế giới với động cơ ShiftCam mạnh mẽ.',
                'image' => 'https://images.unsplash.com/photo-1599819811279-d5ad9cccf838?auto=format&fit=crop&q=80&w=1600',
            ],
            [
                'slug' => 'bmw-m1000rr-racing',
                'name' => 'BMW M 1000 RR',
                'type' => VehicleType::MOTORBIKE,
                'price' => 1599000000,
                'deposit_amount' => 50000000,
                'stock' => 2,
                'specifications' => [
                    'Displacement' => '999 cc',
                    'Engine' => 'Water/oil-cooled 4-cylinder line engine',
                    'Horsepower' => '212 hp at 14,500 rpm',
                    'Torque' => '113 Nm at 11,000 rpm',
                    'Seat Height' => '832 mm',
                    'Dry Weight' => '192 kg',
                    '0-100km/h' => '3.1 seconds',
                    'Top Speed' => '306 km/h',
                ],
                'description' => 'Cỗ máy đua thuần chủng dành cho những tín đồ của tốc độ tối thượng. Sản phẩm đầu tiên từ phân nhánh M cho dòng Motorrad.',
                'image' => 'https://images.unsplash.com/photo-1614165939020-f71f0CC8e411?auto=format&fit=crop&q=80&w=1600',
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

            // Xóa ảnh cũ để tránh lỗi cache route khi seed lại logic URL tuyệt đối
            $product->images()->delete();
            $product->images()->create([
                'path' => $v['image'],
                'is_primary' => true,
                'sort_order' => 0,
            ]);
        }
    }
}
