# TỔNG KẾT PHASE 22: NÂNG CẤP CHATBOT AI AGENT (TOOL CALLING, CONVERSATION MEMORY, RICH ACTION CARDS) & TỐI ƯU UX TƯƠNG TÁC NGỮ CẢNH

## 1. Kết quả đạt được

1. **Hệ Thống 3 AI Tools Read-Only & Matcher Từ Đồng Nghĩa Tiếng Việt**:
   - `App\Services\Ai\ShowroomCatalogMatcher`: Chuẩn hóa tìm kiếm không dấu, chấm điểm mức độ khớp sản phẩm, mở rộng từ đồng nghĩa tiếng Việt (`suv` <-> `sav`, `xe điện` <-> `i4`/`iX`, `xe máy`/`mô tô`/`pkl` <-> `motorrad`) và định dạng dữ liệu công khai thống nhất.
   - `App\Ai\Tools\SearchCatalogTool` (`search_catalog`): Tra cứu sản phẩm đang kinh doanh (`active()`) theo `keyword`, `type` (`car`, `motorrad`, `accessory`), `category`, và `max_price` (VNĐ).
   - `App\Ai\Tools\CompareVehiclesTool` (`compare_vehicles`): Đối chiếu thông số kỹ thuật đầy đủ 2–3 mẫu xe `active()->vehicles()`, **lọc trùng theo `product->id`** (`unique('id')`) trước khi tạo liên kết `/compare?ids=...`; trả thông báo rõ ràng (`status: needs_more_vehicles`) kèm danh sách mẫu xe gợi ý khi chỉ khớp 1 xe duy nhất.
   - `App\Ai\Tools\SearchArticlesAndOffersTool` (`search_articles_and_offers`): Tìm kiếm bài viết ưu đãi, sự kiện và kinh nghiệm đã xuất bản (`Article::published()`), loại trừ hoàn toàn bản nháp và bài hẹn giờ tương lai.

2. **Nâng Cấp Agent `ShowroomAssistant`, Trí Nhớ Đa Lượt (`Conversational`) & Endpoint Reset An Toàn**:
   - `App\Ai\Agents\ShowroomAssistant`: Gắn `#[MaxSteps(6)]`, `#[MaxTokens(1100)]`, đăng ký cả 3 Tool trong `tools()`, nhận lịch sử hội thoại qua `messages()` và đồng bộ các đường dẫn thật (`/booking?type=test_drive`, `/booking?type=quote`, `/booking?type=consult`, `/accessories`, `/catalog/{slug}`, `/compare?ids=...`, `/tim-hieu-them/{slug}`).
   - `App\Services\Ai\ShowroomAssistantService` & `config/showroom_ai.php`: Mở rộng `ask(string $message, array $options = [])`, đọc giới hạn `max_history_messages` (14), `history_ttl_hours` (6), `timeout_seconds` (30) từ cấu hình; nạp hội thoại đa lượt thành công từ `AiChatMessage` sau mốc cache `after_id`; triển khai `resetConversation(?string $visitorId)` giữ nguyên toàn bộ log trong Admin CRM.
   - `App\Http\Controllers\Ai\ShowroomAssistantController` & `routes/web.php`: Validate `product_slug`/`product_name`, thêm endpoint `POST /ai/showroom-assistant/reset` (`ai.showroom-assistant.reset`) xử lý nhẹ nhàng khi `visitor_id` rỗng hoặc không hợp lệ.

3. **Đồng Bộ Ngữ Cảnh Sản Phẩm Vào Admin CRM**:
   - Migration `2026_11_13_081504_add_context_product_to_ai_chat_sessions_table.php`: Thêm `context_product_id` (FK nullable `nullOnDelete`), `context_product_name`, `context_product_slug` vào `ai_chat_sessions`.
   - `AiConversationTracker`, `AiChatSession`, `Admin\AiConversationController` và 2 view `resources/views/admin/ai-conversations/{index,show}.blade.php`: Tự động liên kết sản phẩm đang xem từ `current_product` hoặc `/catalog/{slug}` (chỉ sản phẩm active) và hiển thị nhãn `Đang xem: ...` / `Sản phẩm đang xem` rõ ràng cho đội tư vấn.

