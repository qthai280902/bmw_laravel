<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Enums\VehicleType;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncVehicles extends Command
{
    protected $signature = 'vehicle:sync';
    protected $description = 'Sync BMW car and motorcycle data without deleting existing records';

    public function handle()
    {
        $this->info('Starting vehicle synchronization...');

        $vehicles = [
            // --- CARS ---
            [
                'name' => 'BMW 330i M Sport',
                'category_slug' => 'sedan',
                'type' => VehicleType::CAR,
                'price' => 2499000000,
                'deposit_amount' => 100000000,
                'is_featured' => true,
                'specifications' => [
                    'Horsepower' => '258 HP',
                    'Torque' => '400 Nm',
                    '0-60mph' => '5.9s',
                    'Length_Width_Height' => '4.713 x 1.827 x 1.440 mm',
                    'Wheelbase' => '2.851 mm',
                    'Curb_Weight' => '1.650 kg',
                    'Engine' => '2.0L TwinPower Turbo',
                    'Fuel_Tank_Cap' => '59 l',
                    'Max_Power_RPM' => '258 HP @ 5.000-6.500 rpm',
                    'Max_Torque_RPM' => '400 Nm @ 1.550-4.400 rpm',
                    'Drive_Type' => 'Cầu sau (RWD)',
                    'Transmission_Type' => '8 cấp Steptronic',
                    'Zero_To_Hundred' => '5,9 giây',
                    'Top_Speed_KMH' => '250 km/h',
                ],
                'image' => 'https://www.hdcarwallpapers.com/download/bmw_330i_m_sport_2022_5k-2560x1440.jpg'
            ],
            [
                'name' => 'BMW 530i M Sport',
                'category_slug' => 'sedan',
                'type' => VehicleType::CAR,
                'price' => 3199000000,
                'deposit_amount' => 150000000,
                'is_featured' => true,
                'specifications' => [
                    'Horsepower' => '258 HP',
                    'Torque' => '400 Nm',
                    '0-60mph' => '6.2s',
                    'Length_Width_Height' => '5.060 x 1.900 x 1.515 mm',
                    'Wheelbase' => '2.995 mm',
                    'Curb_Weight' => '1.830 kg',
                    'Engine' => '2.0L TwinPower Turbo (Mild Hybrid)',
                    'Fuel_Tank_Cap' => '60 l',
                    'Max_Power_RPM' => '258 HP @ 5.250-6.500 rpm',
                    'Max_Torque_RPM' => '400 Nm @ 1.600-4.500 rpm',
                    'Drive_Type' => 'Cầu sau (RWD)',
                    'Transmission_Type' => '8 cấp Steptronic',
                    'Zero_To_Hundred' => '6,2 giây',
                    'Top_Speed_KMH' => '250 km/h',
                ],
                'image' => 'https://s3.paultan.org/tk/2025/01/2025-BMW-530i-M-Sport-Preview_Ext-1.jpg'
            ],
            [
                'name' => 'BMW X3 xDrive20i',
                'category_slug' => 'sav',
                'type' => VehicleType::CAR,
                'price' => 2299000000,
                'deposit_amount' => 100000000,
                'is_featured' => false,
                'specifications' => [
                    'Horsepower' => '184 HP',
                    'Torque' => '300 Nm',
                    '0-60mph' => '8.4s',
                    'Length_Width_Height' => '4.708 x 1.891 x 1.676 mm',
                    'Wheelbase' => '2.864 mm',
                    'Curb_Weight' => '1.875 kg',
                    'Engine' => '2.0L TwinPower Turbo',
                    'Fuel_Tank_Cap' => '65 l',
                    'Max_Power_RPM' => '184 HP @ 5.000-6.500 rpm',
                    'Max_Torque_RPM' => '300 Nm @ 1.350-4.000 rpm',
                    'Drive_Type' => 'xDrive (4 bánh toàn thời gian)',
                    'Transmission_Type' => '8 cấp Steptronic',
                    'Zero_To_Hundred' => '8,4 giây',
                    'Top_Speed_KMH' => '215 km/h',
                ],
                'image' => 'https://www.mad4wheels.com/img/free-car-images/mobile/21177/bmw-x3-g45-20-xdrive-2025-767849.jpg'
            ],
            [
                'name' => 'BMW X5 xDrive40i LCI',
                'category_slug' => 'sav',
                'type' => VehicleType::CAR,
                'price' => 4159000000,
                'deposit_amount' => 200000000,
                'is_featured' => true,
                'specifications' => [
                    'Horsepower' => '381 HP',
                    'Torque' => '520 Nm',
                    '0-60mph' => '5.4s',
                    'Length_Width_Height' => '4.935 x 2.004 x 1.765 mm',
                    'Wheelbase' => '2.975 mm',
                    'Curb_Weight' => '2.145 kg',
                    'Engine' => '3.0L Straight-six + 48V Mild Hybrid',
                    'Fuel_Tank_Cap' => '83 l',
                    'Max_Power_RPM' => '381 HP @ 5.200-6.250 rpm',
                    'Max_Torque_RPM' => '520 Nm @ 1.850-5.000 rpm',
                    'Drive_Type' => 'xDrive (AWD)',
                    'Transmission_Type' => '8 cấp Steptronic Sport',
                    'Zero_To_Hundred' => '5,4 giây',
                    'Top_Speed_KMH' => '250 km/h',
                ],
                'image' => 'https://hips.hearstapps.com/hmg-prod/images/2025-bmw-x5-xdrive40i-test-101-6679759711677.jpg'
            ],
            [
                'name' => 'BMW i4 eDrive40',
                'category_slug' => 'bmw-i',
                'type' => VehicleType::CAR,
                'price' => 3759000000,
                'deposit_amount' => 150000000,
                'is_featured' => true,
                'specifications' => [
                    'Horsepower' => '340 HP',
                    'Torque' => '430 Nm',
                    '0-60mph' => '5.7s',
                    'Length_Width_Height' => '4.783 x 1.852 x 1.448 mm',
                    'Wheelbase' => '2.856 mm',
                    'Curb_Weight' => '2.125 kg',
                    'Engine' => 'Động cơ điện (Single Motor)',
                    'Fuel_Tank_Cap' => '83.9 kWh (Pin)',
                    'Max_Power_RPM' => '340 HP @ 8.000-17.000 rpm',
                    'Max_Torque_RPM' => '430 Nm @ 0-5.000 rpm',
                    'Drive_Type' => 'Cầu sau (RWD)',
                    'Transmission_Type' => 'Hộp số đơn cấp',
                    'Zero_To_Hundred' => '5,7 giây',
                    'Top_Speed_KMH' => '190 km/h',
                ],
                'image' => 'https://img.netcarshow.com/BMW-i4_2022_1600x1200_wallpaper_01.jpg'
            ],
            [
                'name' => 'BMW XM Plug-in Hybrid',
                'category_slug' => 'sav',
                'type' => VehicleType::CAR,
                'price' => 10999000000,
                'deposit_amount' => 500000000,
                'is_featured' => true,
                'specifications' => [
                    'Horsepower' => '653 HP',
                    'Torque' => '800 Nm',
                    '0-60mph' => '4.3s',
                    'Length_Width_Height' => '5.110 x 2.005 x 1.755 mm',
                    'Wheelbase' => '3.105 mm',
                    'Curb_Weight' => '2.785 kg',
                    'Engine' => '4.4L V8 TwinPower Turbo + M Hybrid',
                    'Fuel_Tank_Cap' => '69 l (Xăng) + 25.7 kWh (Pin)',
                    'Max_Power_RPM' => '653 HP (Tổng hợp)',
                    'Max_Torque_RPM' => '800 Nm (Tổng hợp)',
                    'Drive_Type' => 'M xDrive (AWD)',
                    'Transmission_Type' => '8 cấp M Steptronic',
                    'Zero_To_Hundred' => '4,3 giây',
                    'Top_Speed_KMH' => '250 km/h (270 km/h with M Driver Package)',
                ],
                'image' => 'https://www.hdcarwallpapers.com/download/g_power_bmw_xm_label_2025_8k-1920x1080.jpg'
            ],
            [
                'name' => 'BMW M3 Competition M xDrive',
                'category_slug' => 'm-performance',
                'type' => VehicleType::CAR,
                'price' => 5499000000,
                'deposit_amount' => 250000000,
                'is_featured' => true,
                'specifications' => [
                    'Horsepower' => '510 HP',
                    'Torque' => '650 Nm',
                    '0-60mph' => '3.5s',
                    'Length_Width_Height' => '4.794 x 1.903 x 1.433 mm',
                    'Wheelbase' => '2.857 mm',
                    'Curb_Weight' => '1.780 kg',
                    'Engine' => '3.0L M TwinPower Turbo I6',
                    'Fuel_Tank_Cap' => '59 l',
                    'Max_Power_RPM' => '510 HP @ 6.250 rpm',
                    'Max_Torque_RPM' => '650 Nm @ 2.750-5.500 rpm',
                    'Drive_Type' => 'M xDrive (AWD)',
                    'Transmission_Type' => '8 cấp M Steptronic',
                    'Zero_To_Hundred' => '3,5 giây',
                    'Top_Speed_KMH' => '290 km/h',
                ],
                'image' => 'https://www.mad4wheels.com/img/free-car-images/mobile/20915/bmw-m3-g80-sedan-2025-753896.jpg'
            ],
            [
                'name' => 'BMW M4 Competition Coupe',
                'category_slug' => 'coupe',
                'type' => VehicleType::CAR,
                'price' => 5699000000,
                'deposit_amount' => 250000000,
                'is_featured' => true,
                'specifications' => [
                    'Horsepower' => '510 HP',
                    'Torque' => '650 Nm',
                    '0-60mph' => '3.9s',
                    'Length_Width_Height' => '4.794 x 1.887 x 1.393 mm',
                    'Wheelbase' => '2.857 mm',
                    'Curb_Weight' => '1.725 kg',
                    'Engine' => '3.0L M TwinPower Turbo I6',
                    'Fuel_Tank_Cap' => '59 l',
                    'Max_Power_RPM' => '510 HP @ 6.250 rpm',
                    'Max_Torque_RPM' => '650 Nm @ 2.750-5.500 rpm',
                    'Drive_Type' => 'Cầu sau (RWD)',
                    'Transmission_Type' => '8 cấp M Steptronic',
                    'Zero_To_Hundred' => '3,9 giây',
                    'Top_Speed_KMH' => '290 km/h',
                ],
                'image' => 'https://www.mad4wheels.com/img/free-car-images/mobile/21035/bmw-m4-g82-cs-2025-760773.jpg'
            ],
            [
                'name' => 'BMW M5 Touring (G99)',
                'category_slug' => 'm-performance',
                'type' => VehicleType::CAR,
                'price' => 6999000000,
                'deposit_amount' => 300000000,
                'is_featured' => true,
                'specifications' => [
                    'Horsepower' => '727 HP',
                    'Torque' => '1.000 Nm',
                    '0-60mph' => '3.6s',
                    'Length_Width_Height' => '5.096 x 1.970 x 1.516 mm',
                    'Wheelbase' => '3.006 mm',
                    'Curb_Weight' => '2.470 kg',
                    'Engine' => '4.4L V8 M TwinPower Turbo Hybrid',
                    'Fuel_Tank_Cap' => '60 l (Xăng) + 18.6 kWh (Pin)',
                    'Max_Power_RPM' => '727 HP @ 5.600-6.500 rpm',
                    'Max_Torque_RPM' => '1.000 Nm @ 1.800-5.400 rpm',
                    'Drive_Type' => 'M xDrive (AWD)',
                    'Transmission_Type' => '8 cấp M Steptronic',
                    'Zero_To_Hundred' => '3,6 giây',
                    'Top_Speed_KMH' => '305 km/h',
                ],
                'image' => 'https://s3.paultan.org/tk/2024/08/2025-BMW-M5-Touring-1.jpg'
            ],
            [
                'name' => 'BMW 550e xDrive',
                'category_slug' => 'sedan',
                'type' => VehicleType::CAR,
                'price' => 4399000000,
                'deposit_amount' => 200000000,
                'is_featured' => false,
                'specifications' => [
                    'Horsepower' => '489 HP',
                    'Torque' => '700 Nm',
                    '0-60mph' => '4.3s',
                    'Length_Width_Height' => '5.060 x 1.900 x 1.515 mm',
                    'Wheelbase' => '2.995 mm',
                    'Curb_Weight' => '2.155 kg',
                    'Engine' => '3.0L I6 Plug-in Hybrid',
                    'Fuel_Tank_Cap' => '60 l (Xăng) + 19.4 kWh (Pin)',
                    'Max_Power_RPM' => '489 HP @ 5.000-6.500 rpm',
                    'Max_Torque_RPM' => '700 Nm @ 1.750-4.700 rpm',
                    'Drive_Type' => 'xDrive (AWD)',
                    'Transmission_Type' => '8 cấp Steptronic Sport',
                    'Zero_To_Hundred' => '4,3 giây',
                    'Top_Speed_KMH' => '250 km/h',
                ],
                'image' => 'https://www.bmwblog.com/wp-content/uploads/2024/02/2025-BMW-550e-xDrive-01.jpg'
            ],

            // --- MOTORCYCLES ---
            [
                'name' => 'BMW G310R',
                'category_slug' => 'motorrad',
                'type' => VehicleType::MOTORBIKE,
                'price' => 189000000,
                'deposit_amount' => 10000000,
                'is_featured' => true,
                'specifications' => [
                    'Horsepower' => '34 HP',
                    'Torque' => '28 Nm',
                    '0-60mph' => '6.8s',
                    'Length_Width_Height' => '2005 x 849 x 1080 mm',
                    'Wheelbase' => '1374 mm',
                    'Curb_Weight' => '164 kg',
                    'Engine' => '1 xy-lanh, 313cc, DOHC',
                    'Fuel_Tank_Cap' => '11 l',
                    'Max_Power_RPM' => '34 HP @ 9500 rpm',
                    'Max_Torque_RPM' => '28 Nm @ 7500 rpm',
                    'Drive_Type' => 'Xích',
                    'Transmission_Type' => '6 cấp',
                    'Zero_To_Hundred' => '7.0 giây',
                    'Top_Speed_KMH' => '143 km/h',
                ],
                'image' => 'https://images.4kwallpapers.com/bikes/bmw-g310r-roadster-11983.jpg'
            ],
            [
                'name' => 'BMW S1000RR',
                'category_slug' => 'motorrad',
                'type' => VehicleType::MOTORBIKE,
                'price' => 959000000,
                'deposit_amount' => 50000000,
                'is_featured' => true,
                'specifications' => [
                    'Horsepower' => '205 HP',
                    'Torque' => '113 Nm',
                    '0-60mph' => '2.7s',
                    'Length_Width_Height' => '2073 x 826 x 1151 mm',
                    'Wheelbase' => '1441 mm',
                    'Curb_Weight' => '197 kg',
                    'Engine' => '4 xy-lanh, 999cc, ShiftCam',
                    'Fuel_Tank_Cap' => '16.5 l',
                    'Max_Power_RPM' => '205 HP @ 13500 rpm',
                    'Max_Torque_RPM' => '113 Nm @ 11000 rpm',
                    'Drive_Type' => 'Xích',
                    'Transmission_Type' => '6 cấp',
                    'Zero_To_Hundred' => '2.8 giây',
                    'Top_Speed_KMH' => '299 km/h',
                ],
                'image' => 'https://www.mcnews.com.au/wp-content/uploads/2024/10/2025-BMW-S-1000-RR-10.jpg'
            ],
            [
                'name' => 'BMW R1250GS',
                'category_slug' => 'motorrad',
                'type' => VehicleType::MOTORBIKE,
                'price' => 619000000,
                'deposit_amount' => 30000000,
                'is_featured' => true,
                'specifications' => [
                    'Horsepower' => '136 HP',
                    'Torque' => '143 Nm',
                    '0-60mph' => '3.4s',
                    'Length_Width_Height' => '2207 x 952 x 1430 mm',
                    'Wheelbase' => '1525 mm',
                    'Curb_Weight' => '249 kg',
                    'Engine' => 'Boxer 2 xy-lanh, 1254cc',
                    'Fuel_Tank_Cap' => '20 l',
                    'Max_Power_RPM' => '136 HP @ 7750 rpm',
                    'Max_Torque_RPM' => '143 Nm @ 6250 rpm',
                    'Drive_Type' => 'Trục (shaft drive)',
                    'Transmission_Type' => '6 cấp',
                    'Zero_To_Hundred' => '3.5 giây',
                    'Top_Speed_KMH' => '200 km/h',
                ],
                'image' => 'https://images.4kwallpapers.com/bikes/bmw-r-1250-gs-spirit-of-gs-edition-6120x3442-9112.jpg'
            ],
            [
                'name' => 'BMW F900R',
                'category_slug' => 'motorrad',
                'type' => VehicleType::MOTORBIKE,
                'price' => 459000000,
                'deposit_amount' => 20000000,
                'is_featured' => false,
                'specifications' => [
                    'Horsepower' => '105 HP',
                    'Torque' => '92 Nm',
                    '0-60mph' => '3.7s',
                    'Length_Width_Height' => '2140 x 815 x 1130 mm',
                    'Wheelbase' => '1518 mm',
                    'Curb_Weight' => '211 kg',
                    'Engine' => '2 xy-lanh, 895cc',
                    'Fuel_Tank_Cap' => '13 l',
                    'Max_Power_RPM' => '105 HP @ 8500 rpm',
                    'Max_Torque_RPM' => '92 Nm @ 6500 rpm',
                    'Drive_Type' => 'Xích',
                    'Transmission_Type' => '6 cấp',
                    'Zero_To_Hundred' => '3.8 giây',
                    'Top_Speed_KMH' => '200 km/h',
                ],
                'image' => 'https://amcn.com.au/wp-content/uploads/2021/01/P90579128_highRes_the-new-bmw-f-900-r.jpg'
            ],
            [
                'name' => 'BMW K1600GT',
                'category_slug' => 'motorrad',
                'type' => VehicleType::MOTORBIKE,
                'price' => 1200000000,
                'deposit_amount' => 50000000,
                'is_featured' => false,
                'specifications' => [
                    'Horsepower' => '160 HP',
                    'Torque' => '180 Nm',
                    '0-60mph' => '3.3s',
                    'Length_Width_Height' => '2340 x 1000 x 1460 mm',
                    'Wheelbase' => '1618 mm',
                    'Curb_Weight' => '343 kg',
                    'Engine' => '6 xy-lanh thẳng hàng, 1649cc',
                    'Fuel_Tank_Cap' => '26.5 l',
                    'Max_Power_RPM' => '160 HP @ 6750 rpm',
                    'Max_Torque_RPM' => '180 Nm @ 5250 rpm',
                    'Drive_Type' => 'Trục',
                    'Transmission_Type' => '6 cấp',
                    'Zero_To_Hundred' => '3.4 giây',
                    'Top_Speed_KMH' => '200 km/h',
                ],
                'image' => 'https://www.webbikeworld.com/wp-content/uploads/2021/10/2022-BMW-K-1600-GT-7.jpg'
            ],
        ];

        foreach ($vehicles as $data) {
            $category = Category::where('slug', $data['category_slug'])->first();
            if (!$category) {
                // If category missing, create it as a safety measure
                $category = Category::create([
                    'name' => ucfirst($data['category_slug']),
                    'slug' => $data['category_slug'],
                ]);
            }

            $product = Product::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'category_id' => $category->id,
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'price' => $data['price'],
                    'deposit_amount' => $data['deposit_amount'],
                    'is_featured' => $data['is_featured'],
                    'is_active' => true, // Ensure active
                    'stock' => 10,
                    'specifications' => $data['specifications'],
                    'description' => $this->generateDescription($data['name'], $data['type']->value),
                ]
            );

            // Sync image
            $product->images()->updateOrCreate(
                ['is_primary' => true],
                ['path' => $data['image'], 'sort_order' => 0]
            );

            $this->line("Synced: {$data['name']}");
        }

        $this->info('Synchronization complete!');
    }

    private function generateDescription(string $name, string $type): string
    {
        if ($type === VehicleType::MOTORBIKE->value) {
            return "Chuẩn mực cơ khí Đức trên hai bánh xe. {$name} mang lại cảm giác lái thuần khiết, linh hoạt và công nghệ hàng đầu phân khúc.";
        }
        return "Tuyệt tác thiết kế và công nghệ của tập đoàn BMW. {$name} là sự kết hợp hoàn hảo giữa sang trọng, tiện nghi và hiệu năng vận hành đỉnh cao.";
    }
}
