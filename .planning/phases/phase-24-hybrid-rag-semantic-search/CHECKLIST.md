# Phase 24 Checklist

- [x] Cập nhật `.env`, `.env.example`, `config/showroom_ai.php` (đa tài khoản Cloudflare Workers AI xoay vòng/failover) và `phpunit.xml` (`AI_EMBEDDINGS_ENABLED=false`).
- [x] Tạo migration `2026_11_15_000001_add_embedding_columns_to_products_and_articles_tables.php` cho `products` và `articles`.
- [x] Cập nhật `Product` & `Article` (`$hidden`, `casts()`, `toEmbeddingParts()` với `$this->loadMissing('category')`).
- [x] Tạo `App\Services\Ai\CloudflareEmbeddingService` với Task Prefixes chuẩn `@cf/google/embeddinggemma-2`, parser `result.data[].embedding`, round-robin failover đa tài khoản, cache 15 phút, `cosineSimilarity()`, `syncModel()`.
- [x] Tạo `ProductObserver` & `ArticleObserver` (lọc `is_active === true` / `isPublished()`, SHA-256 `embedding_hash` dedupe, `saveQuietly()`, `try/catch`), đăng ký trong `AppServiceProvider`.
- [x] Nâng cấp `ShowroomKnowledgeBaseService` (Hybrid score `lexical + semantic`, loại trừ `strict_boundaries`) và `SearchCatalogTool` (semantic fallback).
- [x] Tạo `SyncAiEmbeddingsCommand` (`ai:sync-embeddings {--force}`) đồng bộ batch cho Products, Articles và JSON FAQ (`ai_knowledge_embeddings.json`).
- [x] Viết `tests/Feature/Phase24SemanticRagEmbeddingTest.php` (4 tests / 68 assertions PASS).
- [x] Chạy `vendor\bin\pint --dirty --format agent` PASS, `php artisan optimize:clear`, full `php artisan test --compact` (**134 passed / 1521 assertions**).
- [x] Chạy `php artisan migrate --force` và `php artisan ai:sync-embeddings` đồng bộ thành công **25/25 Products, 15/15 Articles, 7/7 FAQ chunks (0 Thất bại)**, đồng thời xác minh chạy lại bỏ qua toàn bộ bản ghi không đổi nhờ SHA-256.
