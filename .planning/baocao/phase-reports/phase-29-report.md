# Báo Cáo Nghiệm Thu Phase 29 — Hiệu Năng Chịu Tải Cao (`Cache::lock` & `Cache::flexible`), Thực Thi Song Song (`Concurrency`), Lá Chắn Hạn Ngạch (`RateLimiter`), Quản Trị Tài Nguyên (`File Storage` & Docker Hygiene) & Chuẩn Hóa Trợ Lý AI (`Structured Output` + `ShowroomAiToolkit`)

- **Dự án**: `bmw_laravel`
- **Ngày hoàn tất**: `2026-11-20`
- **Trạng thái**: **PASS 100%** (`189 passed`, `2645 assertions` trong `21.18s`)

---

## 1. Tổng Quan Hạng Mục Đã Triển Khai

### Trụ cột 1 — Khóa Tồn Kho Nguyên Tử (`Cache::lock`) & Bộ Đệm Stale-While-Revalidate (`Cache::flexible`)
1. **Chống bán lố phụ kiện nguyên tử (`AccessoryStockService`)**:
   - Tạo `App\Services\Inventory\AccessoryStockService` và `App\Exceptions\InsufficientAccessoryStockException`.
   - `placeOrder()` khóa theo sản phẩm bằng `Cache::lock("accessory-stock:{$product->id}", 10)->block(5, ...)` kết hợp `DB::transaction()` và `lockForUpdate()`, kiểm tra `is_active` / `canOrderAccessory()` và số lượng tồn kho, trừ kho nguyên tử và lưu dấu `meta_data['stock_deducted']` cùng timeline 2 chiều (`InteractionTimelineRecorder`).
   - `AccessoryOrderController::store()` bắt cả `InsufficientAccessoryStockException` lẫn `LockTimeoutException`, trả JSON HTTP `409` khi `expectsJson()` hoặc redirect kèm lỗi/banner `session('error')` trong `accessory-order-calculator.blade.php`.
   - `restoreStockOnCancel()` được tích hợp vào `AccessoryOrder::transitionToStatus()` để hoàn kho đúng **1 lần duy nhất** khi chuyển trạng thái sang `cancelled` (kiểm tra cờ `stock_deducted` & `stock_restored_at`).
   - Nâng mặc định `stock` trong `database/factories/ProductFactory.php` lên `10..25` giúp các test đơn hàng cũ luôn ổn định, trong khi test Phase 29 truyền tường minh `stock = 1` hoặc `0`.
2. **Stale-While-Revalidate Caching (`ShowroomCacheService`)**:
   - Tạo `App\Services\Cache\ShowroomCacheService` sử dụng `Cache::flexible(...)`: `homeData()` (`[60, 300]` trên `/`), `catalogCategories()` / `publicCatalogFilters()` (cho `CatalogBrowser`), `rememberArticlesSegment()`, `rememberCatalogSegment()`, và `dashboardSummary()` (`[30, 120]` trên `DashboardController`).
   - Tích hợp xóa cache tự động (kể cả key metadata `illuminate:cache:flexible:created:*`) thông qua `ProductObserver`, `ProductImageObserver`, `ArticleObserver`, `CategoryObserver`, `AppointmentObserver`, `AccessoryOrder` và `SiteSettingController`.

### Trụ cột 2 — Thực Thi Song Song An Toàn (`ShowroomConcurrency`)
1. **Lớp bọc `App\Services\Concurrency\ShowroomConcurrency` & Cấu hình `config/showroom.php`**:
   - Tự động chuyển về driver `'sync'` khi `app()->runningUnitTests()` hoặc `DB::transactionLevel() > 0`, và tự fallback mượt về `'sync'` kèm log warning nếu tiến trình OS gặp lỗi.
