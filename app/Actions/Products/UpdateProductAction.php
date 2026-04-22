<?php

namespace App\Actions\Products;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateProductAction
{
    /**
     * Execute the action to update an existing product.
     *
     * @param Product $product
     * @param array $data
     * @param array $newImages
     * @return Product
     */
    public function execute(Product $product, array $data, array $newImages = []): Product
    {
        return DB::transaction(function () use ($product, $data, $newImages) {
            // 1. Handle Slug if Name changed
            if (isset($data['name']) && $data['name'] !== $product->name) {
                $data['slug'] = Product::generateUniqueSlug($data['name']);
            }

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

            // 2. Update Product
            $product->update($data);

            // 3. Handle New Image Uploads
            if (!empty($newImages)) {
                $this->uploadNewImages($product, $newImages);
            }

            return $product;
        });
    }

    /**
     * Handle the new image upload logic.
     */
    private function uploadNewImages(Product $product, array $newImages): void
    {
        $lastSortOrder = $product->images()->max('sort_order') ?? -1;

        foreach ($newImages as $index => $image) {
            $path = $image->store('products', 'public');

            $product->images()->create([
                'path' => $path,
                'is_primary' => false, // New images are not primary by default on update
                'sort_order' => $lastSortOrder + $index + 1,
            ]);
        }
    }
}
