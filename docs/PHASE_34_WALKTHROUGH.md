# Nhật Ký Phát Triển & Báo Cáo Nghiệm Thu Phase 34 — `bmw_laravel`

- **Thời điểm hoàn tất:** `2026-11-22` (`Asia/Ho_Chi_Minh`)
- **Trạng thái kiểm thử:** **PASS 100%** (`212 passed, 3451 assertions` trong `49.18s`; `Phase34EloquentContextAiFallbackAndPromptsTest`: `4 passed, 95 assertions` trong `1.68s`)

---

## 1. Tóm Tắt 4 Hạng Mục Triển Khai

### Phần 1 — Cập Nhật Cấu Hình Gemini (`gemini-3.1-flash-lite`) + Fallback Model & Rotation Tối Ưu
1. **Chuẩn hóa cấu hình (`config/showroom_ai.php`, `config/ai.php`, `config/services.php`, `.env.example`):**
   - Đặt model chính mặc định là `gemini-3.1-flash-lite`, bổ sung `fallback_model` là `gemini-3.5-flash-lite`, `timeout_seconds` mặc định `20`, và đồng bộ `GEMINI_KEY_COOLDOWN_SECONDS=60` (khớp chu kỳ reset RPM 60s của Gemini).
   - Bổ sung block cấu hình `services.gemini` phục vụ kiểm soát thống nhất endpoint, key, model và timeout.
2. **Tự động xoay vòng Key + Fallback Model (`GeminiKeyPool`, `GeminiAgentRunner`, `ShowroomAssistantService`, `AppointmentLeadClassifier`, `ArticleEditorialAiService`):**
   - `GeminiAgentRunner` hỗ trợ tự động chuyển từ `gemini-3.1-flash-lite` sang `gemini-3.5-flash-lite` khi model chính cạn quota / gặp `RateLimitedException` / HTTP `429`/`503` / timeout hoặc lỗi nhà cung cấp có thể thử lại, đồng thời đảm bảo tương thích với API key dạng `AQ....` qua header `x-goog-api-key` tới `models/{model}:generateContent`.
   - `ShowroomAssistantService` đồng bộ cơ chế fallback model trên cả luồng đồng bộ `askGeminiWithRotation()` và luồng streaming `stream()`.
   - Cập nhật `tests/Feature/Phase16_3GeminiKeyRotationTest.php` khớp cooldown `60s` và `fallback_model => null` cho unit tests xoay vòng key thuần túy.

---

### Phần 2 — Truy Vết Xuyên Suốt Với `Laravel Context` (`AttachRequestContext` → Timeline → Queue)
1. **Middleware `App\Http\Middleware\AttachRequestContext` (đăng ký trong `bootstrap/app.php`):**
   - Gắn `trace_id` (từ header `X-Request-Trace-Id` hoặc UUID mới), `url`, `actor_id`, `actor_role`, `crm_reference` (khi route mang `Appointment` hoặc `AccessoryOrder`) và `ai_visitor_hash` vào `Laravel Context`; trả header `X-Trace-Id` về client.
   - Bảo mật mã nội bộ (`crm_internal_customer_code`, `crm_voucher_code`, `ai_visitor_raw_id`) bằng `Context::addHidden(...)` để tránh rò rỉ ra log thông thường nhưng vẫn truyền được qua hàng đợi (`database` queue payload).
2. **Kế thừa sang CRM Timeline & AI Steps:**
   - `InteractionTimelineRecorder::makeEvent()` tự động kế thừa `trace_id` từ `Context::get('trace_id')`.
   - `GeminiAgentRunner` và `ShowroomAssistantService` đẩy từng bước gọi AI qua `Context::push('ai_steps', ...)`.

---

### Phần 3 — Nâng Cấp Kiến Trúc Eloquent ORM & Strictness Guardrails
1. **Bảo vệ Eloquent nghiêm ngặt trong non-production (`AppServiceProvider::boot()`):**
   - Bật `Model::preventLazyLoading(! $this->app->isProduction())` (đã phát hiện và vá lỗi thiếu eager load `user:id,name,email` trong `UserController::performance()`).
   - Bật `Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction())` và làm sạch toàn bộ test fixtures truyền nhầm trường ngoài `$fillable` (`unit_price`, `total_amount`, `total_price`, `service_package` tại `Phase28_5`, `Phase30`, `Phase31`, `Phase32`).
   - Bật `Relation::enforceMorphMap([...])` với bí danh `'appointment'`, `'accessory_order'`, `'user'`, `'product'`, `'article'` kèm migration `2026_11_22_000001_backfill_notifications_morph_types.php` chuẩn hóa dữ liệu `notifications.notifiable_type` từ `App\Models\User` sang `'user'`.
