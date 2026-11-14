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
            /** @var Product $singleVehicle */
            $singleVehicle = $uniqueProducts->first();
            $sortedCompanions = $this->rankCompanionVehicles($activeVehicles, $singleVehicle);
            /** @var Product|null $suggestedCompanion */
            $suggestedCompanion = $sortedCompanions->first();

            if ($suggestedCompanion instanceof Product) {
                $suggestedCompareUrl = route('products.compare', [
                    'ids' => $singleVehicle->id.','.$suggestedCompanion->id,
                ], false);

                $payload['suggested_comparison_vehicle'] = array_merge(
                    $this->matcher->formatProductForTool($suggestedCompanion, 4),
                    ['compare_url' => $suggestedCompareUrl]
                );
                $payload['suggested_compare_url'] = $suggestedCompareUrl;
                $payload['message'] = "Chỉ khớp 1 mẫu xe duy nhất sau khi lọc trùng ({$singleVehicle->name}). Hệ thống đã tự động chọn mẫu xe gần nhất cùng phân khúc/dòng xe để đối chiếu là {$suggestedCompanion->name}. Hãy nêu rõ hai tên gọi cùng trỏ về {$singleVehicle->name}, cung cấp giá niêm yết + 3 thông số chính và đối chiếu trực tiếp với {$suggestedCompanion->name} kèm liên kết so sánh sẵn có.";
            } else {
                $payload['message'] = "Chỉ khớp 1 mẫu xe duy nhất sau khi lọc trùng ({$singleVehicle->name}). Cần ít nhất 2 mẫu xe khác nhau để lập bảng so sánh; vui lòng gợi ý Quý khách chọn thêm một mẫu xe khác để đối chiếu.";
            }

            $payload['available_vehicle_suggestions'] = $sortedCompanions
                ->take(4)
                ->map(fn (Product $vehicle): array => [
                    'name' => $vehicle->name,
                    'slug' => $vehicle->slug,
                    'category' => $vehicle->category?->name,
                    'price_vnd' => $this->matcher->money($vehicle->price),
                    'url' => route('products.show', $vehicle->slug, false),
                    'compare_url' => route('products.compare', [
                        'ids' => $singleVehicle->id.','.$vehicle->id,
                    ], false),
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

    /**
     * Rank candidate companion vehicles preferring identical segment and powertrain line.
     *
     * @param  Collection<int, Product>  $activeVehicles
     * @return Collection<int, Product>
     */
    private function rankCompanionVehicles(Collection $activeVehicles, Product $singleVehicle): Collection
    {
        $singlePrice = (float) ($singleVehicle->price ?? 0);

        return $activeVehicles
            ->reject(fn (Product $vehicle): bool => $vehicle->id === $singleVehicle->id)
            ->sort(function (Product $a, Product $b) use ($singleVehicle, $singlePrice): int {
                $tierA = $this->companionAffinityTier($a, $singleVehicle);
                $tierB = $this->companionAffinityTier($b, $singleVehicle);

                if ($tierA !== $tierB) {
                    return $tierA <=> $tierB;
                }

                $diffA = abs(((float) ($a->price ?? 0)) - $singlePrice);
                $diffB = abs(((float) ($b->price ?? 0)) - $singlePrice);

                if ($diffA !== $diffB) {
                    return $diffA <=> $diffB;
                }

                return $a->id <=> $b->id;
            })
            ->values();
    }

    private function companionAffinityTier(Product $candidate, Product $singleVehicle): int
    {
        if ($candidate->type === $singleVehicle->type && $candidate->category_id === $singleVehicle->category_id) {
            return 0;
        }

        if ($candidate->type === $singleVehicle->type) {
            return 1;
        }

        return 2;
    }
}