2. **Áp dụng tại 3 điểm nóng với closure `static fn ()` chỉ bắt kiểu nguyên thủy**:
   - `App\Http\Controllers\Admin\DashboardController::index()` (`/dashboard`).
   - `App\Console\Commands\SendShowroomDailySummaryCommand::handle()` (`showroom:daily-summary`).
   - `App\Services\Ai\ShowroomAssistantService` (truy xuất ngữ cảnh Catalog/Articles và tri thức RAG song song).

### Trụ cột 3 — Lá Chắn `RateLimiter` Gmail, Chống Spam AI Chat & Form Public
1. **Hạn ngạch chiến dịch Gmail (`GmailQuotaGuard` & `gmail-campaign`)**:
   - Đăng ký `RateLimiter::for('gmail-campaign', ...)` trong `AppServiceProvider` (đọc `config('mail.campaign_rate_per_minute', 20)`) và gắn `[new RateLimited('gmail-campaign')]` lên `SendPromotionCampaignEmails::middleware()`.
   - Tạo `App\Services\Mail\GmailQuotaGuard` theo dõi tổng thư gửi theo ngày (`Asia/Ho_Chi_Minh`, `config('mail.daily_soft_limit', 420)`), ghi nhận qua `RecordAppointmentMailSent`, ngắt vòng lặp gửi chiến dịch khi chạm ngưỡng mềm và chỉ bắn **1 thông báo chuông CRM/ngày** tới `admin` + `sales` (không gửi `marketing`), trong khi **email giao dịch / HOT LEAD vẫn ưu tiên gửi bình thường**.
2. **Chống spam Trợ lý AI & Đặt lịch/Đơn phụ kiện**:
   - Đăng ký `ai-concierge-chat` (mặc định `15/phút` + `60/giờ` trên production/.env, `12/phút` trong `phpunit.xml` để tương thích test cũ; khóa theo `visitor_id` + `IP` kèm trần bảo vệ theo IP) trả HTTP `429` JSON (`status`, `reason`, `retry_after`, `message`, `answer`), và xử lý mượt thông báo chờ trong `resources/js/app.js`.
   - Đăng ký `public-booking-submit` (giữ alias `showroom-booking`) bảo vệ cả `appointments.store` và `accessory-orders.store`.

### Trụ cột 4 — Quản Trị Tài Nguyên (`LocalMediaManager`) & Docker Log Hygiene
1. **Dọn ảnh tự động an toàn (`LocalMediaManager`, Observers & `PruneOrphanMediaCommand`)**:
   - Tạo `App\Services\Media\LocalMediaManager` chỉ thao tác trong 2 thư mục quản lý `products/` và `articles/`, tự bỏ qua URL ngoài và `images/` / `assets/` / `build/`, kiểm tra tham chiếu chéo DB trước khi xóa tệp.
   - `ProductObserver::deleting()` thu thập danh sách `product_images` trước khi FK cascade xóa bản ghi con; `ProductImageObserver` & `ArticleObserver` dọn tệp cũ khi cập nhật hoặc xóa.
   - Tạo Artisan command `showroom:prune-orphan-media` (`Isolatable`, hỗ trợ `--dry-run` và `--force-recent`, cửa sổ an toàn 24 giờ) và lập lịch hàng tuần Chủ nhật `04:00` (`Asia/Ho_Chi_Minh`) trong `routes/console.php`.
2. **Xoay vòng log Docker & Laravel**:
   - Cập nhật `docker-compose.yml` cấu hình `logging` `json-file` (`max-size: "10m"`, `max-file: "3"`) trên `x-app-common` (`app`, `queue`, `scheduler`).
   - Cập nhật `.env.example` với `LOG_CHANNEL=daily`, `LOG_DAILY_DAYS=14`, `SHOWROOM_CONCURRENCY_DRIVER=process`, `MAIL_CAMPAIGN_RATE_PER_MINUTE=20`, `MAIL_DAILY_SOFT_LIMIT=420`, `AI_CHAT_RATE_LIMIT_PER_MINUTE=15`, `AI_CHAT_RATE_LIMIT_PER_HOUR=60`.

