<?php

namespace App\Actions\Products;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateProductAction
{
    /**
     * Execute the action to create a new product.
     *
     * @param array $data
     * @param array $images
     * @return Product
     */
    public function execute(array $data, array $images = []): Product
    {
        return DB::transaction(function () use ($data, $images) {
            // 1. Handle Unique Slug
            $data['slug'] = Product::generateUniqueSlug($data['name']);

            // 1.5 Normalize Specifications
            if (isset($data['specifications']) && is_array($data['specifications'])) {
                $normalized = [];
                foreach ($data['specifications'] as $spec) {
                    if (!empty($spec['key'])) {
                        $normalized[$spec['key']] = $spec['value'] ?? '';
                    }
                }
                $data['specifications'] = $normalized;
            }

            // 2. Create Product
            $product = Product::create($data);

            // 3. Handle Image Uploads
            if (!empty($images)) {
                $this->uploadImages($product, $images);
            }

            return $product;
        });
    }

    /**
     * Handle the image upload logic.
     */
    private function uploadImages(Product $product, array $images): void
    {
        foreach ($images as $index => $image) {
            $path = $image->store('products', 'public');

            $product->images()->create([
                'path' => $path,
                'is_primary' => $index === 0, // First image is primary by default
                'sort_order' => $index,
            ]);
        }
    }
}
