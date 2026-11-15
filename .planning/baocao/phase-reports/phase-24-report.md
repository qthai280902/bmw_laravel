# BÁO CÁO NGHIỆM THU PHASE 24: NÂNG CẤP HYBRID RAG SEMANTIC SEARCH VỚI CLOUDFLARE WORKERS AI (`@cf/google/embeddinggemma-2`)

**Thời gian thực hiện**: Ngày 15–16 Tháng 11 Năm 2026  
**Chuyên môn**: Lead AI & Fullstack Architect  
**Trạng thái**: ĐÃ HOÀN THÀNH — **134 TESTS PASS 100% (1521 ASSERTIONS)** & **ĐỒNG BỘ VECTOR LIVE THÀNH CÔNG 100%**

---

## 1. TỔNG QUAN KIẾN TRÚC & TÍNH NĂNG ĐÃ TRIỂN KHAI

1. **Đa Tài Khoản Cloudflare Workers AI & Failover Tự Động (`CloudflareEmbeddingService`, `config/showroom_ai.php`)**:
   - Hỗ trợ đọc danh sách tài khoản từ `CLOUDFLARE_AI_ACCOUNTS`, các cặp đánh số `CLOUDFLARE_ACCOUNT_ID_1/2` & `CLOUDFLARE_API_TOKEN_1/2`, đồng thời tương thích ngược cặp đơn `CLOUDFLARE_ACCOUNT_ID` & `CLOUDFLARE_API_TOKEN`.
   - Gọi endpoint `https://api.cloudflare.com/client/v4/accounts/{account_id}/ai/run/@cf/google/embeddinggemma-2` với payload `{"input": [...], "dimensions": 768}`, tự động xoay vòng round-robin và failover sang tài khoản kế tiếp + đặt cooldown khi gặp HTTP `429`, `401`, `403`, `5xx` hoặc lỗi kết nối.
   - Tích hợp Task Prefixes chuẩn `@cf/google/embeddinggemma-2`: `"title: {title|none} | text: {content}"` cho văn bản và `"task: search result | query: {query}"` cho câu truy vấn, kết hợp bộ nhớ đệm truy vấn 15 phút (`Cache` + memo trong request).
   - Tự động phát hiện và hỗ trợ cấu trúc response thực tế `result.data[].embedding` (OpenAI-style object list) từ Cloudflare `@cf/google/embeddinggemma-2` song song với mảng phẳng.

2. **Schema Database, Bảo Mật `$hidden` & Observers Tự Động**:
   - Migration `2026_11_15_000001_add_embedding_columns_to_products_and_articles_tables.php` thêm `embedding` (`json`, nullable) và `embedding_hash` (`string(64)`, nullable) cho `products` và `articles`.
   - `Product` và `Article` ẩn `embedding`, `embedding_hash` khỏi `$hidden` và cung cấp `toEmbeddingParts()` (tải `loadMissing('category')` trên `Product`).
   - `ProductObserver` và `ArticleObserver` chỉ sinh vector khi `$product->is_active === true` và `$article->isPublished()`, so sánh SHA-256 `embedding_hash` để bỏ qua khi nội dung chưa đổi, cập nhật bằng `saveQuietly()` trong `try/catch` để Admin CRUD không bao giờ bị gián đoạn.

3. **Hybrid Search & CLI Đồng Bộ (`ShowroomKnowledgeBaseService`, `SearchCatalogTool`, `ai:sync-embeddings`)**:
   - `ShowroomKnowledgeBaseService::retrieveRelevantChunks()` tính tổng điểm `lexicalScore + semanticScore` (với ngưỡng `min_similarity = 0.42`, `semantic_weight = 900`), loại trừ `strict_boundaries` khỏi chấm điểm cosine để giữ vai trò fallback mặc định.
   - `SearchCatalogTool` bổ sung semantic fallback khi tìm kiếm từ khóa lexical trả về 0 kết quả mà vẫn giữ nguyên các bộ lọc `type`, `category`, `max_price`.
   - Lệnh `php artisan ai:sync-embeddings {--force}` đồng bộ batch vector cho toàn bộ sản phẩm active, bài viết published và tài liệu tĩnh `bmw_showroom_knowledge.json` vào `storage/app/private/ai_knowledge_embeddings.json`.

---

## 2. KẾT QUẢ KIỂM CHỨNG & ĐỒNG BỘ THỰC TẾ

- **Laravel Pint (`vendor\bin\pint --dirty --format agent`)**: `{"tool":"pint","result":"passed"}`.
- **Phase 24 Test Suite (`tests/Feature/Phase24SemanticRagEmbeddingTest.php`)**: **4 passed (68 assertions)** kiểm chứng payload/Task Prefix, failover 2 tài khoản, query cache 15 phút, `cosineSimilarity()`, Observers + SHA-256 dedupe + `$hidden`, CLI sync `--force`, Hybrid RAG không trùng từ khóa và fallback khi tắt/lỗi API.
- **Full Test Suite (`php artisan test --compact`)**: **134 passed (1521 assertions)** — bảo toàn 130 tests cũ + 4 tests Phase 24 (**PASS 100%**).
- **Database Migration Local (`php artisan migrate --force`)**: `2026_11_15_000001_add_embedding_columns_to_products_and_articles_tables` (`38.12ms DONE`).
- **Đồng Bộ Vector Thực Tế (`php artisan ai:sync-embeddings`)**:
  - **Lần 1**: Sản phẩm active **25/25**, Bài viết published **15/15**, Tri thức tĩnh JSON **7/7** đồng bộ thành công (**0 thất bại**), tạo `storage/app/private/ai_knowledge_embeddings.json` (`137,597 bytes`).
  - **Lần 2**: Bỏ qua **25/25** sản phẩm, **15/15** bài viết và **7/7** FAQ chunks (**0 thất bại**) nhờ cơ chế chống trùng lặp SHA-256 `embedding_hash`.
