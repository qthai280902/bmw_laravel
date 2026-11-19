<?php

namespace App\Services\Ai;

use App\Ai\Agents\ShowroomAssistant;
use App\Ai\Agents\ShowroomRecommendationExtractor;
use App\Enums\VehicleType;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\Article;
use App\Models\Product;
use App\Services\Ai\Dto\ShowroomStructuredReply;
use App\Services\Concurrency\ShowroomConcurrency;
use Generator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\AiManager;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Files\Image;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\MessageRole;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StreamableAgentResponse;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Laravel\Ai\Streaming\Events\TextDelta;
use Throwable;

class ShowroomAssistantService
{
    public function __construct(
        private GeminiKeyPool $geminiKeyPool,
        private ?ShowroomCatalogMatcher $matcher = null,
        private ?ShowroomKnowledgeBaseService $knowledgeBase = null,
        private ?GeminiAgentRunner $runner = null,
        private ?ShowroomAiToolkit $toolkit = null,
        private ?StructuredOutputParser $structuredParser = null,
    ) {}

    /**
     * @param  array{visitor_id?: string|null, page_url?: string|null, product_slug?: string|null, product_name?: string|null, image?: UploadedFile|null}  $options
     * @return array{answer: string, suggestions: array<int, array{label: string, url: string}>, status: string, reason?: string, current_product?: array<string, mixed>|null}
     */
    public function ask(string $message, array $options = []): array
    {
        $pageContext = $this->resolvePageContext($options);
        $context = $this->publicContext($message, $pageContext);
        $suggestions = $this->suggestionsFor($message, $context);
        $provider = $this->runner()->configuredProvider();
        $model = $this->runner()->configuredModel();
        $prompt = $this->buildPrompt($message, $context);
        $conversationMessages = $this->recentConversationMessages($options['visitor_id'] ?? null);
        $pageContextSummary = $this->buildPageContextSummary($pageContext);
        $attachments = $this->buildAttachments($options['image'] ?? null);
        $trackerProduct = $pageContext['tracker_product'] ?? null;

        if ($configurationReason = $this->runner()->configurationFailureReason($provider)) {
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
                attachments: $attachments,
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
                attachments: $attachments,
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

    /**
     * Stream assistant response as chunk & done events with key rotation and fallback.
     *
     * @param  array{visitor_id?: string|null, page_url?: string|null, product_slug?: string|null, product_name?: string|null, image?: UploadedFile|null}  $options
     * @return Generator<int, array{event: string, data: array<string, mixed>}>
     */
    public function stream(string $message, array $options = []): Generator
    {
        $pageContext = $this->resolvePageContext($options);
        $context = $this->publicContext($message, $pageContext);
        $suggestions = $this->suggestionsFor($message, $context);
        $provider = $this->runner()->configuredProvider();
        $model = $this->runner()->configuredModel();
        $prompt = $this->buildPrompt($message, $context);
        $conversationMessages = $this->recentConversationMessages($options['visitor_id'] ?? null);
        $pageContextSummary = $this->buildPageContextSummary($pageContext);
        $attachments = $this->buildAttachments($options['image'] ?? null);
        $trackerProduct = $pageContext['tracker_product'] ?? null;

        if ($configurationReason = $this->runner()->configurationFailureReason($provider)) {
            Log::info('Showroom AI stream skipped.', [
                'reason' => $configurationReason,
                'provider' => $provider,
            ]);

            yield [
                'event' => 'done',
                'data' => $this->withTrackerProduct($this->fallbackResponse($suggestions, 'configuration'), $trackerProduct),
            ];

            return;
        }

        if ($provider !== 'gemini') {
            try {
                $stream = $this->streamAssistant(
                    prompt: $prompt,
                    provider: $provider,
                    model: $model,
                    conversationMessages: $conversationMessages,
                    pageContextSummary: $pageContextSummary,
                    attachments: $attachments,
                );

                $rawText = '';
                foreach ($stream as $event) {
                    if ($event instanceof TextDelta && $event->delta !== '') {
                        $rawText .= $event->delta;
                        yield ['event' => 'chunk', 'data' => ['delta' => $event->delta]];
                    }
                }

                yield [
                    'event' => 'done',
                    'data' => $this->withTrackerProduct(
                        $this->buildSuccessfulPayload($rawText, $suggestions, $context, $message),
                        $trackerProduct,
                    ),
                ];

                return;
            } catch (Throwable $exception) {
                Log::warning('Showroom AI stream failed on non-Gemini provider.', [
                    'exception' => $exception::class,
                    'provider' => $provider,
                ]);

                yield [
                    'event' => 'done',
                    'data' => $this->withTrackerProduct($this->fallbackResponse($suggestions, 'provider_error'), $trackerProduct),
                ];

                return;
            }
        }

        $candidates = $this->geminiKeyPool->candidateSequence();
        if ($candidates === []) {
            yield [
                'event' => 'done',
                'data' => $this->withTrackerProduct(
                    $this->geminiKeyPool->hasKeys()
                        ? $this->fallbackResponse($suggestions, 'rate_limited', $this->rateLimitFallbackMessage())
                        : $this->fallbackResponse($suggestions, 'configuration'),
                    $trackerProduct,
                ),
            ];

            return;
        }

        $attempts = 0;
        $rateLimitedAttempts = 0;

        foreach ($candidates as $candidate) {
            $attempts++;
            $emittedChunk = false;
            $rawText = '';
            Context::add('gemini_key_slot', $candidate['index']);
            $providerName = $this->geminiKeyPool->providerNameFor($candidate);
            $originalConfig = config("ai.providers.{$providerName}");
            $baseConfig = config('ai.providers.gemini', []);

            Config::set("ai.providers.{$providerName}", array_merge($baseConfig, [
                'driver' => 'gemini',
                'key' => $candidate['key'],
            ]));

            app(AiManager::class)->forgetInstance($providerName);

            try {
                $stream = $this->streamAssistant(
                    prompt: $prompt,
                    provider: $providerName,
                    model: $model,
                    conversationMessages: $conversationMessages,
                    pageContextSummary: $pageContextSummary,
                    attachments: $attachments,
                );

                foreach ($stream as $streamEvent) {
                    if ($streamEvent instanceof TextDelta && $streamEvent->delta !== '') {
                        $emittedChunk = true;
                        $rawText .= $streamEvent->delta;
                        yield ['event' => 'chunk', 'data' => ['delta' => $streamEvent->delta]];
                    }
                }

                yield [
                    'event' => 'done',
                    'data' => $this->withTrackerProduct(
                        $this->buildSuccessfulPayload($rawText, $suggestions, $context, $message),
                        $trackerProduct,
                    ),
                ];

                return;
            } catch (RateLimitedException $exception) {
                $rateLimitedAttempts++;
                $this->geminiKeyPool->markRateLimited($candidate);
                if ($emittedChunk) {
                    break;
                }
            } catch (Throwable $exception) {
                if (! $emittedChunk && $this->runner()->isTransientProviderException($exception)) {
                    continue;
                }

                if ($emittedChunk && trim($rawText) !== '') {
                    yield [
                        'event' => 'done',
                        'data' => $this->withTrackerProduct(
                            $this->buildSuccessfulPayload($rawText, $suggestions, $context, $message),
                            $trackerProduct,
                        ),
                    ];

                    return;
                }

                yield [
                    'event' => 'done',
                    'data' => $this->withTrackerProduct($this->fallbackResponse($suggestions, 'provider_error'), $trackerProduct),
                ];

                return;
            } finally {
                app(AiManager::class)->forgetInstance($providerName);
                Config::set("ai.providers.{$providerName}", $originalConfig);
            }
        }

        $reason = ($attempts > 0 && $rateLimitedAttempts === $attempts) ? 'rate_limited' : 'provider_error';
        $fallbackAnswer = $reason === 'rate_limited' ? $this->rateLimitFallbackMessage() : null;

        yield [
            'event' => 'done',
            'data' => $this->withTrackerProduct($this->fallbackResponse($suggestions, $reason, $fallbackAnswer), $trackerProduct),
        ];
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

        AiChatSession::query()
            ->where('visitor_id', $normalizedVisitorId)
            ->update([
                'reset_after_message_id' => $latestMessageId,
            ]);

        Cache::put(
            $this->resetCacheKey($normalizedVisitorId),
            [
                'after_id' => $latestMessageId,
                'reset_at' => now()->toIso8601String(),
            ],
            now()->addDays(60)
        );

        return true;
    }

    /**
     * Re-hydrate recent guest conversation messages & Action Cards from database for lost localStorage recovery.
     *
     * @return array{
     *     status: string,
     *     visitor_id: string|null,
     *     conversation_id: int|null,
     *     restored: bool,
     *     messages: array<int, array<string, mixed>>,
     *     suggestions: array<int, array<string, mixed>>
     * }
     */
    public function getHistory(?string $visitorId): array
    {
        $normalizedVisitorId = $this->normalizeVisitorId($visitorId);

        if ($normalizedVisitorId === null) {
            return [
                'status' => 'ok',
                'visitor_id' => null,
                'conversation_id' => null,
                'restored' => false,
                'messages' => [],
                'suggestions' => [],
            ];
        }

        $history = $this->fetchActiveHistoryRecords($normalizedVisitorId);
        if ($history === null || $history['records']->isEmpty()) {
            return [
                'status' => 'ok',
                'visitor_id' => $normalizedVisitorId,
                'conversation_id' => $history['session']->id ?? null,
                'restored' => false,
                'messages' => [],
                'suggestions' => [],
            ];
        }

        /** @var AiChatSession $session */
        $session = $history['session'];
        $context = $this->publicContext('', []);
        $messages = [];
        $latestSuggestions = [];

        foreach ($history['records'] as $record) {
            $content = trim((string) $record->content);
            if ($content === '') {
                continue;
            }

            if ($record->role === 'user') {
                $messages[] = [
                    'role' => 'user',
                    'content' => $this->redactHistoryText($content),
                    'created_at' => $record->created_at?->toIso8601String(),
                ];

                continue;
            }

            if ($record->role === 'assistant') {
                $actions = $this->suggestionsFor('', $context, $content);
                $latestSuggestions = $actions;

                $messages[] = [
                    'role' => 'assistant',
                    'content' => $content,
                    'actions' => $actions,
                    'created_at' => $record->created_at?->toIso8601String(),
                ];
            }
        }

        return [
            'status' => 'ok',
            'visitor_id' => $normalizedVisitorId,
            'conversation_id' => $session->id,
            'restored' => $messages !== [],
            'messages' => $messages,
            'suggestions' => $latestSuggestions,
        ];
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

        $history = $this->fetchActiveHistoryRecords($normalizedVisitorId);
        if ($history === null) {
            return [];
        }

        $maxMessages = max(2, (int) config('showroom_ai.max_history_messages', 14));
        $records = $history['records'];

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
     * @return array{session: AiChatSession, records: Collection<int, AiChatMessage>}|null
     */
    private function fetchActiveHistoryRecords(string $normalizedVisitorId): ?array
    {
        $session = AiChatSession::query()
            ->where('visitor_id', $normalizedVisitorId)
            ->latest('last_seen_at')
            ->first();

        if (! $session) {
            return null;
        }

        $maxMessages = max(2, (int) config('showroom_ai.max_history_messages', 14));
        $ttlHours = max(1, (int) config('showroom_ai.history_ttl_hours', 1440));
        $resetMarker = Cache::get($this->resetCacheKey($normalizedVisitorId));
        $cachedAfterId = is_array($resetMarker) ? (int) ($resetMarker['after_id'] ?? 0) : 0;
        $afterId = max($cachedAfterId, (int) ($session->reset_after_message_id ?? 0));

        $records = AiChatMessage::query()
            ->where('ai_chat_session_id', $session->id)
            ->when($afterId > 0, fn ($query) => $query->where('id', '>', $afterId))
            ->where('created_at', '>=', now()->subHours($ttlHours))
            ->orderByDesc('id')
            ->limit($maxMessages * 2)
            ->get()
            ->reverse()
            ->values();

        return [
            'session' => $session,
            'records' => $records,
        ];
    }

    /**
     * @param  array<int, array{label: string, url: string}>  $suggestions
     * @param  array<int, Message>  $conversationMessages
     * @param  array<int, mixed>  $attachments
     * @param  array<string, mixed>  $context
     * @return array{answer: string, suggestions: array<int, array{label: string, url: string}>, status: string, reason?: string}
     */
    private function askGeminiWithRotation(
        string $prompt,
        array $suggestions,
        ?string $model,
        array $conversationMessages = [],
        ?string $pageContextSummary = null,
        array $attachments = [],
        array $context = [],
        string $message = ''
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
                $response = $this->runner()->withGeminiCandidate(
                    $candidate,
                    fn (string $providerName): AgentResponse => $this->promptAssistant(
                        prompt: $prompt,
                        provider: $providerName,
                        model: $model,
                        conversationMessages: $conversationMessages,
                        pageContextSummary: $pageContextSummary,
                        attachments: $attachments,
                    ),
                );

                return $this->successfulResponse($response, $suggestions, $context, $message);
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
                if ($this->runner()->isTransientProviderException($exception)) {
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
     * @param  array<int, Message>  $conversationMessages
     * @param  array<int, mixed>  $attachments
     */
    private function promptAssistant(
        string $prompt,
        string $provider,
        ?string $model,
        array $conversationMessages = [],
        ?string $pageContextSummary = null,
        array $attachments = []
    ): AgentResponse {
        return ShowroomAssistant::make(
            conversationMessages: $conversationMessages,
            pageContextSummary: $pageContextSummary,
        )->prompt(
            prompt: $prompt,
            attachments: $attachments,
            provider: $provider,
            model: $model,
            timeout: max(10, (int) config('showroom_ai.timeout_seconds', 30)),
        );
    }

    /**
     * @param  array<int, Message>  $conversationMessages
     * @param  array<int, mixed>  $attachments
     */
    private function streamAssistant(
        string $prompt,
        string $provider,
        ?string $model,
        array $conversationMessages = [],
        ?string $pageContextSummary = null,
        array $attachments = []
    ): StreamableAgentResponse {
        return ShowroomAssistant::make(
            conversationMessages: $conversationMessages,
            pageContextSummary: $pageContextSummary,
        )->stream(
            prompt: $prompt,
            attachments: $attachments,
            provider: $provider,
            model: $model,
            timeout: max(10, (int) config('showroom_ai.timeout_seconds', 30)),
        );
    }

    /**
     * @return array<int, mixed>
     */
    private function buildAttachments(mixed $image): array
    {
        if (! $image instanceof UploadedFile) {
            return [];
        }

        return [Image::fromUpload($image)];
    }

    /**
     * @param  array<int, array{label: string, url: string, subtitle?: string, kind?: string}>  $suggestions
     * @param  array<string, mixed>  $context
     * @return array{answer: string, suggestions: array<int, array{label: string, url: string, subtitle?: string, kind?: string}>, status: string, structured?: array<string, mixed>, tool_calls?: list<string>}
     */
    private function successfulResponse(AgentResponse $response, array $suggestions, array $context = [], string $message = ''): array
    {
        $responseToolCalls = [];
        if (isset($response->toolCalls) && is_iterable($response->toolCalls)) {
            foreach ($response->toolCalls as $toolCall) {
                $name = is_object($toolCall) ? ($toolCall->name ?? null) : (is_array($toolCall) ? ($toolCall['name'] ?? null) : null);
                if (is_string($name) && trim($name) !== '') {
                    $responseToolCalls[] = trim($name);
                }
            }
        }

        return $this->buildSuccessfulPayload($response->text, $suggestions, $context, $message, $responseToolCalls);
    }

    /**
     * @param  array<int, array{label: string, url: string, subtitle?: string, kind?: string}>  $suggestions
     * @param  array<string, mixed>  $context
     * @param  list<string>  $agentToolCalls
     * @return array{answer: string, suggestions: array<int, array{label: string, url: string, subtitle?: string, kind?: string}>, status: string, structured: array<string, mixed>, tool_calls: list<string>}
     */
    private function buildSuccessfulPayload(string $rawAnswer, array $suggestions, array $context = [], string $message = '', array $agentToolCalls = []): array
    {
        $structured = $this->structuredParser()->parse($rawAnswer, $message, $context);
        $answer = trim($structured->replyMarkdown);

        if ($answer !== '') {
            $answer = $this->sanitizeAndRepairMarkdownLinks($answer, $context);
            $suggestions = $this->suggestionsFor($message, $context, $answer, $structured);
        }

        $toolCalls = array_values(array_unique(array_filter(array_merge(
            is_array($context['_tool_calls'] ?? null) ? $context['_tool_calls'] : [],
            $agentToolCalls,
        ), static fn (mixed $item): bool => is_string($item) && trim($item) !== '')));

        return [
            'answer' => $answer !== '' ? $answer : $this->fallbackMessage(),
            'suggestions' => $suggestions,
            'status' => 'ok',
            'structured' => $structured->toArray(),
            'tool_calls' => $toolCalls,
        ];
    }

    /**
     * @param  array<string, mixed>  $pageContext
     * @return array{
     *     products: array<int, array<string, mixed>>,
     *     articles: array<int, array<string, mixed>>,
     *     routes: array<string, string>,
     *     current_product?: array<string, mixed>,
     *     current_article?: array<string, mixed>,
     *     _rag_chunks?: array<int, array<string, mixed>>,
     *     _tool_calls?: list<string>
     * }
     */
    public function publicContext(?string $message = null, array $pageContext = []): array
    {
        /** @var Product|null $currentProductModel */
        $currentProductModel = $pageContext['product_model'] ?? null;
        $currentProductId = $currentProductModel instanceof Product ? (int) $currentProductModel->id : null;
        $messageStr = trim((string) ($message ?? ''));
        $maxProducts = max(30, (int) config('showroom_ai.max_context_products', 30));
        $maxArticles = (int) config('showroom_ai.max_context_articles', 5);

        $parallel = ShowroomConcurrency::run([
            'catalog_and_articles' => static function () use ($messageStr, $currentProductId, $maxProducts, $maxArticles): array {
                $matcher = app(ShowroomCatalogMatcher::class);
                $normalizedMessage = $matcher->normalizeSearchText($messageStr);

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
                    ->orderBy('id')
                    ->get()
                    ->sortByDesc(static function (Product $product) use ($matcher, $normalizedMessage, $currentProductId): int {
                        $score = $matcher->scoreMessageRelevance($product, $normalizedMessage);

                        if ($currentProductId !== null && $product->id === $currentProductId) {
                            $score += 1000;
                        }

                        return $score;
                    })
                    ->take($maxProducts)
                    ->values()
                    ->all();

                $articles = Article::query()
                    ->published()
                    ->select(['title', 'slug', 'category', 'excerpt', 'published_at'])
                    ->latest('published_at')
                    ->limit($maxArticles)
                    ->get()
                    ->map(static fn (Article $article): array => [
                        'title' => $article->title,
                        'category' => $article->categoryLabel(),
                        'excerpt' => $matcher->compactText($article->excerpt),
                        'url' => route('articles.show', $article->slug, false),
                    ])
                    ->values()
                    ->all();

                return [
                    'products' => $products,
                    'articles' => $articles,
                ];
            },
            'rag_knowledge' => static function () use ($messageStr): array {
                if ($messageStr === '') {
                    return [];
                }

                return app(ShowroomKnowledgeBaseService::class)->retrieveRelevantChunks($messageStr, 3, false);
            },
        ]);

        $catalogPayload = is_array($parallel['catalog_and_articles'] ?? null) ? $parallel['catalog_and_articles'] : [];
        $rawProducts = $catalogPayload['products'] ?? [];
        $products = collect($rawProducts)
            ->map(fn (Product $product): array => $this->productContext($product))
            ->values()
            ->all();

        $articles = is_array($catalogPayload['articles'] ?? null) ? $catalogPayload['articles'] : [];
        $ragChunks = is_array($parallel['rag_knowledge'] ?? null) ? $parallel['rag_knowledge'] : [];

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
            '_rag_chunks' => $ragChunks,
        ];

        if ($currentProductModel instanceof Product) {
            $context['current_product'] = $this->productContext($currentProductModel);
        }

        if (isset($pageContext['current_article']) && is_array($pageContext['current_article'])) {
            $context['current_article'] = $pageContext['current_article'];
        }

        $grounding = $messageStr !== ''
            ? $this->toolkit()->executeGroundingForMessage($messageStr, $context)
            : ['tool_calls' => [], 'results' => []];
        $context['_tool_calls'] = $grounding['tool_calls'];
        $context['_toolkit_results'] = $grounding['results'];

        return $context;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function buildPrompt(string $message, array $context): string
    {
        $cleanContext = $context;
        $preloadedRagChunks = $cleanContext['_rag_chunks'] ?? null;
        $toolkitResults = $cleanContext['_toolkit_results'] ?? null;
        unset($cleanContext['_rag_chunks'], $cleanContext['_tool_calls'], $cleanContext['_toolkit_results']);

        $encodedContext = json_encode(
            $cleanContext,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        $pageContextBlock = '';

        if (isset($cleanContext['current_product']) && is_array($cleanContext['current_product'])) {
            $currentProduct = $cleanContext['current_product'];
            $priceText = $currentProduct['price'] ?? 'Liên hệ showroom';
            $pageContextBlock = <<<CONTEXT

Ngữ cảnh trang hiện tại:
- Khách hàng đang xem sản phẩm public: {$currentProduct['name']} (đường dẫn: {$currentProduct['url']}, dòng: {$currentProduct['product_line']}, giá: {$priceText}).
- Nếu Quý khách dùng đại từ như "xe này", "mẫu này", "chiếc này", "sản phẩm này" mà không nêu tên cụ thể, mặc định là đang hỏi về {$currentProduct['name']}.

CONTEXT;
        } elseif (isset($cleanContext['current_article']) && is_array($cleanContext['current_article'])) {
            $currentArticle = $cleanContext['current_article'];
            $pageContextBlock = <<<CONTEXT

Ngữ cảnh trang hiện tại:
- Khách hàng đang đọc bài viết public: {$currentArticle['title']} ({$currentArticle['url']}).

CONTEXT;
        }

        $ragChunks = is_array($preloadedRagChunks)
            ? $preloadedRagChunks
            : $this->knowledgeBase()->retrieveRelevantChunks($message, 3, false);
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

        $toolkitBlock = '';
        if (is_array($toolkitResults) && $toolkitResults !== []) {
            $encodedToolkit = json_encode(
                $toolkitResults,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
            );

            $toolkitBlock = <<<TOOLKIT

[DỮ LIỆU ĐỐI CHIẾU CÔNG CỤ THỜI GIAN THỰC]
{$encodedToolkit}

TOOLKIT;
        }

        return <<<PROMPT
BMW showroom gồm ô tô BMW, BMW Motorrad, phụ kiện, ưu đãi và dịch vụ showroom.
Bạn là Chuyên viên Tư vấn Trực tuyến Cao cấp của BMW Showroom Vietnam.
Phong cách giao tiếp: Tinh tế, súc tích, chuẩn chuyên gia; gọi khách hàng là "Quý khách", không lặp "BMW Showroom" quá 2 lần/câu trả lời và tránh mở đầu rập khuôn kiểu "Kính thưa Quý khách".

Dữ liệu public của showroom BMW:
{$encodedContext}
{$pageContextBlock}{$ragBlock}{$toolkitBlock}
Nguyên tắc trả lời:
- CHỈ sử dụng dữ liệu public ở trên, tài liệu trong mục [TÀI LIỆU TRI THỨC RAG ĐƯỢC XÁC THỰC] và kết quả từ các công cụ (`search_catalog`, `compare_vehicles`, `search_articles_and_offers`, `search_knowledge_base`, `calculate_financial_estimate`, `check_accessory_stock`, `list_service_packages`).
- Khi cần tra cứu chi tiết theo ngân sách, phân khúc (SUV/SAV, xe điện, xe máy/mô tô PKL), so sánh 2–3 mẫu xe, tính trả góp, kiểm tra tồn kho phụ kiện hoặc 4 gói chăm sóc xe, chủ động sử dụng số liệu từ công cụ tương ứng.
- Tuyệt đối KHÔNG suy diễn hoặc bịa đặt giá bán, thông số kỹ thuật hay chính sách nếu không có trong RAG hoặc dữ liệu public trên. Nếu thiếu thông tin, nói rõ chưa có thông tin và mời Quý khách đặt lịch tư vấn qua [Tư vấn trực tiếp]({$cleanContext['routes']['booking_consult']}).
- Tuyệt đối KHÔNG xuất markdown link rỗng `[]()` hoặc `(#)`, và KHÔNG viết câu dẫn link thừa kiểu "Quý khách có thể tìm hiểu thêm tại đây:" ở cuối câu trả lời vì Action Cards đã hiển thị sẵn bên dưới.
- Khi Quý khách quan tâm mua xe, nhận báo giá chi tiết, đăng ký lái thử hoặc bảo dưỡng dịch vụ: Luôn sử dụng đúng liên kết chính thức:
  * Đăng ký lái thử xe: [Đăng ký lái thử]({$cleanContext['routes']['booking_test_drive']})
  * Nhận báo giá & tư vấn: [Yêu cầu báo giá]({$cleanContext['routes']['quote']}) hoặc [Tư vấn trực tiếp]({$cleanContext['routes']['booking_consult']})
  * Phụ kiện chính hãng: [Xem phụ kiện]({$cleanContext['routes']['accessories']})
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
     * @return array<int, array{label: string, url: string, subtitle?: string, kind?: string}>
     */
    private function suggestionsFor(string $message, array $context, ?string $answer = null, ?ShowroomStructuredReply $structured = null): array
    {
        $loweredMessage = Str::lower($message);
        $normalizedMessage = $this->matcher()->normalizeSearchText($loweredMessage);
        $products = collect($context['products']);
        $suggestions = [];
        $currentProduct = isset($context['current_product']) && is_array($context['current_product'])
            ? $context['current_product']
            : null;
        $extractedEntities = $answer !== null ? $this->extractMentionedEntitySuggestions($answer) : [];
        if ($answer !== null && $extractedEntities === [] && $structured && $structured->recommendedProductIds !== []) {
            $extractedEntities = $this->resolveStructuredProductSuggestions($structured->recommendedProductIds);
        }
        if ($answer !== null && $extractedEntities === []) {
            $extractedEntities = $this->extractStructuredRecommendations($message, $answer, $context);
        }

        $includeCurrentProduct = $currentProduct !== null && (
            $answer === null
            || $this->shouldIncludeCurrentProductSuggestion($message, $answer, $currentProduct, $extractedEntities)
        );

        if ($currentProduct && $includeCurrentProduct) {
            if (($currentProduct['is_accessory'] ?? false) === true) {
                if (filled($currentProduct['accessory_order_url'] ?? null)) {
                    $suggestions[] = [
                        'subtitle' => 'PHỤ KIỆN CHÍNH HÃNG',
                        'label' => $currentProduct['name'],
                        'url' => $currentProduct['accessory_order_url'],
                        'kind' => 'accessory',
                    ];
                }

                $suggestions[] = [
                    'label' => 'Xem phụ kiện chính hãng',
                    'url' => route('accessories.index', [], false),
                ];
            } else {
                $suggestions[] = [
                    'subtitle' => 'HỒ SƠ SẢN PHẨM',
                    'label' => $currentProduct['name'],
                    'url' => $currentProduct['url'],
                    'kind' => 'product',
                ];

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
            }
        }

        $matchedProducts = $products
            ->filter(fn (array $product): bool => $extractedEntities !== []
                ? $this->contextProductDirectMatch($product, $normalizedMessage)
                : $this->contextProductMatches($product, $normalizedMessage))
            ->values();
        $matchedProduct = $matchedProducts->first();

        if ($matchedProduct && (! $currentProduct || $includeCurrentProduct || ($matchedProduct['slug'] ?? null) !== ($currentProduct['slug'] ?? null))) {
            $suggestions[] = [
                'subtitle' => ($matchedProduct['is_accessory'] ?? false) === true ? 'PHỤ KIỆN CHÍNH HÃNG' : 'HỒ SƠ SẢN PHẨM',
                'label' => 'Xem '.$matchedProduct['name'],
                'url' => $matchedProduct['url'],
                'kind' => ($matchedProduct['is_accessory'] ?? false) === true ? 'accessory' : 'product',
            ];
        }

        foreach ($extractedEntities as $entity) {
            $suggestions[] = $entity;
        }

        $extractedVehicleIds = collect($extractedEntities)
            ->filter(fn (array $entity): bool => ($entity['kind'] ?? null) === 'product' && isset($entity['product_id']))
            ->pluck('product_id')
            ->unique()
            ->values();

        if ($extractedVehicleIds->count() >= 2 && (! $currentProduct || ! $includeCurrentProduct)) {
            $suggestions[] = [
                'subtitle' => 'SO SÁNH TRỰC TIẾP',
                'label' => 'So sánh '.$extractedVehicleIds->count().' mẫu xe',
                'url' => route('products.compare', [], false).'?ids='.$extractedVehicleIds->take(4)->implode(','),
                'kind' => 'compare',
            ];
        }

        $accessory = $products->first(fn (array $product): bool => filled($product['accessory_order_url']));
        if ($accessory && Str::contains($loweredMessage, ['phụ kiện', 'phu kien', 'thảm', 'tham', 'đặt hàng'])) {
            $suggestions[] = [
                'label' => 'Đặt phụ kiện',
                'url' => $accessory['accessory_order_url'],
            ];
        }

        $vehicles = $products->filter(fn (array $product): bool => filled($product['compare_url']))->values();
        if ($vehicles->count() >= 2 && Str::contains($loweredMessage, ['so sánh', 'so sanh', 'khác gì', 'khac gi'])) {
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

        if (Str::contains($loweredMessage, ['motorrad', 'xe máy', 'xe may', 'mô tô', 'mo to', 'pkl', 's1000', 's 1000'])) {
            $suggestions[] = [
                'label' => 'Xem BMW Motorrad',
                'url' => route('products.index', ['type' => VehicleType::MOTORBIKE->value], false),
            ];
        }

        if ($extractedEntities !== [] && (! $currentProduct || ! $includeCurrentProduct)) {
            $suggestions[] = [
                'label' => 'Đặt lịch lái thử',
                'url' => route('appointments.create', ['type' => 'test_drive'], false),
            ];
        }

        $suggestions[] = ['label' => 'Xem danh mục xe', 'url' => route('products.index', [], false)];
        $suggestions[] = ['label' => 'Đặt lịch tư vấn', 'url' => route('appointments.create', ['type' => 'consult'], false)];
        $suggestions[] = ['label' => 'Đọc ưu đãi & tin tức', 'url' => route('articles.index', [], false)];

        return collect($suggestions)
            ->map(function (array $suggestion): array {
                unset($suggestion['product_id']);

                return $suggestion;
            })
            ->unique(fn (array $suggestion): string => $this->canonicalSuggestionUrl((string) $suggestion['url']))
            ->take(4)
            ->values()
            ->all();
    }

    /**
     * Extract mentioned active products and published articles in exact narrative order.
     *
     * @return list<array{subtitle: string, label: string, url: string, kind: string, product_id?: int}>
     */
    private function extractMentionedEntitySuggestions(string $answer): array
    {
        $answer = trim($answer);
        if ($answer === '') {
            return [];
        }

        $products = Product::query()
            ->active()
            ->with('category')
            ->get(['id', 'category_id', 'name', 'slug', 'type', 'is_active'])
            ->sortByDesc(fn (Product $product): int => mb_strlen($product->name))
            ->values();

        $shortAliasesById = $this->uniqueShortProductAliases($products);
        $entities = [];

        foreach ($products as $product) {
            $url = $product->isAccessory()
                ? route('accessory-orders.create', $product->slug, false)
                : route('products.show', $product->slug, false);

            $position = $this->firstEntityMentionOffset($answer, $product->name, $url, $shortAliasesById[$product->id] ?? null);
            if ($position === null) {
                continue;
            }

            $entities[] = [
                'pos' => $position,
                'subtitle' => $product->isAccessory() ? 'PHỤ KIỆN CHÍNH HÃNG' : 'HỒ SƠ SẢN PHẨM',
                'label' => $product->name,
                'url' => $url,
                'kind' => $product->isAccessory() ? 'accessory' : 'product',
                'product_id' => $product->id,
            ];
        }

        $articles = Article::query()
            ->published()
            ->get(['id', 'title', 'slug', 'category'])
            ->sortByDesc(fn (Article $article): int => mb_strlen($article->title))
            ->values();

        foreach ($articles as $article) {
            $url = route('articles.show', $article->slug, false);
            $position = $this->firstEntityMentionOffset($answer, $article->title, $url);
            if ($position === null) {
                continue;
            }

            $entities[] = [
                'pos' => $position,
                'subtitle' => 'BÀI VIẾT & ƯU ĐÃI',
                'label' => $article->title,
                'url' => $url,
                'kind' => 'article',
            ];
        }

        usort($entities, fn (array $a, array $b): int => $a['pos'] <=> $b['pos']);

        return array_map(function (array $entity): array {
            unset($entity['pos']);

            return $entity;
        }, $entities);
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return array<int, string>
     */
    private function uniqueShortProductAliases(Collection $products): array
    {
        $candidates = [];
        $counts = [];

        foreach ($products as $product) {
            $shortName = trim((string) preg_replace(
                '/(?:\s+(?:Sedan|Gran\s+Coupe|Roadster|Touring|Coupe|Convertible|xDrive\d*[a-z]*|M\s+Sport|LCI|Executive))+$/iu',
                '',
                trim($product->name)
            ));

            if ($shortName === '' || mb_strlen($shortName) >= mb_strlen(trim($product->name)) || mb_strlen($shortName) < 5) {
                continue;
            }

            $key = Str::lower($shortName);
            $candidates[$product->id] = $shortName;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        $unique = [];
        foreach ($candidates as $productId => $shortName) {
            if (($counts[Str::lower($shortName)] ?? 0) === 1) {
                $unique[$productId] = $shortName;
            }
        }

        return $unique;
    }

    private function firstEntityMentionOffset(string $answer, string $name, string $url, ?string $shortAlias = null): ?int
    {
        $offsets = [];

        $urlPos = mb_strpos($answer, $url);
        if ($urlPos !== false) {
            $offsets[] = strlen(mb_substr($answer, 0, $urlPos));
        }

        $namePattern = '/(?<![\p{L}\p{N}])'.preg_quote(trim($name), '/').'(?![\p{L}\p{N}])/ui';
        if (preg_match($namePattern, $answer, $matches, PREG_OFFSET_CAPTURE) === 1) {
            $offsets[] = (int) $matches[0][1];
        }

        if ($shortAlias !== null && $shortAlias !== '') {
            $aliasPattern = '/(?<![\p{L}\p{N}])'.preg_quote($shortAlias, '/').'(?![\p{L}\p{N}])/ui';
            if (preg_match($aliasPattern, $answer, $matches, PREG_OFFSET_CAPTURE) === 1) {
                $offsets[] = (int) $matches[0][1];
            }
        }

        return $offsets !== [] ? min($offsets) : null;
    }

    /**
     * @param  array<string, mixed>  $currentProduct
     * @param  list<array<string, mixed>>  $extractedEntities
     */
    private function shouldIncludeCurrentProductSuggestion(
        string $message,
        string $answer,
        array $currentProduct,
        array $extractedEntities,
    ): bool {
        $combined = $message."\n".$answer;
        $productName = trim((string) ($currentProduct['name'] ?? ''));
        $productUrl = trim((string) ($currentProduct['url'] ?? ''));

        if ($productUrl !== '' && str_contains($answer, $productUrl)) {
            return true;
        }

        if ($productName !== '') {
            $namePattern = '/(?<![\p{L}\p{N}])'.preg_quote($productName, '/').'(?![\p{L}\p{N}])/ui';
            if (preg_match($namePattern, $combined) === 1) {
                return true;
            }

            $modelCode = $this->extractProductModelCode($productName);
            if ($modelCode !== null) {
                $codePattern = '/(?<![\p{L}\p{N}])'.preg_quote($modelCode, '/').'(?![\p{L}\p{N}])/ui';
                if (preg_match($codePattern, $combined) === 1) {
                    return true;
                }
            }
        }

        $lowerMessage = Str::lower($message);
        $asciiMessage = Str::ascii($lowerMessage);
        $demonstratives = [
            'xe này',
            'mẫu này',
            'mẫu xe này',
            'chiếc này',
            'chiếc xe này',
            'xe đang xem',
            'chiếc đang xem',
            'mẫu đang xem',
            'sản phẩm này',
            'lăn bánh',
            'trả góp',
            'đặt cọc',
            'lái thử mẫu này',
            'xe nay',
            'mau nay',
            'mau xe nay',
            'chiec nay',
            'chiec xe nay',
            'xe dang xem',
            'chiec dang xem',
            'mau dang xem',
            'san pham nay',
            'lan banh',
            'tra gop',
            'dat coc',
            'lai thu mau nay',
        ];

        if (Str::contains($lowerMessage, $demonstratives) || Str::contains($asciiMessage, $demonstratives)) {
            return true;
        }

        return $extractedEntities === [] && ! $this->isGeneralPolicyQuestion($message);
    }

    /**
     * @param  array<string, mixed>  $product
     */
    private function contextProductDirectMatch(array $product, string $normalizedMessage): bool
    {
        if ($normalizedMessage === '') {
            return false;
        }

        $candidates = array_filter([
            $this->matcher()->normalizeSearchText((string) ($product['name'] ?? '')),
            $this->matcher()->normalizeSearchText((string) ($product['slug'] ?? '')),
            $this->matcher()->normalizeSearchText((string) ($this->extractProductModelCode((string) ($product['name'] ?? '')) ?? '')),
        ]);

        foreach ($candidates as $candidate) {
            if (strlen($candidate) >= 2 && Str::contains($normalizedMessage, $candidate)) {
                return true;
            }
        }

        return false;
    }

    private function extractProductModelCode(string $productName): ?string
    {
        $stripped = trim((string) preg_replace('/^bmw\s+/i', '', trim($productName)));
        $stripped = trim((string) preg_replace(
            '/(?:\s+(?:Sedan|Gran\s+Coupe|Roadster|Touring|Coupe|Convertible|xDrive\d*[a-z]*|M\s+Sport|LCI|Executive))+$/iu',
            '',
            $stripped
        ));

        if ($stripped === '' || mb_strlen($stripped) < 2) {
            return null;
        }

        return $stripped;
    }

    private function isGeneralPolicyQuestion(string $message): bool
    {
        $lowerMessage = Str::lower($message);
        $asciiMessage = Str::ascii($lowerMessage);
        $policyKeywords = [
            'bảo hành',
            'bao hanh',
            'pin xe điện',
            'pin xe dien',
            'chai pin',
            'bảo dưỡng',
            'bao duong',
            'cbs',
            'thu cũ đổi mới',
            'thu cu doi moi',
            'trade-in',
            'trade in',
            'bằng lái',
            'bang lai',
            'gplx',
            'chính sách',
            'chinh sach',
            'quy định',
            'quy dinh',
            'thủ tục',
            'thu tuc',
        ];

        return Str::contains($lowerMessage, $policyKeywords)
            || Str::contains($asciiMessage, $policyKeywords);
    }

    private function canonicalSuggestionUrl(string $url): string
    {
        $trimmed = trim($url);
        if ($trimmed === '' || $trimmed === '/') {
            return '/';
        }

        $parts = parse_url($trimmed);
        $path = rtrim((string) ($parts['path'] ?? $trimmed), '/');
        $path = $path !== '' ? $path : '/';
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '';

        return $path.$query;
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

    private function runner(): GeminiAgentRunner
    {
        return $this->runner ??= app(GeminiAgentRunner::class);
    }

    private function toolkit(): ShowroomAiToolkit
    {
        return $this->toolkit ??= app(ShowroomAiToolkit::class);
    }

    private function structuredParser(): StructuredOutputParser
    {
        return $this->structuredParser ??= app(StructuredOutputParser::class);
    }

    /**
     * @param  list<int>  $productIds
     * @return list<array<string, mixed>>
     */
    private function resolveStructuredProductSuggestions(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $productsById = Product::query()
            ->active()
            ->with('category')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $suggestions = [];
        foreach ($productIds as $id) {
            $product = $productsById->get($id);
            if (! $product) {
                continue;
            }

            $isAccessory = $product->isAccessory();
            $suggestions[] = [
                'product_id' => (int) $product->id,
                'subtitle' => $isAccessory ? 'PHỤ KIỆN CHÍNH HÃNG' : 'HỒ SƠ SẢN PHẨM',
                'label' => $product->name,
                'url' => $isAccessory
                    ? route('accessory-orders.create', $product->slug, false)
                    : route('products.show', $product->slug, false),
                'kind' => $isAccessory ? 'accessory' : 'product',
            ];
        }

        return $suggestions;
    }

    /**
     * Invoke structured entity extractor ONLY when prose lacks explicit entity matches and mentions buying/products.
     *
     * @param  array<string, mixed>  $context
     * @return list<array<string, mixed>>
     */
    private function extractStructuredRecommendations(string $message, string $answer, array $context): array
    {
        $isFaked = app(AiManager::class)->hasFakeGatewayFor(ShowroomRecommendationExtractor::class);
        $enabled = (bool) config('showroom_ai.structured_recommendations_enabled', false);

        if (! $isFaked && ! $enabled) {
            return [];
        }

        $lowerAnswer = Str::lower($message."\n".$answer);
        $hasAdvisorySignal = Str::contains($lowerAnswer, ['bmw', 'mẫu xe', 'dòng xe', 'phụ kiện', 'báo giá', 'lái thử', 'mua xe', 'tư vấn']);
        if (! $isFaked && ! $hasAdvisorySignal) {
            return [];
        }

        $productCatalog = collect($context['products'] ?? [])
            ->map(fn (array $p): string => ($p['slug'] ?? '').': '.($p['name'] ?? ''))
            ->implode("\n");
        $articleCatalog = collect($context['articles'] ?? [])
            ->map(fn (array $a): string => basename((string) ($a['url'] ?? '')).': '.($a['title'] ?? ''))
            ->implode("\n");

        $prompt = "Câu hỏi khách: {$message}\nCâu trả lời AI: {$answer}\n\nSản phẩm hợp lệ:\n{$productCatalog}\n\nBài viết hợp lệ:\n{$articleCatalog}";

        try {
            $structured = null;

            if ($isFaked) {
                $fakeResponse = ShowroomRecommendationExtractor::make()->prompt($prompt);
                if ($fakeResponse instanceof StructuredAgentResponse) {
                    $structured = $fakeResponse->toArray();
                }
            } else {
                $run = $this->runner()->run(
                    fn (string $provider, ?string $model): mixed => ShowroomRecommendationExtractor::make()->prompt(
                        prompt: $prompt,
                        provider: $provider,
                        model: $model,
                        timeout: 12,
                    ),
                    operation: 'showroom_structured_recommendations',
                );

                if (($run['ok'] ?? false) === true && ($run['result'] ?? null) instanceof StructuredAgentResponse) {
                    $structured = $run['result']->toArray();
                }
            }

            if (! is_array($structured)) {
                return [];
            }

            $results = [];
            $productSlugs = collect($structured['recommended_product_slugs'] ?? [])->filter()->take(3)->values()->all();
            if ($productSlugs !== []) {
                $productsBySlug = Product::query()->active()->whereIn('slug', $productSlugs)->get()->keyBy('slug');
                foreach ($productSlugs as $slug) {
                    $product = $productsBySlug->get((string) $slug);
                    if (! $product) {
                        continue;
                    }
                    $isAccessory = $product->isAccessory();
                    $results[] = [
                        'product_id' => $product->id,
                        'subtitle' => $isAccessory ? 'PHỤ KIỆN CHÍNH HÃNG' : 'HỒ SƠ SẢN PHẨM',
                        'label' => $product->name,
                        'url' => $isAccessory
                            ? route('accessory-orders.create', $product->slug, false)
                            : route('products.show', $product->slug, false),
                        'kind' => $isAccessory ? 'accessory' : 'product',
                    ];
                }
            }

            $articleSlugs = collect($structured['recommended_article_slugs'] ?? [])->filter()->take(2)->values()->all();
            if ($articleSlugs !== []) {
                $articlesBySlug = Article::query()->published()->whereIn('slug', $articleSlugs)->get()->keyBy('slug');
                foreach ($articleSlugs as $slug) {
                    $article = $articlesBySlug->get((string) $slug);
                    if (! $article) {
                        continue;
                    }
                    $results[] = [
                        'subtitle' => 'BÀI VIẾT & ƯU ĐÃI',
                        'label' => $article->title,
                        'url' => route('articles.show', $article->slug, false),
                        'kind' => 'article',
                    ];
                }
            }

            return $results;
        } catch (Throwable) {
            return [];
        }
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
