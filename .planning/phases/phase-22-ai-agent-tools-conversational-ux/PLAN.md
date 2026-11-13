# PHASE 22 PLAN: NÂNG CẤP CHATBOT AI AGENT (TOOL CALLING, CONVERSATION MEMORY, RICH ACTION CARDS) & TỐI ƯU UX TƯƠNG TÁC NGỮ CẢNH

**Trạng thái**: Đã duyệt & triển khai  
**Mục tiêu**: Nâng cấp `App\Ai\Agents\ShowroomAssistant` từ mô hình System Prompt tĩnh sang **AI Agent đa năng** với `HasTools`, `Conversational`, nhận biết ngữ cảnh trang sản phẩm (`/catalog/{slug}`), Rich Action Cards 0px an toàn XSS và đồng bộ ngữ cảnh sản phẩm vào Admin CRM.

---

## 1. Hạng Mục Triển Khai

### 1.1. 3 AI Tools Read-Only (`Laravel\Ai\Contracts\Tool`) & Matcher Dùng Chung
- `App\Services\Ai\ShowroomCatalogMatcher`: Chuẩn hóa tìm kiếm không dấu, mở rộng **từ đồng nghĩa tiếng Việt** (`suv` <-> `sav`, `xe điện` <-> `i4`/`iX`, `xe máy`/`mô tô`/`pkl` <-> `motorrad`), chấm điểm sản phẩm và định dạng payload public.
- `App\Ai\Tools\SearchCatalogTool` (`search_catalog`): Tra cứu sản phẩm `active()` theo `keyword`, `type` (`car`, `motorrad`, `accessory`), `category`, `max_price`.
- `App\Ai\Tools\CompareVehiclesTool` (`compare_vehicles`): Đối chiếu 2–3 mẫu xe `active()->vehicles()`, **lọc trùng theo `product->id`** (`unique('id')`) trước khi tạo `/compare?ids=...`; trả hướng dẫn rõ ràng kèm gợi ý mẫu xe đối chiếu khi chỉ khớp 1 xe duy nhất.
- `App\Ai\Tools\SearchArticlesAndOffersTool` (`search_articles_and_offers`): Tìm kiếm bài viết/ưu đãi `Article::published()`, loại trừ tuyệt đối bản nháp và bài hẹn giờ tương lai.

### 1.2. Agent (`ShowroomAssistant`), Conversational Memory & Reset An Toàn
- Cấu hình `#[MaxSteps(6)]`, `#[MaxTokens(1100)]`, `#[Temperature(0.3)]`, đăng ký 3 Tools trong `tools()` và chuẩn hóa các đường dẫn thật (`/booking`, `/accessories`, `/catalog/{slug}`, `/compare`, `/tim-hieu-them/{slug}`).
- `ShowroomAssistantService`:
  - Hỗ trợ `ask(string $message, array $options = [])` truyền `conversationMessages` và `pageContextSummary` vào `ShowroomAssistant::make(...)`.
  - Nạp lịch sử hội thoại đa lượt từ `AiChatMessage` theo `visitor_id` sau mốc reset cache (`after_id`), loại bỏ các phản hồi fallback lỗi.
  - Bổ sung `resetConversation(?string $visitorId)` giữ nguyên toàn bộ log trong Admin CRM.
- `ShowroomAssistantController` & `routes/web.php`: Validate `product_slug`/`product_name`, cung cấp endpoint `POST /ai/showroom-assistant/reset` (`ai.showroom-assistant.reset`) xử lý nhẹ nhàng kể cả khi `visitor_id` rỗng hoặc không hợp lệ.

### 1.3. Đồng Bộ Ngữ Cảnh Sản Phẩm Vào Admin CRM
- Migration `add_context_product_to_ai_chat_sessions_table`: Bổ sung `context_product_id` (FK nullable `nullOnDelete`), `context_product_name`, `context_product_slug` vào `ai_chat_sessions`.
- `AiConversationTracker` & `AiChatSession`: Lưu sản phẩm đang xem từ `current_product` hoặc `/catalog/{slug}` (chỉ sản phẩm `is_active = true`), suy diễn intent sản phẩm khi câu hỏi dùng đại từ ngữ cảnh, hiển thị rõ trên `/admin/ai-conversations` index & show.

### 1.4. Frontend Rich Action Cards, Progressive Reveal & Product Trigger (0px Border-Radius)
- `resources/js/app.js`: `detectPageContext()`, sự kiện `bmw-ai:ask`, `handleContextualAsk()`, phím `Esc` (`@keydown.escape.window`), `actionMeta()`, progressive reveal với kiểm tra `visiblePartText(part, message.revealedChars).length > 0`, fallback `this.config.resetEndpoint || '/ai/showroom-assistant/reset'`.
- `resources/views/components/public/ai-assistant-widget.blade.php` & `resources/css/app.css`: Gỡ bỏ toàn bộ `rounded-*` / non-zero `border-radius` trên widget (0px tuyệt đối), thêm badge ngữ cảnh `Đang xem`, nút `"Làm mới"` gọi endpoint reset, Rich Action Cards sắc cạnh không dùng `x-html`.
- `resources/views/products/show.blade.php`: Gắn `#ai-page-context` và nút `"Hỏi trợ lý AI về {{ $vehicle->name }}"` bên dưới Hero CTA (an toàn tuyệt đối với trang phụ kiện).

---

## 2. Nguyên Tắc Bảo Toàn Kiểm Thử
- Bảo toàn 100% **114 tests / 1147 assertions** hiện có + bổ sung `tests/Feature/AiAssistantPhase22Test.php`.
- Không tạo `x-html`, không dùng `href="#"`, tuân thủ 0px border-radius và Laravel Pint.
