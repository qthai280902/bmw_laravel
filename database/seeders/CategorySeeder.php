<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Sedan',
                'slug' => Str::slug('Sedan'),
                'description' => 'Xe sedan thể thao hạng sang (3 Series, 5 Series, 7 Series).',
            ],
            [
                'name' => 'SAV (SUV)',
                'slug' => Str::slug('SAV'),
                'description' => 'Sports Activity Vehicle - Các dòng X của BMW (X3, X5, X7).',
            ],
            [
                'name' => 'Coupe / Gran Coupe',
                'slug' => Str::slug('Coupe'),
                'description' => 'Xe thể thao 2 cửa hoặc 4 cửa lai coupe của BMW (4 Series, 8 Series).',
            ],
            [
                'name' => 'M Performance',
                'slug' => Str::slug('M Performance'),
                'description' => 'Dòng xe hiệu năng cao đích thực (M3, M4, M5, M8, v.v.).',
            ],
            [
                'name' => 'BMW i (Thuần điện)',
                'slug' => Str::slug('BMW i'),
                'description' => 'Dòng xe điện của tương lai (iX, i4, i7).',
            ],
            [
                'name' => 'Motorrad',
                'slug' => Str::slug('Motorrad'),
                'description' => 'Phân khúc xe mô tô BMW Motorrad (S1000RR, GS, v.v.).',
            ],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(['slug' => $cat['slug']], $cat);
        }
    }
}
