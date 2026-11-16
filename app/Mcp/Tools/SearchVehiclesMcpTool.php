<?php

namespace App\Mcp\Tools;

use App\Enums\VehicleType;
use App\Models\Product;
use App\Services\VehicleSearchService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Tìm kiếm danh mục ô tô BMW, BMW Motorrad và phụ kiện chính hãng đang mở bán tại showroom theo từ khóa, loại sản phẩm hoặc khoảng giá VNĐ.')]
class SearchVehiclesMcpTool extends Tool
{
    public function __construct(
        private VehicleSearchService $searchService,
    ) {}

    public function handle(Request $request): Response
    {
        $type = trim((string) ($request->get('type') ?? ''));
        $normalizedType = match (strtolower($type)) {
            'oto', 'car' => VehicleType::CAR->value,
            'xe_may', 'motorrad', 'motorbike' => VehicleType::MOTORBIKE->value,
            'accessory', 'phu_kien' => VehicleType::ACCESSORY->value,
            default => '',
        };

        $filters = array_filter([
            'search' => trim((string) ($request->get('keyword') ?? '')),
            'type' => $normalizedType,
            'min_price' => $request->get('min_price'),
            'max_price' => $request->get('max_price'),
            'sort' => trim((string) ($request->get('sort') ?? 'latest')),
        ], static fn (mixed $value): bool => $value !== '' && $value !== null);

        $results = $this->searchService->search($filters);

        $items = collect($results->items())
            ->take(10)
            ->map(function (Product $product): array {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'type' => $product->type instanceof VehicleType ? $product->type->value : (string) $product->type,
                    'category' => $product->category?->name,
                    'price_vnd' => (int) $product->price,
                    'deposit_vnd' => $product->deposit_amount ? (int) $product->deposit_amount : null,
                    'available' => (bool) $product->is_active,
                    'url' => route('products.show', $product->slug),
                ];
            })
            ->values()
            ->all();

        $payload = [
            'total' => $results->total(),
            'count' => count($items),
            'products' => $items,
        ];

        return Response::text(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'keyword' => $schema->string()
                ->description('Từ khóa tên xe hoặc phụ kiện (ví dụ: 330i, X3 M50, S1000RR, thảm lót sàn).')
                ->nullable(),
            'type' => $schema->string()
                ->enum(['car', 'motorrad', 'accessory', 'oto', 'xe_may'])
                ->description('Loại sản phẩm cần lọc.')
                ->nullable(),
            'min_price' => $schema->integer()
                ->description('Giá tối thiểu (VNĐ).')
                ->nullable(),
            'max_price' => $schema->integer()
                ->description('Giá tối đa (VNĐ).')
                ->nullable(),
            'sort' => $schema->string()
                ->enum(['latest', 'price_low', 'price_high'])
                ->description('Thứ tự sắp xếp.')
                ->nullable(),
        ];
    }
}
