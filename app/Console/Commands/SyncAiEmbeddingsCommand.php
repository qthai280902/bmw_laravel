<?php

namespace App\Console\Commands;

use App\Services\Ai\CloudflareEmbeddingService;
use App\Services\Ai\ShowroomKnowledgeBaseService;
use Illuminate\Console\Command;
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

        $databaseStats = $knowledgeBase->syncDatabaseEmbeddings($embeddingService, $force, self::BATCH_SIZE);
        $knowledgeStats = $this->syncKnowledgeChunks($knowledgeBase, $embeddingService, $force);

        $this->table(
            ['Nhóm dữ liệu', 'Tổng', 'Đã đồng bộ', 'Bỏ qua (không đổi)', 'Thất bại'],
            [
                ['Sản phẩm active (products)', ...$databaseStats['products']],
                ['Bài viết published (articles)', ...$databaseStats['articles']],
                ['Tri thức tĩnh JSON (FAQ chunks)', ...$knowledgeStats],
            ]
        );

        return self::SUCCESS;
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
                'formatted' => $formatted,
                'hash' => $hash,
            ];
        }

        if ($pending !== []) {
            $batchMeta = [];
            $inputBatches = [];

            foreach (array_chunk($pending, self::BATCH_SIZE) as $chunkIndex => $batch) {
                $batchKey = "faq:{$chunkIndex}";
                $batchMeta[$batchKey] = $batch;
                $inputBatches[$batchKey] = array_column($batch, 'formatted');
            }

            $pooledResults = $embeddingService->embedInputBatchesConcurrently($inputBatches);

            foreach ($batchMeta as $batchKey => $batch) {
                $vectors = $pooledResults[$batchKey] ?? null;

                foreach ($batch as $index => $item) {
                    $vector = is_array($vectors) ? ($vectors[$index] ?? null) : null;

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
