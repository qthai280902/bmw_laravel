<?php

namespace App\Services\Ai;

use App\Ai\Agents\ShowroomAssistant;
use App\Enums\VehicleType;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\Article;
use App\Models\Product;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\AiManager;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\MessageRole;
use Laravel\Ai\Responses\AgentResponse;
use Throwable;

class ShowroomAssistantService
{
    public function __construct(
        private GeminiKeyPool $geminiKeyPool,
        private ?ShowroomCatalogMatcher $matcher = null,
        private ?ShowroomKnowledgeBaseService $knowledgeBase = null,
    ) {}

    /**
     * @param  array{visitor_id?: string|null, page_url?: string|null, product_slug?: string|null, product_name?: string|null}  $options
     * @return array{answer: string, suggestions: array<int, array{label: string, url: string}>, status: string, reason?: string, current_product?: array<string, mixed>|null}
     */
    public function ask(string $message, array $options = []): array
    {
        $pageContext = $this->resolvePageContext($options);
        $context = $this->publicContext($message, $pageContext);
        $suggestions = $this->suggestionsFor($message, $context);
        $provider = $this->configuredProvider();
        $model = $this->configuredModel();
        $prompt = $this->buildPrompt($message, $context);
        $conversationMessages = $this->recentConversationMessages($options['visitor_id'] ?? null);
        $pageContextSummary = $this->buildPageContextSummary($pageContext);
        $trackerProduct = $pageContext['tracker_product'] ?? null;

        if ($configurationReason = $this->configurationFailureReason($provider)) {
            Log::info('Showroom AI assistant skipped.', [
                'reason' => $configurationReason,
                'provider' => $provider,
            ]);

            return $this->withTrackerProduct($this->fallbackResponse($suggestions, 'configuration'), $trackerProduct);
        }

        if ($provider === 'gemini') {
            $payload = $this->askGeminiWithRotation(
                prompt: $prompt,
                suggestions: $suggestions,
                model: $model,
                conversationMessages: $conversationMessages,
                pageContextSummary: $pageContextSummary,
                context: $context,
                message: $message,
            );

            return $this->withTrackerProduct($payload, $trackerProduct);
        }

        try {
            $response = $this->promptAssistant(
                prompt: $prompt,
                provider: $provider,
                model: $model,
                conversationMessages: $conversationMessages,
                pageContextSummary: $pageContextSummary,
            );

            return $this->withTrackerProduct($this->successfulResponse($response, $suggestions, $context, $message), $trackerProduct);
        } catch (Throwable $exception) {
            Log::warning('Showroom AI assistant request failed.', [
                'reason' => 'provider_error',
                'exception' => $exception::class,
                'provider' => $provider,
            ]);

            return $this->withTrackerProduct($this->fallbackResponse($suggestions, 'provider_error'), $trackerProduct);
        }
    }

    public function resetConversation(?string $visitorId): bool
    {
        $normalizedVisitorId = $this->normalizeVisitorId($visitorId);

        if ($normalizedVisitorId === null) {
            return false;
        }

        $latestMessageId = (int) (AiChatMessage::query()
            ->whereHas('session', fn ($query) => $query->where('visitor_id', $normalizedVisitorId))
            ->max('id') ?? 0);

        Cache::put(
            $this->resetCacheKey($normalizedVisitorId),
            [
                'after_id' => $latestMessageId,
                'reset_at' => now()->toIso8601String(),
            ],
            now()->addDays(7)
        );

        return true;
    }

