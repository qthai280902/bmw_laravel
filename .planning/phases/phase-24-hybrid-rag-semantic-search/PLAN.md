# Phase 24 Plan: Nâng Cấp Hybrid RAG Semantic Search Với Cloudflare Workers AI (`@cf/google/embeddinggemma-2`)

## Objective
Nâng cấp `ShowroomKnowledgeBaseService` và `SearchCatalogTool` từ tìm kiếm dựa trên từ khóa thuần túy sang kiến trúc **Hybrid Search (Vector Cosine Similarity + Lexical/Synonym Matching)** dùng mô hình `@cf/google/embeddinggemma-2` (768 chiều) trên **Cloudflare Workers AI**, hỗ trợ xoay vòng/failover 2 tài khoản Cloudflare, tự động đồng bộ qua Observers (`ProductObserver`, `ArticleObserver`), lệnh CLI `ai:sync-embeddings {--force}`, và Fallback tuyệt đối khi mất mạng hoặc thiếu cấu hình.

## Architecture & Changes
1. **Configuration & Isolation**:
   - `.env` (local), `.env.example`, `config/showroom_ai.php`: cấu hình nhóm `embeddings` hỗ trợ `CLOUDFLARE_AI_ACCOUNTS`, `CLOUDFLARE_ACCOUNT_ID_1/2` + `CLOUDFLARE_API_TOKEN_1/2` và tương thích ngược `CLOUDFLARE_ACCOUNT_ID` + `CLOUDFLARE_API_TOKEN`.
   - `phpunit.xml`: thêm `<env name="AI_EMBEDDINGS_ENABLED" value="false"/>` cô lập hoàn toàn test suite mặc định.
2. **Migration & Models**:
   - `database/migrations/2026_11_15_000001_add_embedding_columns_to_products_and_articles_tables.php`: bổ sung `embedding` (`json`, nullable) và `embedding_hash` (`string(64)`, nullable) cho `products` và `articles`.
   - `Product`, `Article`: ẩn `embedding` & `embedding_hash` qua `$hidden`, cast `'embedding' => 'array'` (và `'is_active' => 'boolean'` trên `Product`), thêm `toEmbeddingParts()` (tải `loadMissing('category')` trên `Product`).
3. **Vector Engine, Observers & Sync CLI**:
   - `App\Services\Ai\CloudflareEmbeddingService`: Task Prefixes (`title: ... | text: ...` và `task: search result | query: ...`), request JSON `{"input": [...], "dimensions": 768}`, parser hỗ trợ cả định dạng OpenAI-style `result.data[i].embedding` thực tế của `@cf/google/embeddinggemma-2` lẫn `result.data[i]`, xoay vòng & failover tự động (`429`/`401`/`403`/`5xx`/exception), query cache 15 phút, `cosineSimilarity()`, `syncModel()`.
   - `ProductObserver`, `ArticleObserver` đăng ký tại `AppServiceProvider`: chỉ index sản phẩm `is_active === true` và bài viết `isPublished()`, chống trùng lặp bằng SHA-256 `embedding_hash`, `saveQuietly()` trong `try/catch`.
   - `SyncAiEmbeddingsCommand` (`php artisan ai:sync-embeddings {--force}`): batch sync cho active products, published articles và `bmw_showroom_knowledge.json` → `storage/app/private/ai_knowledge_embeddings.json`.
4. **Hybrid RAG & Catalog Tool Fallback**:
   - `ShowroomKnowledgeBaseService`: kết hợp `lexicalScore + semanticScore`, loại trừ `strict_boundaries` khỏi chấm điểm cosine, giữ tương thích constructor 2 tham số.
   - `SearchCatalogTool`: semantic fallback bằng `cosineSimilarity()` khi lexical search có `keyword` trả về rỗng.
5. **Verification**:
   - `tests/Feature/Phase24SemanticRagEmbeddingTest.php` (`Http::fake()`), Pint, full test suite (134 tests), migrate và sync thực tế lên local DB.
