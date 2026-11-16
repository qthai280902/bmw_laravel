# BÁO CÁO NGHIỆM THU PHASE 25.1: NÂNG CẤP TOÀN DIỆN LARAVEL AI SDK (STREAMING SSE + VISION + STRUCTURED OUTPUT + ADMIN AI) & LARAVEL MCP SERVER

**Thời gian thực hiện**: Ngày 16 Tháng 11 Năm 2026  
**Chuyên môn**: Lead AI & Fullstack Architect  
**Trạng thái**: ĐÃ HOÀN THÀNH — **146 TESTS (1686 ASSERTIONS) PASS 100%**

---

## 1. TRỤ CỘT 1: SHOWROOM AI — STREAMING SSE + VISION + STRUCTURED OUTPUT

1. **Hạ tầng Xoay Key Dùng Chung (`App\Services\Ai\GeminiAgentRunner`)**:
   - Hợp nhất cơ chế xoay vòng `GeminiKeyPool`, cooldown rate limit và failover provider lỗi tạm thời để tái sử dụng đồng bộ cho cả `ShowroomAssistant` (sync & stream), `ShowroomRecommendationExtractor`, `ArticleEditorialAgent` và `AppointmentLeadClassifierAgent`.
2. **Streaming SSE Thời Gian Thực & Fallback**:
   - `App\Services\Ai\ShowroomAssistantService::stream()`: Phát lần lượt sự kiện `chunk` (`{"delta": "..."}`) ngay khi nhận `TextDelta`, hỗ trợ xoay vòng key Gemini trước chunk đầu tiên, và chốt sự kiện `done` kèm toàn bộ `answer` đã sanitize, `suggestions` và `conversation_id`.
   - `App\Http\Controllers\Ai\ShowroomAssistantController@stream`: Trả `StreamedResponse` với đầy đủ header chống buffering (`Content-Type: text/event-stream; charset=UTF-8`, `Cache-Control: no-cache, no-transform`, `X-Accel-Buffering: no`) và flush ngay sau mỗi sự kiện.
   - Bảo toàn đồng thời route cũ `/ai/showroom-assistant` + bổ sung `/ai/showroom-assistant/stream`, `/api/ai/assistant`, `/api/ai/assistant/stream`.
3. **Vision Attachment & Structured Recommendations**:
   - Hỗ trợ upload ảnh (`image|mimes:jpeg,png,webp|max:5120`) đính kèm vào agent qua `Laravel\Ai\Files\Image::fromUpload()`, kèm hướng dẫn nhận diện xe/phụ kiện BMW đối chiếu `Product::active()`.
   - `App\Ai\Agents\ShowroomRecommendationExtractor` (`HasStructuredOutput`): Chỉ kích hoạt tiết kiệm quota khi câu trả lời tư vấn sản phẩm/mua xe chưa trích xuất được thực thể rõ ràng qua `extractMentionedEntitySuggestions()` (hoặc khi faked trong test).
4. **Widget Chat Frontend (`resources/views/components/public/ai-assistant-widget.blade.php` & `resources/js/app.js`)**:
   - Đọc `ReadableStream` SSE qua `fetch()`, hiển thị từng đoạn văn an toàn bằng `x-text` (không dùng `x-html`), tích hợp nút **Dừng (`AbortController`)**, fallback về JSON khi mất stream, và khay đính kèm ảnh xem trước `0px` (giữ sạch 100% hợp đồng không chứa class `rounded-` trên widget).

---

## 2. TRỤ CỘT 2: TRỢ LÝ BIÊN TẬP BÀI VIẾT & PHÂN LOẠI LEAD CRM CHO ADMIN

1. **AI Tóm Tắt & Tạo SEO Bài Viết**:
   - `App\Ai\Agents\ArticleEditorialAgent` (`HasStructuredOutput`: `excerpt`, `seo_title`, `seo_description`) + `App\Services\Ai\ArticleEditorialAiService` (graceful fallback khi tắt AI hoặc lỗi mạng).
   - Route `POST /admin/articles/ai-assist` (`Admin\ArticleController@aiAssist`, bảo vệ `auth` + `admin`) kết nối trực tiếp nút **"AI Tóm Tắt & Tạo Mô Tả SEO"** trong `resources/views/admin/articles/_form.blade.php`.
2. **Tự Động Chấm Điểm & Phân Loại Lead CRM**:
   - Migration `2026_11_16_000001_add_ai_lead_columns_to_appointments_table` (`535.42ms`) bổ sung `lead_priority`, `lead_intent`, `ai_lead_note` vào `appointments` (`Appointment::$fillable` + helper `leadPriorityLabel()`, `leadPriorityColorClass()`, `leadIntentLabel()`).
   - `App\Ai\Agents\AppointmentLeadClassifierAgent` (`HasStructuredOutput`: `lead_priority`, `lead_intent`, `urgent`, `readiness_score`, `ai_lead_note`) + `App\Services\Ai\AppointmentLeadClassifier` (fallback rule-based tức thì không gây trễ/lỗi đặt lịch) kích hoạt qua `App\Observers\AppointmentObserver::created()` trong `AppServiceProvider`.
   - Bảng `App\Livewire\Admin\AppointmentTable` hiển thị badge `HOT LEAD` / `TIỀM NĂNG` / `THAM KHẢO`, ghi chú AI `ai_lead_note` và bộ lọc theo `leadPriority`.

---

## 3. TRỤ CỘT 3: LARAVEL MCP SERVER (`laravel/mcp` v0.6.7)

- Khai báo `"laravel/mcp": "^0.6"` trong `composer.json` và tạo `routes/ai.php` mở cổng Web MCP (`Mcp::web('/mcp/showroom', BmwShowroomServer::class)`) lẫn Local Stdio (`Mcp::local('bmw-showroom', BmwShowroomServer::class)`).
- Xây dựng `App\Mcp\Servers\BmwShowroomServer` cùng 3 Tools read-only an toàn:
  - `App\Mcp\Tools\SearchVehiclesMcpTool`: Tái sử dụng `VehicleSearchService::search()`.
  - `App\Mcp\Tools\GetVehicleDetailsMcpTool`: Trả chi tiết giá, tiền cọc, tình trạng mở bán, `translated_specs` tiếng Việt và URL đặt lịch cho sản phẩm `active`.
  - `App\Mcp\Tools\SearchShowroomKnowledgeMcpTool`: Tái sử dụng `ShowroomKnowledgeBaseService::retrieveRelevantChunks()` (Hybrid RAG).

---

## 4. KẾT QUẢ KIỂM CHỨNG

- **Migration (`php artisan migrate --force`)**: `2026_11_16_000001_add_ai_lead_columns_to_appointments_table` — **DONE** (`535.42ms`).
- **Laravel Pint (`vendor\bin\pint --dirty --format agent`)**: `{"tool":"pint","result":"passed"}`.
- **Vite Production Build (`npm run build`)**: **PASS** (`55 modules transformed`, `public/build/manifest.json` 0.27 kB, `public/build/assets/app-BHARmere.css` 98.03 kB, `public/build/assets/app-C9jVlp3q.js` 368.03 kB trong `4.58s`).
- **Cache Clear (`php artisan optimize:clear`)**: **DONE**.
- **Bộ kiểm thử mới (`tests/Feature/Phase25_1AiSdkCapabilitiesTest.php`)**: **5 passed (49 assertions)** trong **5.18s** (cô lập hoàn toàn bằng `::fake()` và `BmwShowroomServer::tool()`).
- **Toàn bộ Test Suite (`php artisan test --compact`)**: **146 passed (1686 assertions)** trong **99.24s** (**100% PASS**).
