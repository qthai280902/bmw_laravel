<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Products\CreateProductAction;
use App\Actions\Products\UpdateProductAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::with('brand')->latest();

        // 1. Filtering
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $products = $query->paginate(10)->withQueryString();
        $brands = Brand::orderBy('name')->get();

        return view('admin.products.index', compact('products', 'brands'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $brands = Brand::orderBy('name')->get();

        return view('admin.products.create', compact('brands'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request, CreateProductAction $action)
    {
        $product = $action->execute(
            $request->validated(),
            $request->file('new_images') ?? []
        );

        return redirect()
            ->route('admin.products.index')
            ->with('success', "Xe {$product->name} đã được tạo thành công.");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $brands = Brand::orderBy('name')->get();
        $product->load('images');

        return view('admin.products.edit', compact('product', 'brands'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, Product $product, UpdateProductAction $action)
    {
        $updatedProduct = $action->execute(
            $product,
            $request->validated(),
            $request->file('new_images') ?? []
        );

        return redirect()
            ->route('admin.products.index')
            ->with('success', "Xe {$updatedProduct->name} đã được cập nhật.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return back()->with('success', 'Đã xóa xe thành công.');
    }
}
