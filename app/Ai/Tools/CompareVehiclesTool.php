<?php

namespace App\Ai\Tools;

use App\Models\Product;
use App\Services\Ai\ShowroomCatalogMatcher;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CompareVehiclesTool implements Tool
{
    public function __construct(private readonly ShowroomCatalogMatcher $matcher) {}

    public function name(): string
    {
        return 'compare_vehicles';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Truy xuất thông số kỹ thuật đầy đủ, giá niêm yết và liên kết trang so sánh của 2 đến 3 mẫu xe BMW (ô tô hoặc BMW Motorrad) đang kinh doanh để đối chiếu trực tiếp.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $queries = collect($request->array('vehicles'))
            ->map(fn (mixed $item): string => trim((string) $item))
            ->filter()
            ->take(3)
            ->values();

        $activeVehicles = Product::query()
            ->active()
            ->vehicles()
            ->with('category')
            ->select([
                'id',
                'category_id',
                'name',
                'slug',
                'type',
                'price',
                'deposit_amount',
                'specifications',
                'description',
            ])
            ->orderBy('id')
            ->get();

        $matchedProducts = collect();
        $unmatchedQueries = [];

        foreach ($queries as $vehicleQuery) {
            $bestMatch = $this->findBestMatchingVehicle($activeVehicles, $vehicleQuery);

            if ($bestMatch) {
                $matchedProducts->push($bestMatch);
            } else {
                $unmatchedQueries[] = $vehicleQuery;
            }
        }

        /** @var Collection<int, Product> $uniqueProducts */
        $uniqueProducts = $matchedProducts->unique('id')->values();
        $matchedVehicles = $uniqueProducts
            ->map(fn (Product $product): array => $this->matcher->formatProductForTool($product, null))
            ->values()
            ->all();

        $compareUrl = $uniqueProducts->count() >= 2
            ? route('products.compare', ['ids' => $uniqueProducts->pluck('id')->implode(',')], false)
            : null;

        $status = match (true) {
            $uniqueProducts->count() >= 2 => 'ok',
            $uniqueProducts->count() === 1 => 'needs_more_vehicles',
            default => 'no_match',
        };

        $payload = [
            'status' => $status,
            'matched_vehicles' => $matchedVehicles,
            'compare_url' => $compareUrl,
            'unmatched_queries' => $unmatchedQueries,
        ];

        if ($uniqueProducts->count() === 1) {
            $singleVehicle = $uniqueProducts->first();
            $payload['message'] = "Chỉ khớp 1 mẫu xe duy nhất sau khi lọc trùng ({$singleVehicle->name}). Cần ít nhất 2 mẫu xe khác nhau để lập bảng so sánh; vui lòng gợi ý Quý khách chọn thêm một mẫu xe khác để đối chiếu.";
            $payload['available_vehicle_suggestions'] = $activeVehicles
                ->reject(fn (Product $vehicle): bool => $vehicle->id === $singleVehicle->id)
                ->take(4)
                ->map(fn (Product $vehicle): array => [
                    'name' => $vehicle->name,
                    'slug' => $vehicle->slug,
                    'price_vnd' => $this->matcher->money($vehicle->price),
                    'url' => route('products.show', $vehicle->slug, false),
                ])
                ->values()
                ->all();
        } elseif ($uniqueProducts->isEmpty()) {
            $payload['message'] = 'Không tìm thấy mẫu xe phù hợp trong dữ liệu showroom hiện tại để so sánh.';
        }

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vehicles' => $schema->array()
                ->items($schema->string())
                ->min(2)
                ->max(3)
                ->description('Danh sách 2 đến 3 tên hoặc slug mẫu xe cần so sánh (ví dụ: ["BMW 330i", "BMW 530i"]).')
                ->required(),
        ];
    }

    /**
     * @param  Collection<int, Product>  $activeVehicles
     */
    private function findBestMatchingVehicle(Collection $activeVehicles, string $vehicleQuery): ?Product
    {
        $scored = $activeVehicles
            ->map(fn (Product $vehicle): array => [
                'vehicle' => $vehicle,
                'score' => $this->matcher->scoreProductQuery($vehicle, $vehicleQuery, true, false),
            ])
            ->filter(fn (array $entry): bool => $entry['score'] > 0)
            ->sortByDesc('score')
            ->first();

        return $scored['vehicle'] ?? null;
    }
}
