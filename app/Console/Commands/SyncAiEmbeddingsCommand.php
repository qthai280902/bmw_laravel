<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Product;
use App\Services\Ai\CloudflareEmbeddingService;
use App\Services\Ai\ShowroomKnowledgeBaseService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class SyncAiEmbeddingsCommand extends Command
{
    protected $signature = 'ai:sync-embeddings {--force : Tái tạo lại toàn bộ vector bất chấp embedding_hash}';

    protected $description = 'Đồng bộ vector embedding ngữ nghĩa (@cf/google/embeddinggemma-2) cho Sản phẩm, Bài viết và Tri thức RAG Showroom';

    private const BATCH_SIZE = 10;

    public function handle(
        CloudflareEmbeddingService $embeddingService,
        ShowroomKnowledgeBaseService $knowledgeBase,
    ): int {
        if (! $embeddingService->isEnabled()) {
            $this->warn('Cloudflare Workers AI Embeddings hiện đang tắt hoặc thiếu cấu hình CLOUDFLARE_ACCOUNT_ID / CLOUDFLARE_API_TOKEN.');

            return self::SUCCESS;
        }

        $force = (bool) $this->option('force');
        $this->info(sprintf(
            'Bắt đầu đồng bộ embeddings (%s, %d dims, %d tài khoản Cloudflare)...',
            $embeddingService->model(),
            $embeddingService->dimensions(),
            count($embeddingService->accounts())
        ));

        $products = Product::query()->active()->with('category')->orderBy('id')->get();
        $productStats = $this->syncEloquentCollection(
            $products,
            fn (Product $product): array => $product->toEmbeddingParts(),
            $embeddingService,
            $force,
        );

        $articles = Article::query()->published()->orderBy('id')->get();
        $articleStats = $this->syncEloquentCollection(
            $articles,
            fn (Article $article): array => $article->toEmbeddingParts(),
            $embeddingService,
            $force,
        );

        $knowledgeStats = $this->syncKnowledgeChunks($knowledgeBase, $embeddingService, $force);

        $this->table(
            ['Nhóm dữ liệu', 'Tổng', 'Đã đồng bộ', 'Bỏ qua (không đổi)', 'Thất bại'],
            [
                ['Sản phẩm active (products)', ...$productStats],
                ['Bài viết published (articles)', ...$articleStats],
                ['Tri thức tĩnh JSON (FAQ chunks)', ...$knowledgeStats],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * @template TModel of Model
     *
     * @param  Collection<int, TModel>  $records
     * @param  callable(TModel): array{title: string, content: string}  $extractor
     * @return array{0: int, 1: int, 2: int, 3: int}
     */
    private function syncEloquentCollection(
        Collection $records,
        callable $extractor,
        CloudflareEmbeddingService $embeddingService,
        bool $force,
    ): array {
        $total = $records->count();
        $synced = 0;
        $skipped = 0;
        $failed = 0;
        $pending = [];

        foreach ($records as $record) {
            $parts = $extractor($record);
            $content = trim($parts['content'] ?? '');

            if ($content === '') {
                $skipped++;

                continue;
            }

            $formatted = $embeddingService->formatDocumentInput($content, $parts['title'] ?? null);
            $hash = $embeddingService->computeHash($formatted);
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
                'title' => $parts['title'] ?? null,
                'content' => $content,
                'hash' => $hash,
            ];
        }

        foreach (array_chunk($pending, self::BATCH_SIZE) as $batch) {
            $vectors = $embeddingService->embedDocuments(
                array_map(fn (array $item): array => [
                    'title' => $item['title'],
                    'content' => $item['content'],
                ], $batch)
            );

            foreach ($batch as $index => $item) {
                $vector = $vectors[$index] ?? null;

                if (! is_array($vector) || $vector === []) {
                    $failed++;

                    continue;
                }

                $item['model']->forceFill([
                    'embedding' => $vector,
                    'embedding_hash' => $item['hash'],
                ])->saveQuietly();
                $synced++;
            }
        }

        return [$total, $synced, $skipped, $failed];
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: int}
     */
    private function syncKnowledgeChunks(
        ShowroomKnowledgeBaseService $knowledgeBase,
        CloudflareEmbeddingService $embeddingService,
        bool $force,
    ): array {
        $chunks = $knowledgeBase->knowledgeChunks();
        $existingCache = $knowledgeBase->loadKnowledgeEmbeddings();
        $total = count($chunks);
        $synced = 0;
        $skipped = 0;
        $failed = 0;
        $pending = [];
        $nextCache = $existingCache;

        foreach ($chunks as $chunk) {
            $id = $chunk['id'];
            $parts = $knowledgeBase->chunkEmbeddingParts($chunk);
            $formatted = $embeddingService->formatDocumentInput($parts['content'], $parts['title']);
            $hash = $embeddingService->computeHash($formatted);
            $cachedEntry = $existingCache[$id] ?? null;

            if (! $force
                && is_array($cachedEntry)
                && ($cachedEntry['hash'] ?? null) === $hash
                && is_array($cachedEntry['embedding'] ?? null)
                && $cachedEntry['embedding'] !== []) {
                $skipped++;

                continue;
            }

            $pending[] = [
                'id' => $id,
                'title' => $parts['title'],
                'content' => $parts['content'],
                'hash' => $hash,
            ];
        }

        foreach (array_chunk($pending, self::BATCH_SIZE) as $batch) {
            $vectors = $embeddingService->embedDocuments(
                array_map(fn (array $item): array => [
                    'title' => $item['title'],
                    'content' => $item['content'],
                ], $batch)
            );

            foreach ($batch as $index => $item) {
                $vector = $vectors[$index] ?? null;

                if (! is_array($vector) || $vector === []) {
                    $failed++;

                    continue;
                }

                $nextCache[$item['id']] = [
                    'hash' => $item['hash'],
                    'embedding' => $vector,
                ];
                $synced++;
            }
        }

        $path = $knowledgeBase->knowledgeEmbeddingsPath();
        File::ensureDirectoryExists(dirname($path));
        File::put(
            $path,
            json_encode($nextCache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}'
        );

        return [$total, $synced, $skipped, $failed];
    }
}
