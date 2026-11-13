<?php

namespace App\Ai\Tools;

use App\Services\Ai\ShowroomCatalogMatcher;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchCatalogTool implements Tool
{
    public function __construct(private readonly ShowroomCatalogMatcher $matcher) {}

    public function name(): string
    {
        return 'search_catalog';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Tra cứu danh mục sản phẩm public đang kinh doanh của BMW Showroom (ô tô BMW, BMW Motorrad, phụ kiện chính hãng) theo từ khóa (hỗ trợ đồng nghĩa SUV/SAV, xe điện/i4/iX, xe máy/mô tô/PKL/Motorrad), loại sản phẩm, phân khúc hoặc ngân sách tối đa VNĐ.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $keyword = trim((string) ($request['keyword'] ?? ''));
        $type = trim((string) ($request['type'] ?? ''));
        $category = trim((string) ($request['category'] ?? ''));
        $maxPrice = isset($request['max_price']) && is_numeric($request['max_price'])
            ? (int) $request['max_price']
            : null;

        $products = $this->matcher->searchCatalog(
            keyword: $keyword !== '' ? $keyword : null,
            type: $type !== '' ? $type : null,
            category: $category !== '' ? $category : null,
            maxPrice: $maxPrice !== null && $maxPrice > 0 ? $maxPrice : null,
            limit: 8,
        );

        $items = $products
            ->map(fn ($product): array => $this->matcher->formatProductForTool($product, 5))
            ->values()
            ->all();

        $payload = [
            'status' => count($items) > 0 ? 'ok' : 'no_match',
            'total_found' => count($items),
            'items' => $items,
            'routes' => [
                'catalog' => route('products.index', [], false),
                'accessories' => route('accessories.index', [], false),
                'booking_test_drive' => route('appointments.create', ['type' => 'test_drive'], false),
                'booking_quote' => route('appointments.create', ['type' => 'quote'], false),
            ],
        ];

        if (count($items) === 0) {
            $payload['message'] = 'Không tìm thấy sản phẩm public nào khớp với điều kiện tra cứu hiện tại.';
        }

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'keyword' => $schema->string()
                ->description('Từ khóa tên xe, dòng xe, phụ kiện hoặc loại xe (ví dụ: 330i, S1000RR, X5, SUV, SAV, xe điện, i4, iX, xe máy, mô tô, PKL, thảm lót sàn).')
                ->nullable(),
            'type' => $schema->string()
                ->enum(['car', 'motorrad', 'accessory'])
                ->description('Lọc theo nhóm sản phẩm: car (ô tô), motorrad (xe máy phân khối lớn BMW Motorrad), hoặc accessory (phụ kiện chính hãng).')
                ->nullable(),
            'category' => $schema->string()
                ->description('Tên hoặc slug phân khúc danh mục (ví dụ: Sedan, SAV, SUV, Sport, M Performance).')
                ->nullable(),
            'max_price' => $schema->integer()
                ->description('Ngân sách tối đa tính bằng VNĐ (ví dụ: 3000000000 cho 3 tỷ VNĐ).')
                ->nullable(),
        ];
    }
}
