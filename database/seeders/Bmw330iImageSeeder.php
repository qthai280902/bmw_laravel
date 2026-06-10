<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class Bmw330iImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $product = Product::query()
            ->where('slug', 'bmw-330i-sedan')
            ->first();

        if (! $product) {
            return;
        }

        $product->images()->update(['is_primary' => false]);

        $images = [
            ['path' => 'images/cars/330i/hero-front-three-quarter.png', 'is_primary' => true, 'sort_order' => 0],
            ['path' => 'images/cars/330i/side-profile.png', 'is_primary' => false, 'sort_order' => 1],
            ['path' => 'images/cars/330i/rear-three-quarter.png', 'is_primary' => false, 'sort_order' => 2],
            ['path' => 'images/cars/330i/cockpit-interior.png', 'is_primary' => false, 'sort_order' => 3],
            ['path' => 'images/cars/330i/design-detail-wheel-light.png', 'is_primary' => false, 'sort_order' => 4],
            ['path' => 'images/cars/330i/lifestyle-showroom.png', 'is_primary' => false, 'sort_order' => 5],
            ['path' => 'images/cars/330i/urban-motion.png', 'is_primary' => false, 'sort_order' => 6],
            ['path' => 'images/cars/330i/studio-front-three-quarter.png', 'is_primary' => false, 'sort_order' => 7],
            ['path' => 'images/cars/330i.png', 'is_primary' => false, 'sort_order' => 8],
        ];

        foreach ($images as $image) {
            $product->images()->updateOrCreate(
                ['path' => $image['path']],
                [
                    'is_primary' => $image['is_primary'],
                    'sort_order' => $image['sort_order'],
                ]
            );
        }
    }
}
