<?php

namespace App\Http\Controllers;

use App\Enums\VehicleType;
use App\Models\Category;
use App\Models\Product;
use App\Services\VehicleSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        protected VehicleSearchService $searchService
    ) {}

    /**
     * Display the public vehicle catalog.
     */
    public function index(Request $request): View
    {
        $filters = $request->all();
        if (! isset($filters['type']) && $request->route('type')) {
            $filters['type'] = $request->route('type');
        }

        return view('products.index', [
            'filters' => $filters,
        ]);
    }

    /**
     * Display a specific vehicle detail page.
     */
    public function show(string $slug): View
    {
        $vehicle = Product::where('slug', $slug)
            ->active()
            ->with(['category', 'primaryImage', 'images'])
            ->firstOrFail();

        $relatedVehicles = $vehicle->category_id
            ? Product::query()
                ->active()
                ->where('category_id', $vehicle->category_id)
                ->whereKeyNot($vehicle->id)
                ->with(['category', 'primaryImage', 'images'])
                ->latest()
                ->limit(3)
                ->get()
            : collect();

        return view('products.show', compact('relatedVehicles', 'vehicle'));
    }

    /**
     * Display the comparison view for selected vehicles.
     */
    public function compare(Request $request): View
    {
        $ids = explode(',', $request->query('ids', ''));
        $data = $this->searchService->getComparisonMatrix($ids);

        return view('products.compare', [
            'products' => $data['products'] ?? collect(),
            'matrix' => $data['matrix'] ?? [],
        ]);
    }

    /**
     * Get products by category branch for AJAX cascading dropdowns.
     */
    public function getProductsByCategory(Request $request): JsonResponse
    {
        $type = $request->query('category_type');

        $query = Product::active()->select('id', 'name');

        if ($type === 'oto') {
            $query->where('type', VehicleType::CAR->value);
        } elseif ($type === 'xe_may') {
            $query->where('type', VehicleType::MOTORBIKE->value);
        } else {
            $query->whereRaw('1 = 0');
        }

        $products = $query->orderBy('name')->get();

        return response()->json(['products' => $products]);
    }
}