4. **Frontend Rich Action Cards, Progressive Reveal & Nút Ngữ Cảnh (0px Border-Radius)**:
   - `resources/js/app.js`: Thêm `detectPageContext()`, `handleContextualAsk()`, sự kiện `bmw-ai:ask`, `actionMeta()`, progressive reveal với hàm `visiblePartText()`, và fallback `this.config.resetEndpoint || '/ai/showroom-assistant/reset'`.
   - `resources/views/components/public/ai-assistant-widget.blade.php` & `resources/css/app.css`: Gỡ bỏ toàn bộ `rounded-*` và chuẩn hóa `border-radius: 0px;` cho `.ai-message-bubble` và `.ai-assistant-launcher`; render Rich Action Cards sắc cạnh, nút `"Làm mới"` và phím tắt `Esc` (`@keydown.escape.window`); chỉ render thẻ `<a>` inline khi `visiblePartText(part, message.revealedChars).length > 0`, tuyệt đối không dùng `x-html`.
   - `resources/views/products/show.blade.php`: Thêm `#ai-page-context` và nút `"Hỏi trợ lý AI về {{ $vehicle->name }}"` bên dưới Hero CTA (an toàn tuyệt đối với trang phụ kiện).

---

## 2. Thông Số Kiểm Thử & Đảm Bảo Chất Lượng
- **Kiểm thử PHPUnit (`php artisan test --compact`)**: **121 passed (1239 assertions)** trong **31.22s** (114 test hiện có / 1147 assertions + 7 test Phase 22 / 92 assertions) — **100% PASS**.
- **Biên dịch Frontend (`npm run build`)**: Thành công trong **1.23s** (`55 modules transformed`):
  - `public/build/manifest.json` (`0.27 kB`)
  - `public/build/assets/app-CjKkumYC.css` (`95.92 kB`)
  - `public/build/assets/app-DrWX-9im.js` (`98.09 kB`)
- **Làm mới bộ nhớ đệm (`php artisan optimize:clear`)**: Sạch sẽ (`config`, `cache`, `compiled`, `events`, `routes`, `views`).
- **Tiêu chuẩn mã nguồn (`vendor\bin\pint --dirty --format agent`)**: `{"tool":"pint","result":"passed"}`.

---

## 3. Bổ Sung Phase 22.1 (Hotfix UX/Markdown, UI shadcn/ui & Kiến Trúc RAG)
- **Bảo toàn Action Cards & giới hạn tối đa 4 thẻ (`resources/js/app.js`)**: Lưu/khôi phục `suggestions` và `actions` (tối đa 16 tin nhắn), `persistState()` ngay khi nhận phản hồi API, chuẩn hóa thứ tự ưu tiên và giới hạn tối đa 4 Action Cards mỗi tin nhắn; loại bỏ link `#` trong `internalLink()`, chuyển link rỗng/bị từ chối thành `type: 'strong'`, và loại bỏ `###` thô trong `buildBlocks()`.
- **Vá Link Backend (`ShowroomAssistantService::sanitizeAndRepairMarkdownLinks`)**: Xử lý cả URL có ngoặc đơn XSS (`javascript:alert(1)`), link rỗng `[]()`, `(#)`, slug `/catalog/{slug}` / `/tim-hieu-them/{slug}` / `/accessories/{slug}/order` không tồn tại DB, ánh xạ `/dat-lich` → `/booking` và `/phu-kien` → `/accessories`, và giữ nguyên văn bản thường.
- **So sánh Trùng Tên Xe & Giọng Văn (`CompareVehiclesTool.php`, `ShowroomAssistant.php`)**: Bổ sung `suggested_comparison_vehicle` cùng phân khúc và `suggested_compare_url` khi chỉ khớp 1 xe duy nhất sau lọc trùng (giữ `status: needs_more_vehicles`, `compare_url: null`); tinh chỉnh lời văn chuyên gia ngắn gọn.
- **Giao diện shadcn/ui 0px (`ai-assistant-widget.blade.php`, `resources/css/app.css`)**: Khung `w-[calc(100vw-1.5rem)] sm:w-[420px] max-w-[420px]`, bỏ avatar lặp lại bên trái từng tin nhắn, bong bóng `border-zinc-800/90 bg-zinc-900/90`, lưới Action Cards `grid-cols-2` (`col-span-2` cho thẻ đầu khi số thẻ lẻ), giữ `.scrollbar-none` và `border-radius: 0px`.
- **Kiến trúc RAG (`bmw_showroom_knowledge.json`, `ShowroomKnowledgeBaseService.php`, `SearchKnowledgeBaseTool.php`)**: 7 chunks tri thức showroom đọc an toàn (`File::exists()` & `is_array()`), Hybrid Retrieval dùng chung `ShowroomCatalogMatcher` (kể cả `scoreArticleQuery()`), tool thứ 4 `search_knowledge_base`, tự động bơm top 3 chunks `[TÀI LIỆU TRI THỨC RAG ĐƯỢC XÁC THỰC]` vào `buildPrompt()`.
- **Kiểm chứng sau Phase 22.1**: `pint` (`passed`), `php artisan optimize:clear` (pass), `npm run build` trong **946ms** (`public/build/assets/app-BXsuYffs.css` 96.50 kB, `public/build/assets/app-B0NegWBV.js` 100.66 kB), và `php artisan test --compact`: **124 passed (1288 assertions)** trong **20.48s**.
