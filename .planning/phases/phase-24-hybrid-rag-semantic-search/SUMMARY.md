# Phase 24 Summary: Hybrid RAG Semantic Search Với Cloudflare Workers AI (`@cf/google/embeddinggemma-2`)

## Delivered Changes
- **Multi-Account Cloudflare Configuration**: Cập nhật [config/showroom_ai.php](file:///c:/Users/thaib/du_an_code/bmw_laravel/config/showroom_ai.php), [.env.example](file:///c:/Users/thaib/du_an_code/bmw_laravel/.env.example), [.env](file:///c:/Users/thaib/du_an_code/bmw_laravel/.env) (2 tài khoản Cloudflare Workers AI xoay vòng/failover) và [phpunit.xml](file:///c:/Users/thaib/du_an_code/bmw_laravel/phpunit.xml).
- **Schema & Models**: Tạo [2026_11_15_000001_add_embedding_columns_to_products_and_articles_tables.php](file:///c:/Users/thaib/du_an_code/bmw_laravel/database/migrations/2026_11_15_000001_add_embedding_columns_to_products_and_articles_tables.php); cập nhật [Product.php](file:///c:/Users/thaib/du_an_code/bmw_laravel/app/Models/Product.php) và [Article.php](file:///c:/Users/thaib/du_an_code/bmw_laravel/app/Models/Article.php) ẩn `embedding`/`embedding_hash` qua `$hidden` và tạo `toEmbeddingParts()`.
- **Vector Service & Observers**: Tạo [CloudflareEmbeddingService.php](file:///c:/Users/thaib/du_an_code/bmw_laravel/app/Services/Ai/CloudflareEmbeddingService.php), [ProductObserver.php](file:///c:/Users/thaib/du_an_code/bmw_laravel/app/Observers/ProductObserver.php), [ArticleObserver.php](file:///c:/Users/thaib/du_an_code/bmw_laravel/app/Observers/ArticleObserver.php) và đăng ký tại [AppServiceProvider.php](file:///c:/Users/thaib/du_an_code/bmw_laravel/app/Providers/AppServiceProvider.php). Khi thử nghiệm đồng bộ thực tế lên Cloudflare Workers AI `@cf/google/embeddinggemma-2`, đã phát hiện và chuẩn hóa parser hỗ trợ định dạng OpenAI-style `result.data[i].embedding` cùng với fallback dạng mảng phẳng `result.data[i]`.
- **Hybrid Search & CLI**: Nâng cấp [ShowroomKnowledgeBaseService.php](file:///c:/Users/thaib/du_an_code/bmw_laravel/app/Services/Ai/ShowroomKnowledgeBaseService.php), [SearchCatalogTool.php](file:///c:/Users/thaib/du_an_code/bmw_laravel/app/Ai/Tools/SearchCatalogTool.php) và tạo [SyncAiEmbeddingsCommand.php](file:///c:/Users/thaib/du_an_code/bmw_laravel/app/Console/Commands/SyncAiEmbeddingsCommand.php).

## Verification Results
- `vendor\bin\pint --dirty --format agent`: PASS.
- `php artisan test --compact`: **134 passed (1521 assertions)** — bảo toàn 130 test cũ + 4 test mới (`Phase24SemanticRagEmbeddingTest`, 68 assertions).
- `php artisan migrate --force`: `2026_11_15_000001_add_embedding_columns_to_products_and_articles_tables` DONE.
- `php artisan ai:sync-embeddings`: Đồng bộ thực tế thành công **25/25 sản phẩm active, 15/15 bài viết published, 7/7 FAQ chunks (0 thất bại)**; chạy lần 2 bỏ qua toàn bộ **25/15/7** bản ghi không đổi nhờ `embedding_hash` SHA-256.

## Phase 24.1 Hotfix (Context-Aware Action Cards)
- `ShowroomAssistantService`: chuyển `suggestionsFor()` sang chạy hậu câu trả lời `$answer`, tự động trích xuất thực thể từ `$answer` (`extractMentionedEntitySuggestions` + alias rút gọn duy nhất), ngắt dính `current_product` khi hỏi chủ đề chính sách chung hoặc xe khác (`shouldIncludeCurrentProductSuggestion`), và tự động sinh thẻ `/compare?ids=...` khi giới thiệu >= 2 mẫu xe.
- `ShowroomAssistant`: bổ sung hướng dẫn gắn link Markdown `[Tên xe](/catalog/{slug})` và `[Tiêu đề bài viết](/tim-hieu-them/{slug})`.
- `resources/js/app.js`: bảo toàn tên xe/tiêu đề thật trong `actionLabel()`, khử trùng `canonicalActionUrl()`, bảo toàn `subtitle`/`kind` trong `normalizeSuggestionList()`, và biên dịch lại qua `npm run build`.
- `Phase24SemanticRagEmbeddingTest`: bổ sung `test_action_cards_follow_ai_mentioned_vehicles_and_drop_unrelated_page_context_product` (4 kịch bản A/B/C/D). Full suite: **135 tests PASS 100%**.
