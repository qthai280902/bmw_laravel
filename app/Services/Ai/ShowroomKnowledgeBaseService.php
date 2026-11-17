<?php

namespace App\Services\Ai;

use App\Models\Article;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ShowroomKnowledgeBaseService
{
    /**
     * @var list<string>
     */
    private const GENERIC_QUERY_TOKENS = [
        'bmw',
        'showroom',
        'xe',
        'cho',
        'toi',
        'minh',
        'hoi',
        've',
        'cua',
        'nhu',
        'the',
        'nao',
        'co',
        'khong',
        'gi',
        'sao',
        'la',
        'bao',
        'nhieu',
        'lau',
        'va',
        'voi',
        'duoc',
    ];

    /**
     * @var array<int, array{
     *     id: string,
     *     category: string,
     *     title: string,
     *     keywords: array<int, string>,
     *     content: string,
     *     official_links: array<int, array{label: string, url: string}>
     * }>|null
     */
    private ?array $cachedChunks = null;

    public function __construct(
        private readonly ShowroomCatalogMatcher $matcher,
        private ?string $knowledgePath = null,
        private ?CloudflareEmbeddingService $embeddingService = null,
    ) {}

    /**
     * @return array<int, array{
     *     id: string,
     *     category: string,
     *     title: string,
     *     keywords: array<int, string>,
     *     content: string,
     *     official_links: array<int, array{label: string, url: string}>
     * }>
     */
    public function knowledgeChunks(): array
    {
        if ($this->cachedChunks !== null) {
            return $this->cachedChunks;
        }

        $path = $this->knowledgePath ?: resource_path('knowledge/bmw_showroom_knowledge.json');

        if (! File::exists($path) || ! File::isFile($path)) {
            return $this->cachedChunks = [];
        }

        $raw = File::get($path);
        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return $this->cachedChunks = [];
        }

        $normalized = [];

        foreach ($decoded as $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = trim((string) ($item['id'] ?? ''));
            $title = trim((string) ($item['title'] ?? ''));
            $content = trim((string) ($item['content'] ?? ''));

            if ($id === '' || $title === '' || $content === '') {
                continue;
            }

            $keywords = collect($item['keywords'] ?? [])
                ->filter(fn (mixed $keyword): bool => is_string($keyword) && trim($keyword) !== '')
                ->map(fn (string $keyword): string => trim($keyword))
                ->values()
                ->all();

            $links = collect($item['official_links'] ?? [])
                ->filter(fn (mixed $link): bool => is_array($link)
                    && is_string($link['label'] ?? null)
                    && is_string($link['url'] ?? null)
                    && str_starts_with(trim((string) $link['url']), '/'))
                ->map(fn (array $link): array => [
                    'label' => trim((string) $link['label']),
                    'url' => trim((string) $link['url']),
                ])
                ->values()
                ->all();

            $normalized[] = [
                'id' => $id,
                'category' => trim((string) ($item['category'] ?? 'Thông tin Showroom')),
                'title' => $title,
                'keywords' => $keywords,
                'content' => $content,
                'official_links' => $links,
            ];
        }

        return $this->cachedChunks = $normalized;
    }

    /**
     * @return array<int, array{
     *     source: string,
     *     id: string,
     *     category: string,
     *     title: string,
     *     content: string,
     *     official_links: array<int, array{label: string, url: string}>,
     *     score: int,
     *     semantic_score: int
     * }>
     */
    public function retrieveRelevantChunks(string $query, int $limit = 4, bool $includeCatalogRecords = true): array
    {
        $limit = max(1, min(10, $limit));
        $trimmedQuery = trim($query);
        $chunks = $this->knowledgeChunks();
        $queryVector = $trimmedQuery !== '' ? $this->embeddingService()->embedQuery($trimmedQuery) : null;
        $knowledgeEmbeddings = $queryVector !== null ? $this->loadKnowledgeEmbeddings() : [];
        $candidates = [];

        foreach ($chunks as $chunk) {
            $lexicalScore = $this->scoreKnowledgeChunk($chunk, $trimmedQuery);
            $semanticScore = $chunk['id'] === 'strict_boundaries'
                ? 0
                : $this->computeSemanticScore(
                    $queryVector,
                    $knowledgeEmbeddings[$chunk['id']]['embedding'] ?? null
                );
            $score = $lexicalScore + $semanticScore;

            if ($score > 0) {
                $candidates[] = [
                    'source' => 'knowledge_base',
                    'id' => $chunk['id'],
                    'category' => $chunk['category'],
                    'title' => $chunk['title'],
                    'content' => $chunk['content'],
                    'official_links' => $chunk['official_links'],
                    'score' => $score,
                    'semantic_score' => $semanticScore,
                ];
            }
        }

        if ($includeCatalogRecords && $trimmedQuery !== '') {
            foreach ($this->retrieveCatalogCandidates($trimmedQuery, $queryVector) as $catalogCandidate) {
                $candidates[] = $catalogCandidate;
            }

            foreach ($this->retrieveArticleCandidates($trimmedQuery, $queryVector) as $articleCandidate) {
                $candidates[] = $articleCandidate;
            }
        }

        $ranked = collect($candidates)
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->all();

        if ($ranked === [] && ! $includeCatalogRecords) {
            $fallback = collect($chunks)->firstWhere('id', 'strict_boundaries');

            if ($fallback) {
                return [[
                    'source' => 'knowledge_base',
                    'id' => $fallback['id'],
                    'category' => $fallback['category'],
                    'title' => $fallback['title'],
                    'content' => $fallback['content'],
                    'official_links' => $fallback['official_links'],
                    'score' => 1,
                    'semantic_score' => 0,
                ]];
            }
        }

        return $ranked;
    }

    /**
     * Build structured title and content for a static knowledge chunk.
     *
     * @param  array{
     *     id: string,
     *     category: string,
     *     title: string,
     *     keywords: array<int, string>,
     *     content: string
     * }  $chunk
     * @return array{title: string, content: string}
     */
    public function chunkEmbeddingParts(array $chunk): array
    {
        $keywords = implode(', ', $chunk['keywords'] ?? []);
        $content = implode('. ', array_filter([
            'Chuyên mục: '.($chunk['category'] ?? 'Thông tin Showroom'),
            $chunk['content'] ?? '',
            $keywords !== '' ? 'Từ khóa liên quan: '.$keywords : null,
        ]));

        return [
            'title' => trim((string) ($chunk['title'] ?? '')),
            'content' => $content,
        ];
    }

    public function knowledgeEmbeddingsPath(): string
    {
        $configured = config('showroom_ai.embeddings.knowledge_embeddings_path');

        return is_string($configured) && trim($configured) !== ''
            ? $configured
            : storage_path('app/private/ai_knowledge_embeddings.json');
    }

    /**
     * @return array<string, array{hash: string, embedding: list<float>}>
     */
    public function loadKnowledgeEmbeddings(): array
    {
        $path = $this->knowledgeEmbeddingsPath();

        if (! File::exists($path) || ! File::isFile($path)) {
            return [];
        }

        $decoded = json_decode(File::get($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Synchronize embeddings for active Products and published Articles concurrently via Http::pool().
     *
     * @return array{
     *     products: array{0: int, 1: int, 2: int, 3: int},
     *     articles: array{0: int, 1: int, 2: int, 3: int}
     * }
     */
    public function syncDatabaseEmbeddings(
        ?CloudflareEmbeddingService $embeddingService = null,
        bool $force = false,
        int $batchSize = 10
    ): array {
        $service = $embeddingService ?? $this->embeddingService();

        $groups = [
            'products' => [
                'records' => Product::query()->active()->with('category')->orderBy('id')->get(),
                'extractor' => fn (Product $product): array => $product->toEmbeddingParts(),
            ],
            'articles' => [
                'records' => Article::query()->published()->orderBy('id')->get(),
                'extractor' => fn (Article $article): array => $article->toEmbeddingParts(),
            ],
        ];

        $stats = [];
        $batchMeta = [];
        $inputBatches = [];

        foreach ($groups as $groupKey => $group) {
            /** @var Collection<int, Model> $records */
            $records = $group['records'];
            $extractor = $group['extractor'];

            $total = $records->count();
            $skipped = 0;
            $pending = [];

            foreach ($records as $record) {
                $parts = $extractor($record);
                $content = trim((string) ($parts['content'] ?? ''));

                if ($content === '') {
                    $skipped++;

                    continue;
                }

                $formatted = $service->formatDocumentInput($content, $parts['title'] ?? null);
                $hash = $service->computeHash($formatted);
                $existing = $record->getAttribute('embedding');

                if (! $force
                    && $record->getAttribute('embedding_hash') === $hash
                    && is_array($existing)
                    && $existing !== []) {
                    $skipped++;

                    continue;
                }

                $pending[] = [
                    'model' => $record,
                    'formatted' => $formatted,
                    'hash' => $hash,
                ];
            }

            $stats[$groupKey] = [$total, 0, $skipped, 0];

            foreach (array_chunk($pending, max(1, $batchSize)) as $chunkIndex => $batchItems) {
                $batchKey = "{$groupKey}:{$chunkIndex}";
                $batchMeta[$batchKey] = [
                    'group' => $groupKey,
                    'items' => $batchItems,
                ];
                $inputBatches[$batchKey] = array_column($batchItems, 'formatted');
            }
        }

        if (! $service->isEnabled() || $inputBatches === []) {
            return [
                'products' => $stats['products'],
                'articles' => $stats['articles'],
            ];
        }

        $pooledResults = $service->embedInputBatchesConcurrently($inputBatches);

        foreach ($batchMeta as $batchKey => $meta) {
            $groupKey = $meta['group'];
            $vectors = $pooledResults[$batchKey] ?? null;

            foreach ($meta['items'] as $index => $item) {
                $vector = is_array($vectors) ? ($vectors[$index] ?? null) : null;

                if (! is_array($vector) || $vector === []) {
                    $stats[$groupKey][3]++;

                    continue;
                }

                $item['model']->forceFill([
                    'embedding' => $vector,
                    'embedding_hash' => $item['hash'],
                ])->saveQuietly();
                $stats[$groupKey][1]++;
            }
        }

        return [
            'products' => $stats['products'],
            'articles' => $stats['articles'],
        ];
    }

    private function embeddingService(): CloudflareEmbeddingService
    {
        return $this->embeddingService ??= app(CloudflareEmbeddingService::class);
    }

    /**
     * @param  list<float>|null  $queryVector
     */
    private function computeSemanticScore(?array $queryVector, mixed $itemEmbedding): int
    {
        if ($queryVector === null || ! is_array($itemEmbedding) || $itemEmbedding === []) {
            return 0;
        }

        $similarity = CloudflareEmbeddingService::cosineSimilarity($queryVector, $itemEmbedding);

        if ($similarity < $this->embeddingService()->minSimilarity()) {
            return 0;
        }

        return (int) round($similarity * $this->embeddingService()->semanticWeight());
    }

    /**
     * @param  array{
     *     id: string,
     *     category: string,
     *     title: string,
     *     keywords: array<int, string>,
     *     content: string,
     *     official_links: array<int, array{label: string, url: string}>
     * }  $chunk
     */
    private function scoreKnowledgeChunk(array $chunk, string $query): int
    {
        if ($query === '') {
            return 0;
        }

        $compactQuery = $this->matcher->normalizeSearchText($query);

        if ($compactQuery === '') {
            return 0;
        }

        $compactId = $this->matcher->normalizeSearchText($chunk['id']);
        $compactTitle = $this->matcher->normalizeSearchText($chunk['title']);
        $score = 0;

        if ($compactId !== '' && Str::contains($compactQuery, $compactId)) {
            $score += 750;
        }

        if ($compactTitle !== '' && (Str::contains($compactQuery, $compactTitle) || Str::contains($compactTitle, $compactQuery))) {
            $score += 650;
        }

        $keywordHits = 0;

        foreach ($chunk['keywords'] as $keyword) {
            $compactKeyword = $this->matcher->normalizeSearchText($keyword);

            if ($compactKeyword === '' || strlen($compactKeyword) < 2) {
                continue;
            }

            if (Str::contains($compactQuery, $compactKeyword)) {
                $score += 380;
                $keywordHits++;
            }
        }

        $queryTokens = array_values(array_diff(
            $this->matcher->tokenizeText($query),
            self::GENERIC_QUERY_TOKENS
        ));

        if ($queryTokens === []) {
            $queryTokens = $this->matcher->tokenizeText($query);
        }

        $chunkHaystack = implode(' ', [
            $chunk['category'],
            $chunk['title'],
            implode(' ', $chunk['keywords']),
            $chunk['content'],
        ]);
        $chunkTokens = $this->matcher->tokenizeText($chunkHaystack);
        $matchedTokens = array_intersect($queryTokens, $chunkTokens);

        if ($matchedTokens !== []) {
            $score += count($matchedTokens) * 140;
        }

        if ($keywordHits > 1) {
            $score += 120;
        }

        return $score;
    }

    /**
     * @param  list<float>|null  $queryVector
     * @return array<int, array{
     *     source: string,
     *     id: string,
     *     category: string,
     *     title: string,
     *     content: string,
     *     official_links: array<int, array{label: string, url: string}>,
     *     score: int,
     *     semantic_score: int
     * }>
     */
    private function retrieveCatalogCandidates(string $query, ?array $queryVector = null): array
    {
        $columns = [
            'id',
            'category_id',
            'name',
            'slug',
            'type',
            'price',
            'deposit_amount',
            'specifications',
            'description',
        ];

        if ($queryVector !== null) {
            $columns[] = 'embedding';
        }

        return Product::query()
            ->active()
            ->with('category')
            ->select($columns)
            ->get()
            ->map(function (Product $product) use ($query, $queryVector): array {
                $lexicalScore = $this->matcher->scoreProductQuery($product, $query, false, true);
                $semanticScore = $queryVector !== null
                    ? $this->computeSemanticScore($queryVector, $product->embedding)
                    : 0;

                return [
                    'product' => $product,
                    'score' => $lexicalScore + $semanticScore,
                    'semantic_score' => $semanticScore,
                ];
            })
            ->filter(fn (array $entry): bool => $entry['score'] >= 320)
            ->sortByDesc('score')
            ->take(2)
            ->map(function (array $entry): array {
                /** @var Product $product */
                $product = $entry['product'];
                $formatted = $this->matcher->formatProductForTool($product, 4);
                $priceText = $formatted['price_vnd'] ?? 'Liên hệ showroom';
                $specsText = collect($formatted['specs'] ?? [])
                    ->map(fn (string $value, string $key): string => "{$key}: {$value}")
                    ->implode('; ');
                $links = [
                    ['label' => 'Xem '.$product->name, 'url' => $formatted['url']],
                ];

                if (filled($formatted['booking_test_drive_url'])) {
                    $links[] = ['label' => 'Đặt lịch lái thử', 'url' => $formatted['booking_test_drive_url']];
                } elseif (filled($formatted['accessory_order_url'])) {
                    $links[] = ['label' => 'Đặt phụ kiện', 'url' => $formatted['accessory_order_url']];
                }

                return [
                    'source' => 'catalog',
                    'id' => 'product:'.$product->slug,
                    'category' => $formatted['product_line'],
                    'title' => $product->name,
                    'content' => trim("Giá niêm yết: {$priceText}. ".($specsText !== '' ? "Thông số chính: {$specsText}. " : '').($formatted['description'] ?? '')),
                    'official_links' => $links,
                    'score' => $entry['score'],
                    'semantic_score' => $entry['semantic_score'],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<float>|null  $queryVector
     * @return array<int, array{
     *     source: string,
     *     id: string,
     *     category: string,
     *     title: string,
     *     content: string,
     *     official_links: array<int, array{label: string, url: string}>,
     *     score: int,
     *     semantic_score: int
     * }>
     */
    private function retrieveArticleCandidates(string $query, ?array $queryVector = null): array
    {
        $columns = ['id', 'title', 'slug', 'category', 'excerpt', 'body', 'published_at'];

        if ($queryVector !== null) {
            $columns[] = 'embedding';
        }

        return Article::query()
            ->published()
            ->select($columns)
            ->latest('published_at')
            ->get()
            ->map(function (Article $article) use ($query, $queryVector): array {
                $lexicalScore = $this->matcher->scoreArticleQuery($article, $query);
                $semanticScore = $queryVector !== null
                    ? $this->computeSemanticScore($queryVector, $article->embedding)
                    : 0;

                return [
                    'article' => $article,
                    'score' => $lexicalScore + $semanticScore,
                    'semantic_score' => $semanticScore,
                ];
            })
            ->filter(fn (array $entry): bool => $entry['score'] >= 300)
            ->sortByDesc('score')
            ->take(2)
            ->map(function (array $entry): array {
                /** @var Article $article */
                $article = $entry['article'];

                return [
                    'source' => 'article',
                    'id' => 'article:'.$article->slug,
                    'category' => $article->categoryLabel(),
                    'title' => $article->title,
                    'content' => $this->matcher->compactText($article->excerpt ?: $article->body, 320) ?? '',
                    'official_links' => [
                        ['label' => 'Đọc bài viết', 'url' => route('articles.show', $article->slug, false)],
                    ],
                    'score' => $entry['score'],
                    'semantic_score' => $entry['semantic_score'],
                ];
            })
            ->values()
            ->all();
    }
}
