<?php

namespace App\Http\Controllers;

use App\Enums\VehicleType;
use App\Models\Brand;
use App\Models\Product;
use App\Services\VehicleSearchService;
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
        $vehicles = $this->searchService->search($request->all());
        $brands = Brand::orderBy('name')->get();
        $types = VehicleType::cases();

        return view('products.index', [
            'vehicles' => $vehicles,
            'brands' => $brands,
            'types' => $types,
            'filters' => $request->all(),
        ]);
    }

    /**
     * Display a specific vehicle detail page.
     */
    public function show(string $slug): View
    {
        $vehicle = Product::where('slug', $slug)
            ->active()
            ->with(['brand', 'images']) // Detail page needs all images
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
}