    /**
     * @return array<int, Message>
     */
    public function recentConversationMessages(?string $visitorId): array
    {
        $normalizedVisitorId = $this->normalizeVisitorId($visitorId);

        if ($normalizedVisitorId === null) {
            return [];
        }

        $session = AiChatSession::query()
            ->where('visitor_id', $normalizedVisitorId)
            ->latest('last_seen_at')
            ->first();

        if (! $session) {
            return [];
        }

        $maxMessages = max(2, (int) config('showroom_ai.max_history_messages', 14));
        $ttlHours = max(1, (int) config('showroom_ai.history_ttl_hours', 6));
        $resetMarker = Cache::get($this->resetCacheKey($normalizedVisitorId));
        $afterId = is_array($resetMarker) ? (int) ($resetMarker['after_id'] ?? 0) : 0;

        $records = AiChatMessage::query()
            ->where('ai_chat_session_id', $session->id)
            ->when($afterId > 0, fn ($query) => $query->where('id', '>', $afterId))
            ->where('created_at', '>=', now()->subHours($ttlHours))
            ->orderByDesc('id')
            ->limit($maxMessages * 2)
            ->get()
            ->reverse()
            ->values();

        $pairs = [];
        $pendingUserMessage = null;

        foreach ($records as $record) {
            $content = $this->redactHistoryText((string) $record->content);
            if ($content === '') {
                continue;
            }

            if ($record->role === 'user') {
                $pendingUserMessage = $content;

                continue;
            }

            if ($record->role === 'assistant' && $record->response_reason === 'ok' && $pendingUserMessage !== null) {
                $pairs[] = [
                    new Message(MessageRole::User, $pendingUserMessage),
                    new Message(MessageRole::Assistant, $content),
                ];
                $pendingUserMessage = null;
            } else {
                $pendingUserMessage = null;
            }
        }

        $maxPairs = max(1, intdiv($maxMessages, 2));
        $selectedPairs = array_slice($pairs, -$maxPairs);
        $messages = [];

        foreach ($selectedPairs as $pair) {
            $messages[] = $pair[0];
            $messages[] = $pair[1];
        }

        return $messages;
    }

    /**
     * @param  array<int, array{label: string, url: string}>  $suggestions
     * @param  array<int, Message>  $conversationMessages
     * @param  array<string, mixed>  $context
     * @return array{answer: string, suggestions: array<int, array{label: string, url: string}>, status: string, reason?: string}
     */
    private function askGeminiWithRotation(
        string $prompt,
        array $suggestions,
        ?string $model,
        array $conversationMessages = [],
        ?string $pageContextSummary = null,
        array $context = []
    ): array {
        $candidates = $this->geminiKeyPool->candidateSequence();

        if ($candidates === []) {
            Log::info('Showroom AI assistant skipped.', [
                'reason' => $this->geminiKeyPool->hasKeys() ? 'all_gemini_keys_cooling_down' : 'gemini_key_missing',
                'provider' => 'gemini',
            ]);

            return $this->geminiKeyPool->hasKeys()
                ? $this->fallbackResponse($suggestions, 'rate_limited', $this->rateLimitFallbackMessage())
                : $this->fallbackResponse($suggestions, 'configuration');
        }

        $attempts = 0;
        $rateLimitedAttempts = 0;

        foreach ($candidates as $candidate) {
            $attempts++;

            try {
                $response = $this->promptGeminiWithCandidate(
                    candidate: $candidate,
                    prompt: $prompt,
                    model: $model,
                    conversationMessages: $conversationMessages,
                    pageContextSummary: $pageContextSummary,
                );

                return $this->successfulResponse($response, $suggestions, $context);
            } catch (RateLimitedException $exception) {
                $rateLimitedAttempts++;
                $this->geminiKeyPool->markRateLimited($candidate);

                Log::warning('Showroom AI assistant Gemini key rate limited.', [
                    'reason' => 'rate_limited',
                    'provider' => 'gemini',
                    'model' => $model,
                    'key_index' => $candidate['index'],
                    'exception' => $exception::class,
                    'attempt' => $attempts,
                    'configured_keys' => count($this->geminiKeyPool->keys()),
                ]);
            } catch (Throwable $exception) {
                if ($this->isTransientProviderException($exception)) {
                    Log::warning('Showroom AI assistant Gemini key attempt failed.', [
                        'reason' => 'provider_error',
                        'provider' => 'gemini',
                        'model' => $model,
                        'key_index' => $candidate['index'],
                        'exception' => $exception::class,
                        'attempt' => $attempts,
                        'configured_keys' => count($this->geminiKeyPool->keys()),
                    ]);

                    continue;
                }

                Log::warning('Showroom AI assistant request failed.', [
                    'reason' => 'provider_error',
                    'exception' => $exception::class,
                    'provider' => 'gemini',
                    'key_index' => $candidate['index'],
                    'attempt' => $attempts,
                ]);

                return $this->fallbackResponse($suggestions, 'provider_error');
            }
        }

        if ($attempts > 0 && $rateLimitedAttempts === $attempts) {
            Log::warning('Showroom AI assistant exhausted Gemini keys.', [
                'reason' => 'rate_limited',
                'provider' => 'gemini',
                'attempts' => $attempts,
                'configured_keys' => count($this->geminiKeyPool->keys()),
            ]);

            return $this->fallbackResponse($suggestions, 'rate_limited', $this->rateLimitFallbackMessage());
        }

        Log::warning('Showroom AI assistant exhausted Gemini key attempts.', [
            'reason' => 'provider_error',
            'provider' => 'gemini',
            'attempts' => $attempts,
            'configured_keys' => count($this->geminiKeyPool->keys()),
        ]);

        return $this->fallbackResponse($suggestions, 'provider_error');
    }

