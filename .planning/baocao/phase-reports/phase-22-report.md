# BÁO CÁO NGHIỆM THU PHASE 22: NÂNG CẤP CHATBOT AI AGENT (TOOL CALLING, CONVERSATION MEMORY, RICH ACTION CARDS) & TỐI ƯU UX TƯƠNG TÁC NGỮ CẢNH

**Thời gian thực hiện**: Ngày 13 Tháng 11 Năm 2026  
**Chuyên môn**: Lead Fullstack & AI Engineer  
**Trạng thái**: ĐÃ HOÀN THÀNH — **121 TESTS PASS 100% (1239 ASSERTIONS)**

---

## 1. BỐI CẢNH & MỤC TIÊU
Chuyển đổi `App\Ai\Agents\ShowroomAssistant` từ trợ lý chỉ dựa vào System Prompt tĩnh sang **AI Agent đa năng** dùng `laravel/ai` SDK (v0.7.2) tích hợp 3 công cụ truy vấn dữ liệu công khai (`HasTools`), trí nhớ hội thoại đa lượt (`Conversational`), nhận biết ngữ cảnh trang sản phẩm (`/catalog/{slug}`), Rich Action Cards 0px an toàn XSS và đồng bộ ngữ cảnh sản phẩm đang xem vào Admin CRM.

---

## 2. CHI TIẾT CÁC HẠNG MỤC TRIỂN KHAI

### 2.1. Hệ Thống 3 AI Tools Read-Only & Bộ Khớp Từ Đồng Nghĩa Tiếng Việt
- **`App\Services\Ai\ShowroomCatalogMatcher`**:
  - Chuẩn hóa truy vấn tiếng Việt không dấu, mở rộng **từ đồng nghĩa** (`suv` <-> `sav`, `xe điện` <-> `i4`/`iX`, `xe máy`/`mô tô`/`pkl` <-> `motorrad`), chấm điểm khớp sản phẩm và định dạng payload public thống nhất.
- **`App\Ai\Tools\SearchCatalogTool` (`search_catalog`)**:
  - Truy vấn sản phẩm `active()` theo `keyword`, `type` (`car`, `motorrad`, `accessory`), `category`, `max_price` (VNĐ); trả về liên kết thật `/catalog/{slug}`, `/accessories/{slug}/order`, `/booking?type=test_drive`, `/booking?type=quote`.
- **`App\Ai\Tools\CompareVehiclesTool` (`compare_vehicles`)**:
  - Đối chiếu 2–3 mẫu xe `active()->vehicles()`, khai báo `ArrayType->items($schema->string())` tương thích Gemini Function Calling.
  - **Khử trùng lặp (`unique('id')`)** trước khi tạo `/compare?ids=...`; nếu chỉ khớp 1 xe duy nhất sau lọc trùng, trả `status: needs_more_vehicles`, thông báo hướng dẫn cụ thể và danh sách gợi ý xe khác để AI đối chiếu.
- **`App\Ai\Tools\SearchArticlesAndOffersTool` (`search_articles_and_offers`)**:
  - Truy vấn bài viết/ưu đãi `Article::published()`, loại trừ tuyệt đối bài nháp và bài hẹn giờ tương lai, trả liên kết `/tim-hieu-them/{slug}`.

### 2.2. Nâng Cấp Agent `ShowroomAssistant`, Trí Nhớ Đa Lượt & Endpoint Reset An Toàn
- **`App\Ai\Agents\ShowroomAssistant`**:
  - Gắn `#[MaxSteps(6)]`, `#[MaxTokens(1100)]`, `#[Temperature(0.3)]`, đăng ký đủ 3 Tools, cập nhật `messages()` và `instructions()` dùng route thật của hệ thống (`/booking`, `/accessories`, `/catalog`, `/compare`, `/tim-hieu-them`).
- **`App\Services\Ai\ShowroomAssistantService` & `config/showroom_ai.php`**:
  - Bổ sung `max_history_messages` (14), `history_ttl_hours` (6), `timeout_seconds` (30).
  - Nạp lịch sử hội thoại thành công theo `visitor_id` sau mốc cache `after_id` (`resetConversation()`), không làm mất log lịch sử của Admin CRM.
  - Nhận diện `current_product` / `current_article` từ `product_slug` và `page_url` (chỉ sản phẩm `is_active = true`), ưu tiên sản phẩm đang xem trong prompt và `suggestions`.
- **`App\Http\Controllers\Ai\ShowroomAssistantController` & `routes/web.php`**:
  - Thêm route `POST /ai/showroom-assistant/reset` (`ai.showroom-assistant.reset`) xử lý nhẹ nhàng `200 OK` kể cả khi `visitor_id` rỗng hoặc không hợp lệ.

### 2.3. Đồng Bộ Ngữ Cảnh Sản Phẩm Đang Xem Vào Admin CRM
- Tạo migration `2026_11_13_081504_add_context_product_to_ai_chat_sessions_table.php` thêm `context_product_id`, `context_product_name`, `context_product_slug` cho `ai_chat_sessions`.
- Cập nhật `AiConversationTracker`, `AiChatSession`, `Admin\AiConversationController` và `resources/views/admin/ai-conversations/{index,show}.blade.php` liên kết và hiển thị rõ sản phẩm đang xem cho tư vấn viên CRM.

### 2.4. Rich Action Cards, Progressive Reveal & Kích Hoạt Ngữ Cảnh Sản Phẩm (0px Border-Radius)
- **`resources/js/app.js`**:
  - Bổ sung `detectPageContext()`, `handleContextualAsk()`, lắng nghe `bmw-ai:ask`, `actionMeta()`, progressive reveal gác điều kiện và fallback `this.config.resetEndpoint || '/ai/showroom-assistant/reset'`.
- **`resources/views/components/public/ai-assistant-widget.blade.php` & `resources/css/app.css`**:
  - Đồng bộ triệt để `0px border-radius` (bỏ `rounded-full` trong widget, chuyển `.ai-message-bubble` và `.ai-assistant-launcher` về `border-radius: 0px;`).
  - Chỉ render `<a>` inline khi `visiblePartText(part, message.revealedChars).length > 0`, tuyệt đối không dùng `x-html`.
  - Bổ sung Rich Action Cards (`data-ai-action-chip`), thanh ngữ cảnh `Đang xem`, nút `"Làm mới"` và phím tắt `Esc` (`@keydown.escape.window`).
- **`resources/views/products/show.blade.php`**:
  - Thêm `#ai-page-context` và nút `"Hỏi trợ lý AI về {{ $vehicle->name }}"` kích hoạt sự kiện `bmw-ai:ask`, bảo toàn điều kiện tách biệt trên trang phụ kiện.

---

## 3. KẾT QUẢ KIỂM CHỨNG HỆ THỐNG

- **Kiểm thử tự động (`php artisan test --compact`)**: **121 passed (1239 assertions)** trong **31.22s** (bao gồm 7 test mới tại `tests/Feature/AiAssistantPhase22Test.php` với 92 assertions).
- **Định dạng mã nguồn (`vendor\bin\pint --dirty --format agent`)**: `{"tool":"pint","result":"passed"}`.
- **Biên dịch tài nguyên (`npm run build`)**: Hoàn thành trong **1.23s** (`public/build/assets/app-CjKkumYC.css` 95.92 kB, `public/build/assets/app-DrWX-9im.js` 98.09 kB).
- **Làm mới bộ nhớ đệm (`php artisan optimize:clear`)**: Thành công.
