<?php

namespace App\Services\Ai;

use App\Ai\Agents\ShowroomAssistant;
use App\Enums\VehicleType;
use App\Models\Article;
use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ShowroomAssistantService
{
    /**
     * @return array{answer: string, suggestions: array<int, array{label: string, url: string}>, status: string}
     */
    public function ask(string $message): array
    {
        $context = $this->publicContext($message);
        $suggestions = $this->suggestionsFor($message, $context);
        $provider = $this->configuredProvider();
        $model = $this->configuredModel();

        if ($configurationReason = $this->configurationFailureReason($provider)) {
            Log::info('Showroom AI assistant skipped.', [
                'reason' => $configurationReason,
                'provider' => $provider,
            ]);

            return $this->fallbackResponse($suggestions, 'configuration');
        }

        try {
            $response = ShowroomAssistant::make()->prompt(
                prompt: $this->buildPrompt($message, $context),
                provider: $provider,
                model: $model,
                timeout: 20,
            );

            return [
                'answer' => trim($response->text) ?: $this->fallbackMessage(),
                'suggestions' => $suggestions,
                'status' => 'ok',
            ];
        } catch (Throwable $exception) {
            Log::warning('Showroom AI assistant request failed.', [
                'reason' => 'provider_error',
                'exception' => $exception::class,
                'provider' => $provider,
            ]);

            return $this->fallbackResponse($suggestions, 'provider_error');
        }
    }

    /**
     * @return array{
     *     products: array<int, array<string, mixed>>,
     *     articles: array<int, array<string, mixed>>,
     *     routes: array<string, string>
     * }
     */
    public function publicContext(?string $message = null): array
    {
        $products = $this->contextProducts($message)
            ->map(fn (Product $product): array => $this->productContext($product))
            ->values()
            ->all();

        $articles = Article::query()
            ->published()
            ->select(['title', 'slug', 'category', 'excerpt', 'published_at'])
            ->latest('published_at')
            ->limit((int) config('showroom_ai.max_context_articles', 5))
            ->get()
            ->map(fn (Article $article): array => [
                'title' => $article->title,
                'category' => $article->categoryLabel(),
                'excerpt' => $this->compactText($article->excerpt),
                'url' => route('articles.show', $article->slug, false),
            ])
            ->values()
            ->all();

        return [
            'products' => $products,
            'articles' => $articles,
            'routes' => [
                'catalog' => route('products.index', [], false),
                'accessories' => route('accessories.index', [], false),
                'booking_consult' => route('appointments.create', ['type' => 'consult'], false),
                'booking_test_drive' => route('appointments.create', ['type' => 'test_drive'], false),
                'quote' => route('appointments.create', ['type' => 'quote'], false),
                'articles' => route('articles.index', [], false),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function buildPrompt(string $message, array $context): string
    {
        $encodedContext = json_encode(
            $context,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        return <<<PROMPT
BMW showroom gồm ô tô BMW, BMW Motorrad, phụ kiện, ưu đãi và dịch vụ showroom.

Dữ liệu public của showroom BMW:
{$encodedContext}

Nguyên tắc trả lời:
- Chỉ dùng dữ liệu public ở trên.
- Nếu thiếu thông tin, nói rõ chưa có dữ liệu.
- Không hỏi hoặc ghi nhận số điện thoại, email, địa chỉ hay thông tin riêng tư trong chat.
- Không tự tạo booking hoặc đơn hàng; chỉ dẫn tới link/form phù hợp.
- Không bịa giá hoặc ưu đãi.

Câu hỏi của khách hàng:
{$message}
PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    private function productContext(Product $product): array
    {
        $isAccessory = $product->isAccessory();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'type' => $product->type instanceof VehicleType ? $product->type->label() : (string) $product->type,
            'product_line' => $this->productLine($product),
            'category' => $product->category?->name,
            'segment' => $product->category?->name,
            'price' => $this->money($product->price),
            'deposit' => $this->money($product->deposit_amount),
            'description' => $this->compactText($product->description),
            'specs' => $this->specContext($product),
            'search_aliases' => $this->productAliases($product),
            'url' => route('products.show', $product->slug, false),
            'compare_url' => $product->canCompare() ? route('products.compare', ['ids' => $product->id], false) : null,
            'booking_url' => $product->canTestDrive()
                ? route('appointments.create', ['type' => 'test_drive', 'product_id' => $product->id], false)
                : null,
            'quote_url' => $product->isVehicle()
                ? route('appointments.create', ['type' => 'quote', 'product_id' => $product->id], false)
                : null,
            'accessory_order_url' => $isAccessory
                ? route('accessory-orders.create', $product->slug, false)
                : null,
        ];
    }

    /**
     * @return Collection<int, Product>
     */
    private function contextProducts(?string $message): Collection
    {
        $limit = max(30, (int) config('showroom_ai.max_context_products', 30));
        $normalizedMessage = $this->normalizeSearchText((string) $message);

        return Product::query()
            ->active()
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
            ->get()
            ->sortByDesc(fn (Product $product): int => $this->productMatchScore($product, $normalizedMessage))
            ->take($limit)
            ->values();
    }

    private function productLine(Product $product): string
    {
        if ($product->type === VehicleType::MOTORBIKE) {
            return 'BMW Motorrad';
        }

        if ($product->isAccessory()) {
            return 'BMW phụ kiện chính hãng';
        }

        return 'BMW ô tô';
    }

    /**
     * @return array<string, string>
     */
    private function specContext(Product $product): array
    {
        return collect($product->translated_specs)
            ->take(6)
            ->map(fn (mixed $value): string => is_array($value) ? implode(', ', $value) : (string) $value)
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function productAliases(Product $product): array
    {
        $values = [
            $product->name,
            $product->slug,
            $product->category?->name,
            $this->productLine($product),
        ];

        if (Str::startsWith(Str::lower($product->name), 'bmw ')) {
            $values[] = Str::after($product->name, 'BMW ');
        }

        $tokenValues = collect([$product->name, $product->slug])
            ->filter()
            ->flatMap(fn (string $value): array => preg_split('/[\s\-\/]+/', Str::ascii(Str::lower($value))) ?: [])
            ->map(fn (string $value): string => $this->normalizeSearchText($value))
            ->filter(fn (string $value): bool => $this->isUsefulProductAliasToken($value))
            ->all();

        return collect(array_merge($values, $tokenValues))
            ->filter()
            ->map(fn (string $value): string => $this->normalizeSearchText($value))
            ->filter(fn (string $value): bool => strlen($value) >= 4 || $this->isUsefulProductAliasToken($value))
            ->unique()
            ->values()
            ->all();
    }

    private function isUsefulProductAliasToken(string $value): bool
    {
        if ($value === 'bmw') {
            return false;
        }

        return strlen($value) >= 2 && preg_match('/\d/', $value) === 1;
    }

    private function productMatchScore(Product $product, string $normalizedMessage): int
    {
        if ($normalizedMessage === '') {
            return 0;
        }

        return collect($this->productAliases($product))
            ->contains(fn (string $alias): bool => Str::contains($normalizedMessage, $alias))
            ? 100
            : 0;
    }

    /**
     * @param  array<string, mixed>  $product
     */
    private function contextProductMatches(array $product, string $normalizedMessage): bool
    {
        if ($normalizedMessage === '') {
            return false;
        }

        return collect($product['search_aliases'] ?? [])
            ->filter(fn (mixed $alias): bool => is_string($alias) && strlen($alias) >= 4)
            ->contains(fn (string $alias): bool => Str::contains($normalizedMessage, $alias));
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<int, array{label: string, url: string}>
     */
    private function suggestionsFor(string $message, array $context): array
    {
        $message = Str::lower($message);
        $normalizedMessage = $this->normalizeSearchText($message);
        $products = collect($context['products']);
        $suggestions = [];

        $matchedProducts = $products
            ->filter(fn (array $product): bool => $this->contextProductMatches($product, $normalizedMessage))
            ->values();
        $matchedProduct = $matchedProducts->first();

        if ($matchedProduct) {
            $suggestions[] = [
                'label' => 'Xem '.$matchedProduct['name'],
                'url' => $matchedProduct['url'],
            ];
        }

        $accessory = $products->first(fn (array $product): bool => filled($product['accessory_order_url']));
        if ($accessory && Str::contains($message, ['phụ kiện', 'phu kien', 'thảm', 'tham', 'đặt hàng'])) {
            $suggestions[] = [
                'label' => 'Đặt phụ kiện',
                'url' => $accessory['accessory_order_url'],
            ];
        }

        $vehicles = $products->filter(fn (array $product): bool => filled($product['compare_url']))->values();
        if ($vehicles->count() >= 2 && Str::contains($message, ['so sánh', 'so sanh', 'khác gì', 'khac gi'])) {
            $compareVehicles = $matchedProducts
                ->filter(fn (array $product): bool => filled($product['compare_url']))
                ->whenEmpty(fn () => $vehicles)
                ->take(2);

            $suggestions[] = [
                'label' => 'Mở so sánh xe',
                'url' => route('products.compare', [
                    'ids' => $compareVehicles->pluck('id')->implode(','),
                ], false),
            ];
        }

        if (Str::contains($message, ['motorrad', 'xe máy', 'xe may', 's1000', 's 1000'])) {
            $suggestions[] = [
                'label' => 'Xem BMW Motorrad',
                'url' => route('products.index', ['type' => VehicleType::MOTORBIKE->value], false),
            ];
        }

        $suggestions[] = ['label' => 'Xem danh mục xe', 'url' => route('products.index', [], false)];
        $suggestions[] = ['label' => 'Đặt lịch tư vấn', 'url' => route('appointments.create', ['type' => 'consult'], false)];
        $suggestions[] = ['label' => 'Đọc ưu đãi & tin tức', 'url' => route('articles.index', [], false)];

        return collect($suggestions)
            ->unique(fn (array $suggestion): string => $suggestion['label'].'|'.$suggestion['url'])
            ->take(4)
            ->values()
            ->all();
    }

    /**
     * @return array{answer: string, suggestions: array<int, array{label: string, url: string}>, status: string}
     */
    private function fallbackResponse(array $suggestions, string $reason): array
    {
        return [
            'answer' => $this->fallbackMessage(),
            'suggestions' => $suggestions,
            'status' => 'fallback',
            'reason' => $reason,
        ];
    }

    private function configurationFailureReason(string $provider): ?string
    {
        if (! (bool) config('showroom_ai.enabled', true)) {
            return 'assistant_disabled';
        }

        if ($provider === '') {
            return 'provider_missing';
        }

        if ($provider === 'gemini') {
            return filled(config('ai.providers.gemini.key')) ? null : 'gemini_key_missing';
        }

        return filled(Arr::get(config("ai.providers.{$provider}", []), 'key')) ? null : 'provider_key_missing';
    }

    private function configuredProvider(): string
    {
        return trim((string) config('showroom_ai.provider', 'gemini'));
    }

    private function configuredModel(): ?string
    {
        $model = config('showroom_ai.model');

        return filled($model) ? trim((string) $model) : null;
    }

    private function fallbackMessage(): string
    {
        return (string) config('showroom_ai.fallback_message');
    }

    private function normalizeSearchText(string $value): string
    {
        return preg_replace(
            '/[^a-z0-9]+/',
            '',
            Str::ascii(Str::lower($value))
        ) ?? '';
    }

    private function compactText(?string $text): ?string
    {
        return filled($text)
            ? Str::limit(preg_replace('/\s+/', ' ', strip_tags((string) $text)) ?? '', 220)
            : null;
    }

    private function money(?int $amount): ?string
    {
        return $amount !== null && $amount > 0
            ? number_format($amount, 0, ',', '.').' VND'
            : null;
    }
}
