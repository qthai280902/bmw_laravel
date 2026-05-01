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
        // Merge route defaults (e.g., type=accessory from accessories.index) into filters
        $filters = $request->all();
        if (! isset($filters['type']) && $request->route('type')) {
            $filters['type'] = $request->route('type');
        }

        $vehicles = $this->searchService->search($filters);

        // Filter categories contextually for accessories vs vehicles
        $isAccessory = ($filters['type'] ?? null) === 'accessory';
        $categories = $isAccessory
            ? Category::where('name', 'like', '%Phụ kiện%')->orderBy('name')->get()
            : Category::where('name', 'not like', '%Phụ kiện%')->orderBy('name')->get();

        $types = VehicleType::cases();

        return view('products.index', [
            'vehicles' => $vehicles,
            'categories' => $categories,
            'types' => $types,
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
            ->with(['category', 'images']) // Detail page needs all images
            ->firstOrFail();

        return view('products.show', compact('vehicle'));
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
        } elseif ($type === 'phu_kien') {
            $query->whereHas('category', function ($q) {
                $q->where('name', 'like', '%Phụ kiện%');
            });
        }

        $products = $query->orderBy('name')->get();

        return response()->json(['products' => $products]);
    }
}