    /**
     * @param  array{index: int, key: string, fingerprint: string}  $candidate
     * @param  array<int, Message>  $conversationMessages
     */
    private function promptGeminiWithCandidate(
        array $candidate,
        string $prompt,
        ?string $model,
        array $conversationMessages = [],
        ?string $pageContextSummary = null
    ): AgentResponse {
        $providerName = $this->geminiKeyPool->providerNameFor($candidate);
        $originalConfig = config("ai.providers.{$providerName}");
        $baseConfig = config('ai.providers.gemini', []);

        Config::set("ai.providers.{$providerName}", array_merge($baseConfig, [
            'driver' => 'gemini',
            'key' => $candidate['key'],
        ]));

        app(AiManager::class)->forgetInstance($providerName);

        try {
            return $this->promptAssistant(
                prompt: $prompt,
                provider: $providerName,
                model: $model,
                conversationMessages: $conversationMessages,
                pageContextSummary: $pageContextSummary,
            );
        } finally {
            app(AiManager::class)->forgetInstance($providerName);
            Config::set("ai.providers.{$providerName}", $originalConfig);
        }
    }

    /**
     * @param  array<int, Message>  $conversationMessages
     */
    private function promptAssistant(
        string $prompt,
        string $provider,
        ?string $model,
        array $conversationMessages = [],
        ?string $pageContextSummary = null
    ): AgentResponse {
        return ShowroomAssistant::make(
            conversationMessages: $conversationMessages,
            pageContextSummary: $pageContextSummary,
        )->prompt(
            prompt: $prompt,
            provider: $provider,
            model: $model,
            timeout: max(10, (int) config('showroom_ai.timeout_seconds', 30)),
        );
    }

    /**
     * @param  array<int, array{label: string, url: string}>  $suggestions
     * @param  array<string, mixed>  $context
     * @return array{answer: string, suggestions: array<int, array{label: string, url: string}>, status: string}
     */
    private function successfulResponse(AgentResponse $response, array $suggestions, array $context = []): array
    {
        $answer = trim($response->text);

        if ($answer !== '') {
            $answer = $this->sanitizeAndRepairMarkdownLinks($answer, $context);
        }

        return [
            'answer' => $answer !== '' ? $answer : $this->fallbackMessage(),
            'suggestions' => $suggestions,
            'status' => 'ok',
        ];
    }

    private function isTransientProviderException(Throwable $exception): bool
    {
        if ($exception instanceof ProviderOverloadedException || $exception instanceof ConnectionException) {
            return true;
        }

        return $exception instanceof RequestException
            && $exception->response !== null
            && $exception->response->status() >= 500;
    }

