<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

class VehicleSearchService
{
    /**
     * Search and filter vehicles with optimized eager loading.
     */
    public function search(array $filters = []): LengthAwarePaginator
    {
        $query = Product::query()
            ->active()
            ->with(['category', 'primaryImage']); // Senior Architect: Optimized memory loading

        // Filter by Vehicle Type (Enum)
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter by Category
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Filter by Price Range
        if (! empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }
        if (! empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // Sorting
        $sort = $filters['sort'] ?? 'latest';
        match ($sort) {
            'price_low' => $query->orderBy('price', 'asc'),
            'price_high' => $query->orderBy('price', 'desc'),
            default => $query->latest(),
        };

        // CTO Requirement: withQueryString() to persist filters during pagination
        return $query->paginate(12)->withQueryString();
    }

    /**
     * Normalize specifications from multiple vehicles into a comparison matrix.
     */
    public function getComparisonMatrix(array $productIds): array
    {
        $products = Product::whereIn('id', $productIds)
            ->active()
            ->with(['category', 'primaryImage'])
            ->get();

        if ($products->isEmpty()) {
            return [];
        }

        // 1. Extract all unique specification keys across all selected vehicles
        $allKeys = $products->flatMap(function ($product) {
            return collect($product->specifications)->keys();
        })->unique();

        // 2. Build the matrix: [Spec Name => [Vehicle 1 Value, Vehicle 2 Value, ...]]
        $matrix = [];
        foreach ($allKeys as $key) {
            $row = [];
            foreach ($products as $product) {
                // Defensive check to ensure specifications is an array before access
                $specs = is_array($product->specifications) ? $product->specifications : [];
                $row[] = $specs[$key] ?? '---';
            }
            $matrix[$key] = $row;
        }

        return [
            'products' => $products,
            'matrix' => $matrix,
        ];
    }
}
