<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function up(): void
    {
        Brand::updateOrCreate(
            ['slug' => 'bmw'],
            [
                'name' => 'BMW',
                'description' => 'The Ultimate Driving Machine. Thương hiệu hạng sang đỉnh cao từ Đức.',
                'logo' => 'brands/bmw-logo.png',
            ]
        );
    }

    /**
     * Alias for run if needed by some older versions, but standard is run.
     */
    public function run(): void
    {
        $this->up();
    }
}