2. **Cải tiến Models & Relationships:**
   - **`Appointment` & `AccessoryOrder`**: Ẩn `customer_internal_code`, `daily_sequence_code`, `meta_data` khỏi `toArray()`/`toJson()` (`$hidden`); gán `->withDefault(['name' => 'Chưa phân công'])` cho `assignedSales()` kèm kiểm tra `$assignedSales?->exists` trên view `guest-manage.blade.php`, `admin/accessory-orders/show.blade.php`, `AccountingController`.
   - **`Appointment` & `AiChatMessage`**: Chuyển cast `'meta_data' => AsArrayObject::class` và chuẩn hóa helper `InteractionTimelineRecorder::normalizeMetaArray()` cho toàn bộ điểm đọc/ghi; thêm `protected $touches = ['session'];` trên `AiChatMessage` để tự động cập nhật `AiChatSession::updated_at`.
   - **`Product::primaryImage(): HasOne`**: Dùng `->ofMany(['is_primary' => 'max', 'id' => 'min'])` để đảm bảo luôn trả về đúng 1 ảnh đại diện ngay cả khi sản phẩm chưa có ảnh đánh dấu `is_primary = 1`.
3. **Custom Eloquent Collections & Factory States:**
   - Tạo `App\Collections\AppointmentCollection` và `App\Collections\AccessoryOrderCollection`, gắn qua `#[CollectedBy(...)]` trên `Appointment` & `AccessoryOrder`, và tái sử dụng thống nhất trong `AccountingController` & `UserController::performance()`.
   - Mở rộng `UserFactory` (`topSuperAdmin()`), `AppointmentFactory` (`hotLead()`, `assignedTo()`, `rescheduledByGuest()`), `ProductFactory` (`outOfStock()`), và tạo mới `AccessoryOrderFactory` (`completed()`, `cancelled()`, `assignedTo()`, gắn `HasFactory` trên `AccessoryOrder`).

---

### Phần 4 — Nâng Cấp Lệnh Vận Hành Với `Laravel Prompts`, `ShowroomCacheClearCommand` & `AboutCommand`
1. **Lệnh mới:**
   - `php artisan showroom:create-staff` (`CreateStaffCommand`): Khởi tạo nhân sự `@bmw.com` (`admin` / `sales` / `marketing`, hỗ trợ `--super-admin`) qua `Laravel\Prompts\form()` (`text()`, `select()`, `password()`), xuất `table()` + `callout()` và hỗ trợ đầy đủ CLI options cho kiểm thử tự động.
   - `php artisan showroom:cache-clear` (`ShowroomCacheClearCommand`): Xóa đồng bộ catalog, articles, dashboard, sales roster cache và tích hợp vào `$this->optimizes(clear: 'showroom:cache-clear')` khi chạy `php artisan optimize:clear`.
2. **Nâng cấp tương tác `ResetCrmDataCommand` & `CleanAndSyncShowroomDataCommand`:**
   - Bổ sung `confirm()`, `multiselect()`, `table()` và `callout()` của `Laravel\Prompts` cho chế độ tương tác, bảo toàn 100% cơ chế non-interactive khi truyền `--force` hoặc `--dry-run`.
3. **`AboutCommand::add('BMW Showroom', ...)` trong `AppServiceProvider`:**
   - Hiển thị thông số trực tiếp khi chạy `php artisan about` (`AI Provider`, `AI Primary Model`, `AI Fallback Model`, `AI Timeout`, `Gemini API Keys`, `Cache Driver`, `Timezone`).

---

## 2. Kết Quả Kiểm Chứng Sau Lần Sửa Code Cuối
- `php artisan migrate --force`: `2026_11_22_000001_backfill_notifications_morph_types` — **DONE** (`85.16ms`).
- `vendor\bin\pint --dirty --format agent`: **DONE** (`fixed` unused imports tại `Phase34EloquentContextAiFallbackAndPromptsTest.php`).
- `php artisan optimize:clear`: **DONE** (chạy đầy đủ `config`, `cache`, `compiled`, `events`, `routes`, `views`, và custom `app` `showroom:cache-clear` `49.01ms`).
- `php artisan about`: Hiển thị đầy đủ section `BMW Showroom` (`gemini-3.1-flash-lite`, `gemini-3.5-flash-lite`, `20s`).
- `php artisan test --compact tests/Feature/Phase34EloquentContextAiFallbackAndPromptsTest.php`: **`4 passed (95 assertions)`** trong **`1.68s`**.
- `php artisan test --compact`: **`212 passed (3451 assertions)`** trong **`49.18s`** (0 failed, 0 skipped; bảo toàn ngân sách `<= 8 SQL queries` cache lạnh trên 3 bảng CRM).