### Trụ cột 5 — Chuẩn Hóa Trợ Lý AI (`App\Services\Ai`, Tool Calling & Structured Output)
1. **Structured Output & Grounding dữ liệu thật DB**:
   - Migration `2026_11_19_185648_add_meta_data_to_ai_chat_messages_table` thêm cột `meta_data` (nullable JSON) cho `AiChatMessage`.
   - Tạo `App\Services\Ai\Dto\ShowroomStructuredReply`, `App\Services\Ai\StructuredOutputParser` (kèm `geminiGenerationConfig()`, lọc ID sản phẩm active tồn tại trong DB, chuẩn hóa gói dịch vụ & `prefill_booking`, và fallback an toàn cho văn bản Markdown).
   - Tạo `App\Services\Ai\ShowroomAiToolkit` (`calculateFinancialEstimate`, `checkAccessoryStock`, `listServicePackages`) cùng 3 AI Tools (`CalculateFinancialEstimateTool`, `CheckAccessoryStockTool`, `ListServicePackagesTool`) gắn lên `App\Ai\Agents\ShowroomStructuredAssistant` (`HasStructuredOutput`, 7 tools), đồng thời giữ nguyên 4 tools gốc trên `ShowroomAssistant` để tương thích 100% với kiểm thử Phase 22.
2. **Lưu vết CRM & Giữ ngân sách SQL `<= 8 queries`**:
   - `AiConversationTracker::recordExchange()` lưu `meta_data` (`intent`, `lead_level`, `recommended_product_ids`, `recommended_service_package`, `prefill_booking`, `tool_calls`) lên tin nhắn assistant và ưu tiên `last_intent` từ structured JSON khi không phải fallback.
   - Hiển thị các badge chuẩn BMW Dark Luxury (`rounded-none`) tại `/admin/ai-conversations/{session}` (`show.blade.php`) và trong Quick Inspector Drawer của `appointment-table.blade.php` với 0 truy vấn bổ sung trên danh sách CRM (`<= 8` SQL queries khi cache lạnh).

---

## 2. Kết Quả Kiểm Thử & Nghiệm Thu

| Hạng mục kiểm chứng | Lệnh thực thi | Kết quả |
| --- | --- | --- |
| Migration DB | `php artisan migrate --force` | **PASS** (`2026_11_19_185648_add_meta_data_to_ai_chat_messages_table` `18.29ms DONE`) |
| Chuẩn mã nguồn Pint | `vendor\bin\pint --dirty --format agent` | **PASS** (`{"tool":"pint","result":"passed"}`) |
| Biên dịch tài nguyên | `npm run build` | **PASS** (`55 modules`, `app-n2wMdn68.css`, `app-Cq5J2GD5.js` trong `1.84s`) |
| Lịch cron & dọn ảnh thử | `php artisan schedule:list`, `php artisan showroom:prune-orphan-media --dry-run` | **PASS** (8 schedules gồm `0 21 * * 6 php artisan showroom:prune-orphan-media`, dry-run exit `0`) |
| Kiểm thử AI mục tiêu | `php artisan test --compact tests/Feature/AiAssistantPhase22Test.php tests/Feature/AiShowroomAssistantTest.php tests/Feature/Phase17AiConversationTest.php tests/Feature/Phase29PerformanceLocksRateLimitingStorageAndAiTest.php` | **PASS** (`29 passed (379 assertions)` trong `2.15s`) |
| Kiểm thử chuyên sâu Phase 29 | `php artisan test --compact tests/Feature/Phase29PerformanceLocksRateLimitingStorageAndAiTest.php` | **PASS** (`6 passed (157 assertions)` trong `1.29s`) |
| Toàn bộ Test Suite | `php artisan test --compact` | **PASS** (`189 passed (2645 assertions)` trong `21.18s`) |
