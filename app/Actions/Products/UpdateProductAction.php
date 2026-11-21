<?php

namespace App\Actions\Products;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UpdateProductAction
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $images
     */
    public function execute(Product $product, array $data, array $images = []): Product
    {
        return DB::transaction(function () use ($product, $data, $images): Product {
            if (! empty($data['name']) && empty($data['slug'])) {
                $data['slug'] = Product::generateUniqueSlug((string) $data['name'], $product->id);
            }

            $rawSpecs = $data['specifications'] ?? ($data['specs'] ?? null);
            if (is_array($rawSpecs)) {
                $cleanSpecs = [];
                foreach ($rawSpecs as $k => $spec) {
                    if (is_array($spec) && ! empty($spec['key']) && isset($spec['value']) && $spec['value'] !== '') {
                        $cleanSpecs[trim((string) $spec['key'])] = trim((string) $spec['value']);
                    } elseif (is_string($k) && trim($k) !== '' && ! is_array($spec) && (string) $spec !== '') {
                        $cleanSpecs[trim($k)] = trim((string) $spec);
                    }
                }
                $data['specifications'] = $cleanSpecs;
            }
            unset($data['specs']);

            if (array_key_exists('is_active', $data)) {
                $data['is_active'] = (bool) $data['is_active'];
            }
            if (array_key_exists('is_featured', $data)) {
                $data['is_featured'] = (bool) $data['is_featured'];
            }

            $deleteImageIds = isset($data['delete_image_ids']) && is_array($data['delete_image_ids'])
                ? array_values(array_unique(array_map('intval', $data['delete_image_ids'])))
                : [];
            $primaryImageId = isset($data['primary_image_id']) && is_numeric($data['primary_image_id'])
                ? (int) $data['primary_image_id']
                : null;

            unset($data['images'], $data['delete_image_ids'], $data['primary_image_id']);

            $product->update($data);

            if ($deleteImageIds !== []) {
                $imagesToDelete = $product->images()->whereIn('id', $deleteImageIds)->get();
                foreach ($imagesToDelete as $imgToDelete) {
                    $imgToDelete->delete();
                }
            }

            if ($primaryImageId !== null && ! in_array($primaryImageId, $deleteImageIds, true)) {
                $existsOnProduct = $product->images()->whereKey($primaryImageId)->exists();
                if ($existsOnProduct) {
                    $product->images()->update(['is_primary' => false]);
                    $product->images()->whereKey($primaryImageId)->update(['is_primary' => true]);
                }
            }

            if (! empty($images)) {
                $hasPrimary = $product->images()->where('is_primary', true)->exists();
                $currentCount = $product->images()->count();
                foreach ($images as $index => $image) {
                    $path = $image->store('products', 'public');
                    $product->images()->create([
                        'path' => $path,
                        'is_primary' => ! $hasPrimary && $index === 0,
                        'sort_order' => $currentCount + $index,
                    ]);
                }
            }

            if (! $product->images()->where('is_primary', true)->exists()) {
                $firstRemaining = $product->images()->orderBy('sort_order')->orderBy('id')->first();
                if ($firstRemaining) {
                    $firstRemaining->update(['is_primary' => true]);
                }
            }

            return $product;
        });
    }
}
