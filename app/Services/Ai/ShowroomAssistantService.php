<?php

namespace App\Services\Ai;

use App\Ai\Agents\ShowroomAssistant;
use App\Enums\VehicleType;
use App\Models\Article;
use App\Models\Product;
use Illuminate\Support\Arr;
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
        $context = $this->publicContext();
        $suggestions = $this->suggestionsFor($message, $context);

        if (! $this->isConfigured()) {
            return $this->fallbackResponse($suggestions);
        }

        try {
            $response = ShowroomAssistant::make()->prompt(
                prompt: $this->buildPrompt($message, $context),
                provider: config('showroom_ai.provider', 'gemini'),
                model: filled(config('showroom_ai.model')) ? (string) config('showroom_ai.model') : null,
                timeout: 20,
            );

            return [
                'answer' => trim($response->text) ?: $this->fallbackMessage(),
                'suggestions' => $suggestions,
                'status' => 'ok',
            ];
        } catch (Throwable $exception) {
            Log::warning('Showroom AI assistant request failed.', [
                'exception' => $exception::class,
            ]);

            return $this->fallbackResponse($suggestions);
        }
    }

    /**
     * @return array{
     *     products: array<int, array<string, mixed>>,
     *     articles: array<int, array<string, mixed>>,
     *     routes: array<string, string>
     * }
     */
    public function publicContext(): array
    {
        $products = Product::query()
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
            ->latest()
            ->limit((int) config('showroom_ai.max_context_products', 8))
            ->get()
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
            'type' => $product->type instanceof VehicleType ? $product->type->label() : (string) $product->type,
            'category' => $product->category?->name,
            'price' => $this->money($product->price),
            'deposit' => $this->money($product->deposit_amount),
            'description' => $this->compactText($product->description),
            'specs' => $this->specContext($product),
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
     * @param  array<string, mixed>  $context
     * @return array<int, array{label: string, url: string}>
     */
    private function suggestionsFor(string $message, array $context): array
    {
        $message = Str::lower($message);
        $products = collect($context['products']);
        $suggestions = [];

        $matchedProduct = $products->first(function (array $product) use ($message): bool {
            return Str::contains($message, Str::lower((string) $product['name']))
                || collect(explode(' ', Str::lower((string) $product['name'])))
                    ->filter(fn (string $part): bool => strlen($part) >= 3)
                    ->contains(fn (string $part): bool => Str::contains($message, $part));
        });

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
            $suggestions[] = [
                'label' => 'Mở so sánh xe',
                'url' => route('products.compare', [
                    'ids' => $vehicles->take(2)->pluck('id')->implode(','),
                ], false),
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
    private function fallbackResponse(array $suggestions): array
    {
        return [
            'answer' => $this->fallbackMessage(),
            'suggestions' => $suggestions,
            'status' => 'fallback',
        ];
    }

    private function isConfigured(): bool
    {
        if (! (bool) config('showroom_ai.enabled', true)) {
            return false;
        }

        $provider = (string) config('showroom_ai.provider', 'gemini');

        return filled(Arr::get(config("ai.providers.{$provider}", []), 'key'));
    }

    private function fallbackMessage(): string
    {
        return (string) config('showroom_ai.fallback_message');
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
