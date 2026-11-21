<?php

namespace App\Actions\Products;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class CreateProductAction
{
    /**
     * Execute the action to create a new product.
     */
    public function execute(array $data, array $images = []): Product
    {
        return DB::transaction(function () use ($data, $images) {
            // 1. Handle Unique Slug
            $data['slug'] = Product::generateUniqueSlug($data['name']);

            // 1.5 Normalize Specifications (supports both specifications and specs keys)
            $rawSpecs = $data['specifications'] ?? ($data['specs'] ?? null);
            if (is_array($rawSpecs)) {
                $normalized = [];
                foreach ($rawSpecs as $k => $spec) {
                    if (is_array($spec) && ! empty($spec['key'])) {
                        $normalized[trim((string) $spec['key'])] = trim((string) ($spec['value'] ?? ''));
                    } elseif (is_string($k) && trim($k) !== '' && ! is_array($spec)) {
                        $normalized[trim($k)] = trim((string) $spec);
                    }
                }
                $data['specifications'] = $normalized;
            }
            unset($data['specs']);

            // 2. Create Product
            $product = Product::create($data);

            // 3. Handle Image Uploads
            if (! empty($images)) {
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
