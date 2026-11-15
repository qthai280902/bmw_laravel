<?php

namespace Tests\Feature;

use App\Ai\Tools\SearchCatalogTool;
use App\Ai\Tools\SearchKnowledgeBaseTool;
use App\Enums\VehicleType;
use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Services\Ai\CloudflareEmbeddingService;
use App\Services\Ai\ShowroomCatalogMatcher;
use App\Services\Ai\ShowroomKnowledgeBaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\Request as ToolRequest;
use Tests\TestCase;

class Phase24SemanticRagEmbeddingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cloudflare_embedding_service_formats_payload_caches_queries_and_fails_over_across_accounts(): void
    {
        Cache::flush();

        config([
            'showroom_ai.embeddings.enabled' => true,
            'showroom_ai.embeddings.accounts_list' => 'acc-primary:tok-primary,acc-backup:tok-backup',
            'showroom_ai.embeddings.numbered_accounts' => [],
            'showroom_ai.embeddings.account_id' => null,
            'showroom_ai.embeddings.api_token' => null,
            'showroom_ai.embeddings.model' => '@cf/google/embeddinggemma-2',
            'showroom_ai.embeddings.dimensions' => 768,
            'showroom_ai.embeddings.query_cache_ttl_seconds' => 900,
        ]);

        // Test mathematical edge cases of cosineSimilarity()
        $this->assertEqualsWithDelta(1.0, CloudflareEmbeddingService::cosineSimilarity([1, 0, 0], [1, 0, 0]), 0.0001);
        $this->assertEqualsWithDelta(0.0, CloudflareEmbeddingService::cosineSimilarity([1, 0, 0], [0, 1, 0]), 0.0001);
        $this->assertSame(0.0, CloudflareEmbeddingService::cosineSimilarity([], []));
        $this->assertSame(0.0, CloudflareEmbeddingService::cosineSimilarity([1, 0], [1, 0, 0]));
        $this->assertSame(0.0, CloudflareEmbeddingService::cosineSimilarity([0, 0], [1, 1]));

        Http::fake([
            'https://api.cloudflare.com/client/v4/accounts/acc-primary/ai/run/*' => Http::response(['success' => false], 429),
            'https://api.cloudflare.com/client/v4/accounts/acc-backup/ai/run/*' => function (HttpRequest $request) {
                $inputs = $request['input'] ?? [];
                $firstInput = (string) ($inputs[0] ?? '');

                $vector = str_contains($firstInput, 'task: search result | query:')
                    ? [0.9, 0.1, 0.0]
                    : [0.4, 0.5, 0.1];

                return Http::response([
                    'success' => true,
                    'result' => [
                        'data' => [$vector],
                        'shape' => [1, 3],
                    ],
                ], 200);
            },
        ]);

        $service = app(CloudflareEmbeddingService::class);
        $this->assertTrue($service->isEnabled());
        $this->assertCount(2, $service->accounts());

        // 1st query hits acc-primary (429), fails over to acc-backup (200)
        $queryVector = $service->embedQuery('Xe gầm cao gia đình');
        $this->assertSame([0.9, 0.1, 0.0], $queryVector);

        Http::assertSent(fn (HttpRequest $request): bool => str_contains($request->url(), 'acc-backup')
            && $request['input'] === ['task: search result | query: Xe gầm cao gia đình']
            && $request['dimensions'] === 768);

        // 2nd identical query uses 15-minute cache across fresh service instances (no HTTP calls)
        $freshInstance = new CloudflareEmbeddingService;
        $cachedVector = $freshInstance->embedQuery('Xe gầm cao gia đình');
        $this->assertSame([0.9, 0.1, 0.0], $cachedVector);
        Http::assertSentCount(2);

        // Document prefix verification (with & without title) + OpenAI-style Cloudflare response object (`data[i].embedding`)
        Http::fake([
            'https://api.cloudflare.com/client/v4/accounts/*' => Http::response([
                'success' => true,
                'result' => [
                    'object' => 'list',
                    'model' => '@cf/google/embeddinggemma-2',
                    'data' => [
                        ['index' => 0, 'object' => 'embedding', 'embedding' => [0.4, 0.5, 0.1]],
                    ],
                ],
            ], 200),
        ]);

        $docVecWithTitle = $service->embedDocument('Chi tiết mẫu xe', 'BMW Z4 Roadster');
        $this->assertSame([0.4, 0.5, 0.1], $docVecWithTitle);
        Http::assertSent(fn (HttpRequest $request): bool => $request['input'] === ['title: BMW Z4 Roadster | text: Chi tiết mẫu xe']);

        $docVecNoTitle = $service->embedDocument('Chỉ nội dung');
        $this->assertSame([0.4, 0.5, 0.1], $docVecNoTitle);
        Http::assertSent(fn (HttpRequest $request): bool => $request['input'] === ['title: none | text: Chỉ nội dung']);

        // Graceful fallback when disabled or HTTP server error occurs
        config(['showroom_ai.embeddings.enabled' => false]);
        $this->assertFalse($service->isEnabled());
        $this->assertNull($service->embedQuery('Xe gầm cao mới'));
        $this->assertNull($service->embedDocument('Nội dung mới'));
    }

    public function test_observers_auto_index_active_products_and_published_articles_with_dedupe_and_hidden_guard(): void
    {
        Cache::flush();

        config([
            'showroom_ai.embeddings.enabled' => true,
            'showroom_ai.embeddings.accounts_list' => 'acc-obs:tok-obs',
            'showroom_ai.embeddings.numbered_accounts' => [],
            'showroom_ai.embeddings.account_id' => null,
            'showroom_ai.embeddings.api_token' => null,
        ]);

        Http::fake([
            'https://api.cloudflare.com/client/v4/accounts/*' => Http::sequence()
                ->push(['success' => true, 'result' => ['data' => [[0.8, 0.6, 0.0]]]], 200)
                ->push(['success' => true, 'result' => ['data' => [[0.7, 0.7, 0.0]]]], 200)
                ->push(['success' => true, 'result' => ['data' => [[0.5, 0.5, 0.7]]]], 200)
                ->push(['success' => false], 500),
        ]);

        $category = Category::factory()->create(['name' => 'Roadster', 'slug' => 'roadster']);

        // Inactive product must NOT call embedding API
        $inactiveProduct = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'BMW Z4 Ẩn',
            'slug' => 'bmw-z4-an',
            'is_active' => false,
        ]);
        $this->assertNull($inactiveProduct->embedding);
        Http::assertNothingSent();

        // Draft and scheduled articles must NOT call embedding API
        $draftArticle = Article::factory()->create([
            'title' => 'Bài nháp nội bộ',
            'status' => Article::STATUS_DRAFT,
            'published_at' => null,
        ]);
        $futureArticle = Article::factory()->create([
            'title' => 'Bài hẹn giờ tương lai',
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now()->addDays(2),
        ]);
        $this->assertNull($draftArticle->embedding);
        $this->assertNull($futureArticle->embedding);
        Http::assertNothingSent();

        // Active product auto-indexes & hides embedding fields from serialization
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'BMW Z4 Roadster',
            'slug' => 'bmw-z4-roadster',
            'type' => VehicleType::CAR,
            'price' => 3329000000,
            'is_active' => true,
            'description' => 'Mẫu xe hai chỗ mui trần thể thao.',
        ]);

        $product->refresh();
        $this->assertEquals([0.8, 0.6, 0.0], $product->embedding);
        $this->assertNotNull($product->embedding_hash);
        $this->assertSame(64, strlen((string) $product->embedding_hash));
        $this->assertArrayNotHasKey('embedding', $product->toArray());
        $this->assertArrayNotHasKey('embedding_hash', $product->toArray());
        Http::assertSentCount(1);

        // Updating unrelated field (stock) keeps hash identical => SHA-256 deduplication skips HTTP
        $product->update(['stock' => 7]);
        Http::assertSentCount(1);

        // Updating product name changes hash => triggers new embedding call
        $originalHash = $product->embedding_hash;
        $product->update(['name' => 'BMW Z4 M40i Roadster']);
        $product->refresh();
        $this->assertEquals([0.7, 0.7, 0.0], $product->embedding);
        $this->assertNotSame($originalHash, $product->embedding_hash);
        Http::assertSentCount(2);

        // Published article auto-indexes & hides embedding fields
        $article = Article::factory()->create([
            'title' => 'Trải nghiệm xe mui trần BMW',
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now()->subHour(),
            'body' => 'Cảm giác lái phấn khích trên cung đường ven biển.',
        ]);
        $article->refresh();
        $this->assertSame([0.5, 0.5, 0.7], $article->embedding);
        $this->assertArrayNotHasKey('embedding', $article->toArray());
        $this->assertArrayNotHasKey('embedding_hash', $article->toArray());
        Http::assertSentCount(3);

        // Cloudflare 500 failure must NEVER fail Admin CRUD save
        $product->update(['price' => 3499000000]);
        $product->refresh();
        $this->assertSame(3499000000, $product->price);
        $this->assertEquals([0.7, 0.7, 0.0], $product->embedding);
    }

    public function test_sync_ai_embeddings_command_syncs_and_respects_hash_and_force(): void
    {
        Cache::flush();
        $cachePath = storage_path('framework/testing/phase24-sync-knowledge.json');
        File::delete($cachePath);

        try {
            config([
                'showroom_ai.embeddings.enabled' => false,
                'showroom_ai.embeddings.knowledge_embeddings_path' => $cachePath,
            ]);

            // Command exits gracefully when embeddings disabled
            $this->artisan('ai:sync-embeddings')->assertSuccessful();
            $this->assertFileDoesNotExist($cachePath);

            // Seed data while embeddings are disabled
            $category = Category::factory()->create(['name' => 'SAV', 'slug' => 'sav']);
            $activeProduct = Product::factory()->create([
                'category_id' => $category->id,
                'name' => 'BMW X5 xDrive40i',
                'slug' => 'bmw-x5-xdrive40i',
                'is_active' => true,
            ]);
            $inactiveProduct = Product::factory()->create([
                'category_id' => $category->id,
                'name' => 'BMW X6 Ngừng Bán',
                'slug' => 'bmw-x6-stop',
                'is_active' => false,
            ]);
            $publishedArticle = Article::factory()->create([
                'title' => 'Ưu đãi dịch vụ bảo dưỡng BMW',
                'status' => Article::STATUS_PUBLISHED,
                'published_at' => now()->subDay(),
            ]);
            $draftArticle = Article::factory()->create([
                'title' => 'Bài nháp chưa xuất bản',
                'status' => Article::STATUS_DRAFT,
                'published_at' => null,
            ]);

            config([
                'showroom_ai.embeddings.enabled' => true,
                'showroom_ai.embeddings.accounts_list' => 'acc-sync:tok-sync',
                'showroom_ai.embeddings.numbered_accounts' => [],
                'showroom_ai.embeddings.account_id' => null,
                'showroom_ai.embeddings.api_token' => null,
            ]);

            Http::fake([
                'https://api.cloudflare.com/client/v4/accounts/*' => function (HttpRequest $request) {
                    $inputs = $request['input'] ?? [];
                    $vectors = [];
                    foreach ($inputs as $idx => $ignored) {
                        $vectors[] = [0.6, 0.8, (float) ($idx + 1) / 100];
                    }

                    return Http::response([
                        'success' => true,
                        'result' => ['data' => $vectors],
                    ], 200);
                },
            ]);

            // First sync: 3 batches (1 active product, 1 published article, 7 FAQ chunks)
            $this->artisan('ai:sync-embeddings')->assertSuccessful();

            $activeProduct->refresh();
            $inactiveProduct->refresh();
            $publishedArticle->refresh();
            $draftArticle->refresh();

            $this->assertIsArray($activeProduct->embedding);
            $this->assertNotNull($activeProduct->embedding_hash);
            $this->assertNull($inactiveProduct->embedding);

            $this->assertIsArray($publishedArticle->embedding);
            $this->assertNotNull($publishedArticle->embedding_hash);
            $this->assertNull($draftArticle->embedding);

            $this->assertFileExists($cachePath);
            $decodedCache = json_decode(File::get($cachePath), true);
            $this->assertArrayHasKey('warranty_policy', $decodedCache);
            $this->assertIsArray($decodedCache['warranty_policy']['embedding']);
            Http::assertSentCount(3);

            // Second sync without changes skips all records (0 HTTP requests)
            $this->artisan('ai:sync-embeddings')->assertSuccessful();
            Http::assertSentCount(3);

            // --force re-syncs all 3 groups
            $this->artisan('ai:sync-embeddings', ['--force' => true])->assertSuccessful();
            Http::assertSentCount(6);
        } finally {
            File::delete($cachePath);
        }
    }

    public function test_hybrid_rag_and_search_catalog_tool_retrieve_semantic_matches_and_fallback_cleanly(): void
    {
        Cache::flush();
        $cachePath = storage_path('framework/testing/phase24-hybrid-knowledge.json');
        File::delete($cachePath);

        try {
            config([
                'showroom_ai.embeddings.enabled' => true,
                'showroom_ai.embeddings.accounts_list' => 'acc-hybrid:tok-hybrid',
                'showroom_ai.embeddings.numbered_accounts' => [],
                'showroom_ai.embeddings.account_id' => null,
                'showroom_ai.embeddings.api_token' => null,
                'showroom_ai.embeddings.knowledge_embeddings_path' => $cachePath,
                'showroom_ai.embeddings.min_similarity' => 0.45,
                'showroom_ai.embeddings.semantic_weight' => 900,
            ]);

            File::ensureDirectoryExists(dirname($cachePath));
            File::put($cachePath, json_encode([
                'warranty_policy' => [
                    'hash' => 'faq-hash-1',
                    'embedding' => [0.0, 0.1, 0.99],
                ],
                // Even if strict_boundaries had vector [1, 0, 0], it MUST be excluded from cosine scoring
                'strict_boundaries' => [
                    'hash' => 'strict-hash',
                    'embedding' => [1.0, 0.0, 0.0],
                ],
            ]));

            // Create catalog & article data before setting Http::fake(), using direct DB embedding values
            $roadsterCategory = Category::factory()->create(['name' => 'Roadster', 'slug' => 'roadster']);
            $savCategory = Category::factory()->create(['name' => 'SAV', 'slug' => 'sav']);

            $z4 = Product::factory()->create([
                'category_id' => $roadsterCategory->id,
                'name' => 'BMW Z4 Roadster',
                'slug' => 'bmw-z4-roadster',
                'type' => VehicleType::CAR,
                'price' => 3329000000,
                'is_active' => true,
                'description' => 'Hai chỗ thể thao cổ điển.',
            ]);
            $z4->forceFill([
                'embedding' => [0.96, 0.04, 0.0],
                'embedding_hash' => str_repeat('a', 64),
            ])->saveQuietly();

            $x7 = Product::factory()->create([
                'category_id' => $savCategory->id,
                'name' => 'BMW X7 xDrive40i',
                'slug' => 'bmw-x7-xdrive40i',
                'type' => VehicleType::CAR,
                'price' => 5549000000,
                'is_active' => true,
                'description' => 'SAV 7 chỗ cỡ lớn.',
            ]);
            $x7->forceFill([
                'embedding' => [0.0, 0.99, 0.01],
                'embedding_hash' => str_repeat('b', 64),
            ])->saveQuietly();

            $roadsterArticle = Article::factory()->create([
                'title' => 'Hành trình ven biển cùng BMW Z4',
                'slug' => 'hanh-trinh-ven-bien-z4',
                'status' => Article::STATUS_PUBLISHED,
                'published_at' => now()->subHour(),
                'excerpt' => 'Trải nghiệm động cơ 6 xy-lanh mạnh mẽ.',
            ]);
            $roadsterArticle->forceFill([
                'embedding' => [0.94, 0.06, 0.0],
                'embedding_hash' => str_repeat('c', 64),
            ])->saveQuietly();

            Http::fake([
                'https://api.cloudflare.com/client/v4/accounts/*' => Http::response([
                    'success' => true,
                    'result' => [
                        'data' => [[0.97, 0.03, 0.0]],
                    ],
                ], 200),
            ]);

            // Query uses ZERO lexical tokens from BMW Z4 / article title ("mui trần", "mùa hè", "dạo phố")
            $semanticQuery = 'tìm mẫu xe hai cửa mui trần dạo phố mùa hè';
            $knowledgeBase = app(ShowroomKnowledgeBaseService::class);
            $ragChunks = $knowledgeBase->retrieveRelevantChunks($semanticQuery, 4, true);

            $ragIds = array_column($ragChunks, 'id');
            $this->assertContains('product:bmw-z4-roadster', $ragIds);
            $this->assertContains('article:hanh-trinh-ven-bien-z4', $ragIds);
            $this->assertNotContains('strict_boundaries', $ragIds);

            $z4Chunk = collect($ragChunks)->firstWhere('id', 'product:bmw-z4-roadster');
            $this->assertIsArray($z4Chunk);
            $this->assertGreaterThan(700, $z4Chunk['semantic_score']);

            // SearchKnowledgeBaseTool exposes semantic match end-to-end
            $kbTool = app(SearchKnowledgeBaseTool::class);
            $kbResult = json_decode((string) $kbTool->handle(new ToolRequest([
                'query' => $semanticQuery,
            ])), true);
            $this->assertSame('ok', $kbResult['status']);
            $this->assertContains('product:bmw-z4-roadster', array_column($kbResult['chunks'], 'id'));

            // SearchCatalogTool lexical search returns empty for "mui trần dạo phố mùa hè",
            // triggering semantic fallback that locates BMW Z4 Roadster accurately.
            $catalogTool = app(SearchCatalogTool::class);
            $catalogResult = json_decode((string) $catalogTool->handle(new ToolRequest([
                'keyword' => 'mui trần dạo phố mùa hè',
            ])), true);
            $this->assertSame('ok', $catalogResult['status']);
            $this->assertGreaterThan(0, $catalogResult['total_found']);
            $this->assertSame('bmw-z4-roadster', $catalogResult['items'][0]['slug']);

            // Verify legacy constructor compatibility (2 arguments) and lexical fallback when disabled
            config(['showroom_ai.embeddings.enabled' => false]);
            $legacyKb = new ShowroomKnowledgeBaseService(app(ShowroomCatalogMatcher::class));
            $lexicalOnly = $legacyKb->retrieveRelevantChunks('Chính sách bảo hành pin xe điện', 3, false);
            $this->assertNotEmpty($lexicalOnly);
            $this->assertTrue(collect($lexicalOnly)->contains(
                fn (array $item): bool => $item['id'] === 'warranty_policy' && $item['semantic_score'] === 0
            ));
        } finally {
            File::delete($cachePath);
        }
    }
}
