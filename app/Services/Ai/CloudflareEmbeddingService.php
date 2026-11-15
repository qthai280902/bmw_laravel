<?php

namespace App\Services\Ai;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CloudflareEmbeddingService
{
    private const ROTATION_CACHE_KEY = 'showroom_ai:cloudflare_embedding_cursor';

    private const COOLDOWN_PREFIX = 'showroom_ai:cloudflare_embedding_cooldown:';

    private const QUERY_CACHE_PREFIX = 'showroom_ai:cloudflare_query_embedding:';

    /**
     * @var array<string, list<float>>
     */
    private array $queryMemo = [];

    public function isEnabled(): bool
    {
        return (bool) config('showroom_ai.embeddings.enabled', true)
            && $this->accounts() !== [];
    }

    public function model(): string
    {
        $model = trim((string) config('showroom_ai.embeddings.model', '@cf/google/embeddinggemma-2'));

        return $model !== '' ? $model : '@cf/google/embeddinggemma-2';
    }

    public function dimensions(): int
    {
        $dimensions = (int) config('showroom_ai.embeddings.dimensions', 768);

        return $dimensions > 0 ? $dimensions : 768;
    }

    public function minSimilarity(): float
    {
        $min = (float) config('showroom_ai.embeddings.min_similarity', 0.42);

        return max(0.0, min(1.0, $min));
    }

    public function semanticWeight(): int
    {
        return max(100, (int) config('showroom_ai.embeddings.semantic_weight', 900));
    }

    /**
     * @return list<array{account_id: string, api_token: string, fingerprint: string}>
     */
    public function accounts(): array
    {
        $pairs = [];

        $rawList = config('showroom_ai.embeddings.accounts_list');
        if (is_string($rawList) && trim($rawList) !== '') {
            $entries = preg_split('/[\r\n,;]+/', $rawList) ?: [];
            foreach ($entries as $entry) {
                $entry = trim($entry);
                if ($entry === '' || ! str_contains($entry, ':')) {
                    continue;
                }

                [$accountId, $apiToken] = array_pad(explode(':', $entry, 2), 2, '');
                $pairs[] = [
                    'account_id' => trim($accountId),
                    'api_token' => trim($apiToken),
                ];
            }
        }

        $numbered = config('showroom_ai.embeddings.numbered_accounts', []);
        if (is_array($numbered)) {
            foreach ($numbered as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $pairs[] = [
                    'account_id' => trim((string) ($item['account_id'] ?? '')),
                    'api_token' => trim((string) ($item['api_token'] ?? '')),
                ];
            }
        }

        $singleAccountId = trim((string) config('showroom_ai.embeddings.account_id', ''));
        $singleApiToken = trim((string) config('showroom_ai.embeddings.api_token', ''));
        if ($singleAccountId !== '' && $singleApiToken !== '') {
            $pairs[] = [
                'account_id' => $singleAccountId,
                'api_token' => $singleApiToken,
            ];
        }

        $unique = [];
        foreach ($pairs as $pair) {
            if ($pair['account_id'] === '' || $pair['api_token'] === '') {
                continue;
            }

            $dedupeKey = $pair['account_id'].':'.$pair['api_token'];
            if (isset($unique[$dedupeKey])) {
                continue;
            }

            $unique[$dedupeKey] = [
                'account_id' => $pair['account_id'],
                'api_token' => $pair['api_token'],
                'fingerprint' => substr(sha1($dedupeKey), 0, 12),
            ];
        }

        return array_values($unique);
    }

    public function formatDocumentInput(string $content, ?string $title = null): string
    {
        $cleanTitle = preg_replace('/\s+/', ' ', trim((string) ($title ?? ''))) ?? '';
        $cleanContent = preg_replace('/\s+/', ' ', trim($content)) ?? '';
        $resolvedTitle = $cleanTitle !== '' ? $cleanTitle : 'none';

        return "title: {$resolvedTitle} | text: {$cleanContent}";
    }

    public function formatQueryInput(string $query): string
    {
        $cleanQuery = preg_replace('/\s+/', ' ', trim($query)) ?? '';

        return "task: search result | query: {$cleanQuery}";
    }

    public function computeHash(string $formattedInput): string
    {
        return hash('sha256', "{$this->model()}|{$this->dimensions()}|{$formattedInput}");
    }

    /**
     * @return list<float>|null
     */
    public function embedQuery(string $query): ?array
    {
        $trimmed = trim($query);
        if ($trimmed === '' || ! $this->isEnabled()) {
            return null;
        }

        $formatted = $this->formatQueryInput($trimmed);
        $hash = $this->computeHash($formatted);

        if (isset($this->queryMemo[$hash])) {
            return $this->queryMemo[$hash];
        }

        $cacheKey = self::QUERY_CACHE_PREFIX.$hash;
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && $cached !== []) {
            $normalized = $this->normalizeVector($cached);
            if ($normalized !== null) {
                return $this->queryMemo[$hash] = $normalized;
            }
        }