    /**
     * @param  array<string, mixed>  $pageContext
     * @return array{
     *     products: array<int, array<string, mixed>>,
     *     articles: array<int, array<string, mixed>>,
     *     routes: array<string, string>,
     *     current_product?: array<string, mixed>,
     *     current_article?: array<string, mixed>
     * }
     */
    public function publicContext(?string $message = null, array $pageContext = []): array
    {
        /** @var Product|null $currentProductModel */
        $currentProductModel = $pageContext['product_model'] ?? null;

        $products = $this->contextProducts($message, $currentProductModel)
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
                'excerpt' => $this->matcher()->compactText($article->excerpt),
                'url' => route('articles.show', $article->slug, false),
            ])
            ->values()
            ->all();

        $context = [
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

        if ($currentProductModel instanceof Product) {
            $context['current_product'] = $this->productContext($currentProductModel);
        }

        if (isset($pageContext['current_article']) && is_array($pageContext['current_article'])) {
            $context['current_article'] = $pageContext['current_article'];
        }

        return $context;
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

        $pageContextBlock = '';

        if (isset($context['current_product']) && is_array($context['current_product'])) {
            $currentProduct = $context['current_product'];
            $priceText = $currentProduct['price'] ?? 'Liên hệ showroom';
            $pageContextBlock = <<<CONTEXT

Ngữ cảnh trang hiện tại:
- Khách hàng đang xem sản phẩm public: {$currentProduct['name']} (đường dẫn: {$currentProduct['url']}, dòng: {$currentProduct['product_line']}, giá: {$priceText}).
- Nếu Quý khách dùng đại từ như "xe này", "mẫu này", "chiếc này", "sản phẩm này" mà không nêu tên cụ thể, mặc định là đang hỏi về {$currentProduct['name']}.

CONTEXT;
        } elseif (isset($context['current_article']) && is_array($context['current_article'])) {
            $currentArticle = $context['current_article'];
            $pageContextBlock = <<<CONTEXT

Ngữ cảnh trang hiện tại:
- Khách hàng đang đọc bài viết public: {$currentArticle['title']} ({$currentArticle['url']}).

CONTEXT;
        }

        $ragChunks = $this->knowledgeBase()->retrieveRelevantChunks($message, 3, false);
        $ragBlock = '';

        if ($ragChunks !== []) {
            $encodedRag = json_encode(
                $ragChunks,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
            );

            $ragBlock = <<<RAG

[TÀI LIỆU TRI THỨC RAG ĐƯỢC XÁC THỰC]
{$encodedRag}

RAG;
        }

        return <<<PROMPT
BMW showroom gồm ô tô BMW, BMW Motorrad, phụ kiện, ưu đãi và dịch vụ showroom.
Bạn là Chuyên viên Tư vấn Trực tuyến Cao cấp của BMW Showroom Vietnam.
Phong cách giao tiếp: Tinh tế, súc tích, chuẩn chuyên gia; gọi khách hàng là "Quý khách", không lặp "BMW Showroom" quá 2 lần/câu trả lời và tránh mở đầu rập khuôn kiểu "Kính thưa Quý khách".

Dữ liệu public của showroom BMW:
{$encodedContext}
{$pageContextBlock}{$ragBlock}
Nguyên tắc trả lời:
- CHỈ sử dụng dữ liệu public ở trên, tài liệu trong mục [TÀI LIỆU TRI THỨC RAG ĐƯỢC XÁC THỰC] và kết quả từ các công cụ (`search_catalog`, `compare_vehicles`, `search_articles_and_offers`, `search_knowledge_base`).
- Khi cần tra cứu chi tiết theo ngân sách, phân khúc (SUV/SAV, xe điện, xe máy/mô tô PKL), so sánh 2–3 mẫu xe, tìm ưu đãi/bài viết hoặc chính sách bảo hành/bảo dưỡng/lái thử/trả góp/thu cũ đổi mới, chủ động gọi công cụ tương ứng.
- Tuyệt đối KHÔNG suy diễn hoặc bịa đặt giá bán, thông số kỹ thuật hay chính sách nếu không có trong RAG hoặc dữ liệu public trên. Nếu thiếu thông tin, nói rõ chưa có thông tin và mời Quý khách đặt lịch tư vấn qua [Tư vấn trực tiếp]({$context['routes']['booking_consult']}).
- Tuyệt đối KHÔNG xuất markdown link rỗng `[]()` hoặc `(#)`, và KHÔNG viết câu dẫn link thừa kiểu "Quý khách có thể tìm hiểu thêm tại đây:" ở cuối câu trả lời vì Action Cards đã hiển thị sẵn bên dưới.
- Khi Quý khách quan tâm mua xe, nhận báo giá chi tiết, đăng ký lái thử hoặc bảo dưỡng dịch vụ: Luôn sử dụng đúng liên kết chính thức:
  * Đăng ký lái thử xe: [Đăng ký lái thử]({$context['routes']['booking_test_drive']})
  * Nhận báo giá & tư vấn: [Yêu cầu báo giá]({$context['routes']['quote']}) hoặc [Tư vấn trực tiếp]({$context['routes']['booking_consult']})
  * Phụ kiện chính hãng: [Xem phụ kiện]({$context['routes']['accessories']})
- Không hỏi hoặc ghi nhận số điện thoại, email, địa chỉ hay thông tin riêng tư trong đoạn chat; hướng dẫn Quý khách điền vào form bảo mật tương ứng.
- Không tự tạo booking hoặc đơn hàng; chỉ dẫn tới link/form phù hợp.
- Trả lời súc tích, chuyên nghiệp, làm nổi bật cảm giác lái thuần chất và đẳng cấp công nghệ BMW.

Câu hỏi của khách hàng:
{$message}
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{
     *     product_model: Product|null,
     *     current_article: array<string, mixed>|null,
     *     tracker_product: array<string, mixed>|null
     * }
     */
    private function resolvePageContext(array $options): array
    {
        $pagePath = $this->safePath($options['page_url'] ?? null);
        $productSlug = trim((string) ($options['product_slug'] ?? ''));

        if ($productSlug === '' && $pagePath && preg_match('#^/catalog/([a-z0-9-]+)$#i', $pagePath, $matches) === 1) {
            $productSlug = $matches[1];
        }

        $productModel = null;
        $trackerProduct = null;

        if ($productSlug !== '' && preg_match('/^[a-z0-9-]+$/i', $productSlug) === 1) {
            $productModel = Product::query()
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
                ->where('slug', $productSlug)
                ->first();

            if ($productModel) {
                $trackerProduct = [
                    'id' => $productModel->id,
                    'name' => $productModel->name,
                    'slug' => $productModel->slug,
                    'raw_type' => $productModel->type instanceof VehicleType ? $productModel->type->value : (string) $productModel->type,
                    'is_accessory' => $productModel->isAccessory(),
                    'category' => $productModel->category?->name,
                ];
            }
        }

        $currentArticle = null;
        if ($pagePath && preg_match('#^/tim-hieu-them/([a-z0-9-]+)$#i', $pagePath, $matches) === 1) {
            $article = Article::query()
                ->published()
                ->select(['title', 'slug', 'category', 'excerpt', 'published_at'])
                ->where('slug', $matches[1])
                ->first();

            if ($article) {
                $currentArticle = [
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'category' => $article->categoryLabel(),
                    'excerpt' => $this->matcher()->compactText($article->excerpt),
                    'url' => route('articles.show', $article->slug, false),
                ];
            }
        }

        return [
            'product_model' => $productModel,
            'current_article' => $currentArticle,
            'tracker_product' => $trackerProduct,
        ];
    }

    /**
     * @param  array<string, mixed>  $pageContext
     */
    private function buildPageContextSummary(array $pageContext): ?string
    {
        /** @var Product|null $product */
        $product = $pageContext['product_model'] ?? null;

        if ($product instanceof Product) {
            $price = $this->matcher()->money($product->price) ?? 'Liên hệ showroom';

            return "Quý khách đang đứng tại trang chi tiết sản phẩm {$product->name} (slug: {$product->slug}, dòng: {$this->matcher()->productLine($product)}, giá: {$price}).";
        }

        if (isset($pageContext['current_article']) && is_array($pageContext['current_article'])) {
            $article = $pageContext['current_article'];

            return "Quý khách đang đọc bài viết {$article['title']} ({$article['url']}).";
        }

        return null;
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
            'raw_type' => $product->type instanceof VehicleType ? $product->type->value : (string) $product->type,
            'is_accessory' => $isAccessory,
            'product_line' => $this->matcher()->productLine($product),
            'category' => $product->category?->name,
            'segment' => $product->category?->name,
            'price' => $this->matcher()->money($product->price),
            'deposit' => $this->matcher()->money($product->deposit_amount),
            'description' => $this->matcher()->compactText($product->description),
            'specs' => $this->matcher()->specContext($product, 6),
            'search_aliases' => $this->matcher()->productAliases($product),
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
    private function contextProducts(?string $message, ?Product $currentProduct = null): Collection
    {
        $limit = max(30, (int) config('showroom_ai.max_context_products', 30));
        $normalizedMessage = $this->matcher()->normalizeSearchText((string) $message);

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
            ->sortByDesc(function (Product $product) use ($normalizedMessage, $currentProduct): int {
                $score = $this->matcher()->scoreMessageRelevance($product, $normalizedMessage);

                if ($currentProduct instanceof Product && $product->id === $currentProduct->id) {
                    $score += 1000;
                }

                return $score;
            })
            ->take($limit)
            ->values();
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
        $normalizedMessage = $this->matcher()->normalizeSearchText($message);
        $products = collect($context['products']);
        $suggestions = [];
        $currentProduct = isset($context['current_product']) && is_array($context['current_product'])
            ? $context['current_product']
            : null;

        if ($currentProduct) {
            if (($currentProduct['is_accessory'] ?? false) === true) {
                if (filled($currentProduct['accessory_order_url'] ?? null)) {
                    $suggestions[] = [
                        'label' => 'Đặt phụ kiện '.$currentProduct['name'],
                        'url' => $currentProduct['accessory_order_url'],
                    ];
                }

                $suggestions[] = [
                    'label' => 'Xem phụ kiện chính hãng',
                    'url' => route('accessories.index', [], false),
                ];
            } else {
                if (filled($currentProduct['booking_url'] ?? null)) {
                    $suggestions[] = [
                        'label' => 'Đặt lịch lái thử',
                        'url' => $currentProduct['booking_url'],
                    ];
                }

                if (filled($currentProduct['quote_url'] ?? null)) {
                    $suggestions[] = [
                        'label' => 'Nhận báo giá '.$currentProduct['name'],
                        'url' => $currentProduct['quote_url'],
                    ];
                }

                $suggestions[] = [
                    'label' => 'Xem '.$currentProduct['name'],
                    'url' => $currentProduct['url'],
                ];
            }
        }

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

        if (Str::contains($message, ['motorrad', 'xe máy', 'xe may', 'mô tô', 'mo to', 'pkl', 's1000', 's 1000'])) {
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
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>|null  $trackerProduct
     * @return array<string, mixed>
     */
    private function withTrackerProduct(array $payload, ?array $trackerProduct): array
    {
        if ($trackerProduct !== null) {
            $payload['current_product'] = $trackerProduct;
        }

        return $payload;
    }

    /**
     * @param  array<int, array{label: string, url: string}>  $suggestions
     * @return array{answer: string, suggestions: array<int, array{label: string, url: string}>, status: string, reason: string}
     */
    private function fallbackResponse(array $suggestions, string $reason, ?string $answer = null): array
    {
        return [
            'answer' => $answer ?? $this->fallbackMessage(),
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
            return $this->geminiKeyPool->hasKeys() ? null : 'gemini_key_missing';
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

    private function rateLimitFallbackMessage(): string
    {
        return (string) config('showroom_ai.rate_limit_fallback_message', $this->fallbackMessage());
    }

    /**
     * Repair empty, hash, legacy, or hallucinated markdown links in AI responses.
     *
     * @param  array<string, mixed>  $context
     */
    public function sanitizeAndRepairMarkdownLinks(string $answer, array $context = []): string
    {
        if (! str_contains($answer, '[') || ! str_contains($answer, '](')) {
            return $answer;
        }

        $activeProducts = Product::query()
            ->active()
            ->with('category')
            ->select(['id', 'category_id', 'name', 'slug', 'type'])
            ->get();

        $publishedArticles = Article::query()
            ->published()
            ->select(['id', 'title', 'slug', 'category', 'excerpt'])
            ->get();

        $repaired = preg_replace_callback(
            '/\[([^\[\]\n]{1,120})\]\(\s*((?:[^()\n]|\([^()\n]*\))*)\s*\)/u',
            function (array $matches) use ($context, $activeProducts, $publishedArticles): string {
                $rawLabel = trim($matches[1]);
                $rawHref = trim($matches[2]);
                $plainLabel = trim(preg_replace('/\*{1,2}/', '', $rawLabel) ?? $rawLabel);

                if ($plainLabel === '') {
                    return '';
                }

                $validHref = $this->resolveMarkdownHref(
                    rawHref: $rawHref,
                    label: $plainLabel,
                    context: $context,
                    activeProducts: $activeProducts,
                    publishedArticles: $publishedArticles,
                );

                if ($validHref !== null) {
                    return "[{$plainLabel}]({$validHref})";
                }

                return "**{$plainLabel}**";
            },
            $answer
        );

        return is_string($repaired) ? trim($repaired) : $answer;
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  Collection<int, Product>  $activeProducts
     * @param  Collection<int, Article>  $publishedArticles
     */
    private function resolveMarkdownHref(
        string $rawHref,
        string $label,
        array $context,
        Collection $activeProducts,
        Collection $publishedArticles
    ): ?string {
        $candidate = $this->normalizeInternalHrefCandidate($rawHref);

        if ($candidate !== null && $this->isVerifiedShowroomHref($candidate, $activeProducts, $publishedArticles)) {
            return $candidate;
        }

        return $this->inferHrefFromLabel($label, $context, $activeProducts, $publishedArticles);
    }

    private function normalizeInternalHrefCandidate(string $rawHref): ?string
    {
        $href = trim($rawHref);
        $lowered = Str::lower($href);

        if (
            $href === ''
            || $href === '#'
            || str_starts_with($href, '#')
            || str_starts_with($lowered, 'javascript:')
            || str_starts_with($lowered, 'data:')
            || str_starts_with($lowered, 'vbscript:')
            || str_starts_with($href, '//')
        ) {
            return null;
        }

        if (preg_match('#^https?://#i', $href) === 1) {
            $parsedHref = parse_url($href);
            $parsedApp = parse_url(url('/'));

            if (
                ! is_array($parsedHref)
                || ! is_array($parsedApp)
                || Str::lower((string) ($parsedHref['host'] ?? '')) !== Str::lower((string) ($parsedApp['host'] ?? ''))
            ) {
                return null;
            }

            $path = (string) ($parsedHref['path'] ?? '/');
            $query = isset($parsedHref['query']) && $parsedHref['query'] !== '' ? '?'.$parsedHref['query'] : '';
            $href = $path.$query;
        }

        if (! str_starts_with($href, '/') || str_starts_with($href, '/\\')) {
            return null;
        }

        $path = (string) (parse_url($href, PHP_URL_PATH) ?? '/');
        $query = (string) (parse_url($href, PHP_URL_QUERY) ?? '');

        if ($path === '/dat-lich' || str_starts_with($path, '/dat-lich/')) {
            $path = '/booking'.substr($path, strlen('/dat-lich'));
        } elseif ($path === '/phu-kien' || str_starts_with($path, '/phu-kien/')) {
            $path = '/accessories'.substr($path, strlen('/phu-kien'));
        }

        return $path.($query !== '' ? '?'.$query : '');
    }

    /**
     * @param  Collection<int, Product>  $activeProducts
     * @param  Collection<int, Article>  $publishedArticles
     */
    private function isVerifiedShowroomHref(
        string $href,
        Collection $activeProducts,
        Collection $publishedArticles
    ): bool {
        $path = (string) (parse_url($href, PHP_URL_PATH) ?? '');

        if ($path === '') {
            return false;
        }

        if (preg_match('#^/catalog/([a-z0-9-]+)$#i', $path, $matches) === 1) {
            return $activeProducts->contains(fn (Product $product): bool => Str::lower($product->slug) === Str::lower($matches[1]));
        }

        if (preg_match('#^/tim-hieu-them/([a-z0-9-]+)$#i', $path, $matches) === 1) {
            return $publishedArticles->contains(fn (Article $article): bool => Str::lower($article->slug) === Str::lower($matches[1]));
        }

        if (preg_match('#^/accessories/([a-z0-9-]+)/order$#i', $path, $matches) === 1) {
            return $activeProducts->contains(
                fn (Product $product): bool => $product->isAccessory() && Str::lower($product->slug) === Str::lower($matches[1])
            );
        }

        return in_array($path, [
            '/',
            '/catalog',
            '/compare',
            '/accessories',
            '/tim-hieu-them',
            '/offers',
            '/booking',
            '/contact',
            '/privacy-policy',
        ], true);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  Collection<int, Product>  $activeProducts
     * @param  Collection<int, Article>  $publishedArticles
     */
    private function inferHrefFromLabel(
        string $label,
        array $context,
        Collection $activeProducts,
        Collection $publishedArticles
    ): ?string {
        $normalizedLabel = Str::ascii(Str::lower($label));

        if ($normalizedLabel === '') {
            return null;
        }

        $bestProductEntry = $activeProducts
            ->map(fn (Product $product): array => [
                'product' => $product,
                'score' => $this->matcher()->scoreProductQuery($product, $label, true, false),
            ])
            ->filter(fn (array $entry): bool => $entry['score'] >= 350)
            ->sortByDesc('score')
            ->first();

        if ($bestProductEntry !== null) {
            /** @var Product $matchedProduct */
            $matchedProduct = $bestProductEntry['product'];

            if ($matchedProduct->canTestDrive() && Str::contains($normalizedLabel, ['lai thu', 'test drive'])) {
                return route('appointments.create', ['type' => 'test_drive', 'product_id' => $matchedProduct->id], false);
            }

            if ($matchedProduct->isVehicle() && Str::contains($normalizedLabel, ['bao gia', 'quote', 'lan banh'])) {
                return route('appointments.create', ['type' => 'quote', 'product_id' => $matchedProduct->id], false);
            }

            if (Str::contains($normalizedLabel, ['tu van', 'consult'])) {
                return route('appointments.create', ['type' => 'consult', 'product_id' => $matchedProduct->id], false);
            }

            if ($matchedProduct->isAccessory() && Str::contains($normalizedLabel, ['dat phu kien', 'dat mua', 'order'])) {
                return route('accessory-orders.create', $matchedProduct->slug, false);
            }

            return route('products.show', $matchedProduct->slug, false);
        }

        $bestArticleEntry = $publishedArticles
            ->map(fn (Article $article): array => [
                'article' => $article,
                'score' => $this->matcher()->scoreArticleQuery($article, $label),
            ])
            ->filter(fn (array $entry): bool => $entry['score'] >= 350)
            ->sortByDesc('score')
            ->first();

        if ($bestArticleEntry !== null) {
            /** @var Article $matchedArticle */
            $matchedArticle = $bestArticleEntry['article'];

            return route('articles.show', $matchedArticle->slug, false);
        }

        if (
            isset($context['current_product']['url'])
            && is_string($context['current_product']['url'])
            && Str::contains($normalizedLabel, ['xem chi tiet', 'chi tiet xe', 'san pham nay', 'xe nay', 'chiec nay'])
        ) {
            return $context['current_product']['url'];
        }

        $intentRoutes = [
            ['keywords' => ['lai thu', 'test drive', 'dat lich lai'], 'url' => route('appointments.create', ['type' => 'test_drive'], false)],
            ['keywords' => ['bao gia', 'quote', 'lan banh', 'tra gop'], 'url' => route('appointments.create', ['type' => 'quote'], false)],
            ['keywords' => ['tu van', 'bao duong', 'dat lich', 'consult', 'lien he'], 'url' => route('appointments.create', ['type' => 'consult'], false)],
            ['keywords' => ['phu kien', 'accessory', 'do choi'], 'url' => route('accessories.index', [], false)],
            ['keywords' => ['so sanh', 'compare'], 'url' => route('products.compare', [], false)],
            ['keywords' => ['uu dai', 'khuyen mai', 'exclusive'], 'url' => route('offers.exclusive', [], false)],
            ['keywords' => ['bai viet', 'tin tuc', 'tim hieu'], 'url' => route('articles.index', [], false)],
            ['keywords' => ['motorrad', 'xe may', 'mo to', 'pkl'], 'url' => route('products.index', ['type' => VehicleType::MOTORBIKE->value], false)],
            ['keywords' => ['danh muc', 'tat ca xe', 'san pham'], 'url' => route('products.index', [], false)],
        ];

        foreach ($intentRoutes as $entry) {
            if (Str::contains($normalizedLabel, $entry['keywords'])) {
                return $entry['url'];
            }
        }

        return null;
    }

    private function matcher(): ShowroomCatalogMatcher
    {
        return $this->matcher ??= app(ShowroomCatalogMatcher::class);
    }

    private function knowledgeBase(): ShowroomKnowledgeBaseService
    {
        return $this->knowledgeBase ??= app(ShowroomKnowledgeBaseService::class);
    }

    private function normalizeVisitorId(?string $visitorId): ?string
    {
        $visitorId = trim((string) $visitorId);

        if ($visitorId === '' || strlen($visitorId) > 80) {
            return null;
        }

        return preg_match('/^[A-Za-z0-9._:-]+$/', $visitorId) === 1 ? $visitorId : null;
    }

    private function resetCacheKey(string $visitorId): string
    {
        return 'showroom_ai:reset:'.$visitorId;
    }

    private function redactHistoryText(string $text): string
    {
        $cleaned = preg_replace([
            '/AIza[0-9A-Za-z_\-]{20,}/',
            '/sk-[A-Za-z0-9_\-]{20,}/',
            '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i',
            '/(?:\+?84|0)(?:[\s.-]?\d){8,10}/',
        ], [
            '[api-key hidden]',
            '[api-key hidden]',
            '[email hidden]',
            '[phone hidden]',
        ], $text) ?? $text;

        return Str::limit(trim($cleaned), 1200);
    }

    private function safePath(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '' || strlen($url) > 2048 || Str::contains($url, ["\n", "\r"])) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) ? Str::limit($path, 500, '') : null;
    }
}