        $vectors = $this->requestEmbeddings([$formatted]);
        $vector = $vectors[0] ?? null;

        if ($vector !== null) {
            $this->queryMemo[$hash] = $vector;
            $ttl = max(60, (int) config('showroom_ai.embeddings.query_cache_ttl_seconds', 900));
            Cache::put($cacheKey, $vector, now()->addSeconds($ttl));
        }

        return $vector;
    }

    /**
     * @return list<float>|null
     */
    public function embedDocument(string $content, ?string $title = null): ?array
    {
        $trimmedContent = trim($content);
        if ($trimmedContent === '' || ! $this->isEnabled()) {
            return null;
        }

        $results = $this->embedDocuments([[
            'title' => $title,
            'content' => $trimmedContent,
        ]]);

        return $results[0] ?? null;
    }

    /**
     * @param  array<int, array{title?: string|null, content: string}>  $documents
     * @return array<int, list<float>|null>
     */
    public function embedDocuments(array $documents): array
    {
        $results = [];
        $inputs = [];
        $indexMap = [];

        foreach ($documents as $index => $doc) {
            $results[$index] = null;
            $content = trim((string) ($doc['content'] ?? ''));

            if ($content === '') {
                continue;
            }

            $inputs[] = $this->formatDocumentInput(
                $content,
                isset($doc['title']) ? (string) $doc['title'] : null
            );
            $indexMap[] = $index;
        }

        if ($inputs === [] || ! $this->isEnabled()) {
            return $results;
        }

        $vectors = $this->requestEmbeddings($inputs);
        if ($vectors === null) {
            return $results;
        }

        foreach ($indexMap as $position => $originalIndex) {
            $results[$originalIndex] = $vectors[$position] ?? null;
        }

        return $results;
    }

    /**
     * Compute and persist embedding for an Eloquent model if its content hash changed.
     */
    public function syncModel(Model $model, string $content, ?string $title = null, bool $force = false): string
    {
        if (! $this->isEnabled() || trim($content) === '') {
            return 'skipped';
        }

        $formatted = $this->formatDocumentInput($content, $title);
        $hash = $this->computeHash($formatted);
        $existingEmbedding = $model->getAttribute('embedding');

        if (! $force
            && $model->getAttribute('embedding_hash') === $hash
            && is_array($existingEmbedding)
            && $existingEmbedding !== []) {
            return 'skipped';
        }

        try {
            $vector = $this->embedDocument($content, $title);

            if ($vector === null) {
                return 'failed';
            }

            $model->forceFill([
                'embedding' => $vector,
                'embedding_hash' => $hash,
            ])->saveQuietly();

            return 'synced';
        } catch (Throwable $exception) {
            Log::warning('Failed to persist model embedding.', [
                'model' => $model::class,
                'id' => $model->getKey(),
                'message' => $exception->getMessage(),
            ]);

            return 'failed';
        }
    }

    /**
     * @param  array<int, float|int>  $vectorA
     * @param  array<int, float|int>  $vectorB
     */
    public static function cosineSimilarity(array $vectorA, array $vectorB): float
    {
        $count = count($vectorA);

        if ($count === 0 || $count !== count($vectorB)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $count; $i++) {
            $a = $vectorA[$i] ?? null;
            $b = $vectorB[$i] ?? null;

            if (! is_numeric($a) || ! is_numeric($b)) {
                return 0.0;
            }

            $floatA = (float) $a;
            $floatB = (float) $b;
            $dotProduct += $floatA * $floatB;
            $normA += $floatA * $floatA;
            $normB += $floatB * $floatB;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        $similarity = $dotProduct / (sqrt($normA) * sqrt($normB));

        return max(-1.0, min(1.0, $similarity));
    }

    /**
     * @param  list<string>  $inputs
     * @return list<list<float>|null>|null
     */
    private function requestEmbeddings(array $inputs): ?array
    {
        $candidates = $this->candidateSequence();

        if ($candidates === []) {
            return null;
        }

        $model = $this->model();
        $dimensions = $this->dimensions();
        $timeout = max(1, (int) config('showroom_ai.embeddings.timeout_seconds', 8));

        foreach ($candidates as $account) {
            $endpoint = sprintf(
                'https://api.cloudflare.com/client/v4/accounts/%s/ai/run/%s',
                $account['account_id'],
                $model
            );

            try {
                /** @var Response $response */
                $response = Http::withToken($account['api_token'])
                    ->acceptJson()
                    ->asJson()
                    ->timeout($timeout)
                    ->post($endpoint, [
                        'input' => array_values($inputs),
                        'dimensions' => $dimensions,
                    ]);
            } catch (Throwable $exception) {
                Log::warning('Cloudflare Workers AI embedding request exception.', [
                    'account_fingerprint' => $account['fingerprint'],
                    'model' => $model,
                    'message' => $exception->getMessage(),
                ]);
                $this->markCoolingDown($account['fingerprint']);

                continue;
            }

            if ($this->shouldFailoverResponse($response)) {
                Log::warning('Cloudflare Workers AI embedding failover triggered.', [
                    'account_fingerprint' => $account['fingerprint'],
                    'model' => $model,
                    'status' => $response->status(),
                ]);
                $this->markCoolingDown($account['fingerprint']);

                continue;
            }

            if (! $response->successful()) {
                Log::warning('Cloudflare Workers AI embedding non-success HTTP status.', [
                    'account_fingerprint' => $account['fingerprint'],
                    'model' => $model,
                    'status' => $response->status(),
                ]);

                continue;
            }

            $parsed = $this->extractVectorsFromResponse($response, count($inputs));
            if ($parsed !== null) {
                return $parsed;
            }

            Log::warning('Cloudflare Workers AI returned malformed embedding payload.', [
                'account_fingerprint' => $account['fingerprint'],
                'model' => $model,
            ]);
        }

        return null;
    }

    /**
     * @return list<array{account_id: string, api_token: string, fingerprint: string}>
     */
    private function candidateSequence(): array
    {
        $available = collect($this->accounts())
            ->reject(fn (array $account): bool => $this->isCoolingDown($account['fingerprint']))
            ->values()
            ->all();

        if ($available === []) {
            return [];
        }

        $cursor = (int) Cache::get(self::ROTATION_CACHE_KEY, 0);
        Cache::put(self::ROTATION_CACHE_KEY, $cursor + 1, now()->addDay());
        $start = $cursor % count($available);

        return array_values(array_merge(
            array_slice($available, $start),
            array_slice($available, 0, $start),
        ));
    }

    private function shouldFailoverResponse(Response $response): bool
    {
        $status = $response->status();

        return $status === 429 || $status === 401 || $status === 403 || $status >= 500;
    }

    private function markCoolingDown(string $fingerprint): void
    {
        $seconds = max(10, (int) config('showroom_ai.embeddings.cooldown_seconds', 120));
        Cache::put(self::COOLDOWN_PREFIX.$fingerprint, true, now()->addSeconds($seconds));
    }

    private function isCoolingDown(string $fingerprint): bool
    {
        return (bool) Cache::get(self::COOLDOWN_PREFIX.$fingerprint, false);
    }

    /**
     * Extract embedding vectors supporting both OpenAI-style objects (`data[i].embedding`)
     * and flat vector arrays (`result.data[i]`).
     *
     * @return list<list<float>|null>|null
     */
    private function extractVectorsFromResponse(Response $response, int $expectedCount): ?array
    {
        $rawData = $response->json('result.data');

        if (! is_array($rawData)) {
            $rawData = $response->json('data');
        }

        if (! is_array($rawData) || count($rawData) === 0) {
            return null;
        }

        $vectors = array_fill(0, $expectedCount, null);

        foreach ($rawData as $position => $item) {
            if (! is_array($item)) {
                continue;
            }

            if (array_key_exists('embedding', $item) && is_array($item['embedding'])) {
                $targetIndex = isset($item['index']) && is_numeric($item['index'])
                    ? (int) $item['index']
                    : (int) $position;

                if ($targetIndex >= 0 && $targetIndex < $expectedCount) {
                    $vectors[$targetIndex] = $this->normalizeVector($item['embedding']);
                }

                continue;
            }

            if (is_int($position) && $position >= 0 && $position < $expectedCount) {
                $vectors[$position] = $this->normalizeVector($item);
            }
        }

        $hasAnyValid = collect($vectors)->contains(fn (mixed $vector): bool => is_array($vector) && $vector !== []);

        return $hasAnyValid ? $vectors : null;
    }

    /**
     * @param  array<int, mixed>  $rawVector
     * @return list<float>|null
     */
    private function normalizeVector(array $rawVector): ?array
    {
        if ($rawVector === []) {
            return null;
        }

        $normalized = [];
        foreach ($rawVector as $value) {
            if (! is_numeric($value)) {
                return null;
            }

            $normalized[] = (float) $value;
        }

        return $normalized;
    }
}
