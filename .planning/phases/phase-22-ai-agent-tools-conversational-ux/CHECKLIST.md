# CHECKLIST PHASE 22: AI AGENT TOOL CALLING, CONVERSATIONAL MEMORY & UX NGỮ CẢNH

## 1. AI Tools & Từ Đồng Nghĩa / Chống Trùng Lặp
- [x] Tạo `app/Services/Ai/ShowroomCatalogMatcher.php` dùng chung chuẩn hóa tìm kiếm, mở rộng từ đồng nghĩa tiếng Việt (`suv` <-> `sav`, `xe điện` <-> `i4`/`iX`, `xe máy`/`mô tô`/`pkl` <-> `motorrad`) và định dạng payload public.
- [x] Tạo `App\Ai\Tools\SearchCatalogTool` (`search_catalog`) chỉ đọc sản phẩm `active()`, lọc theo `keyword`, `type`, `category`, `max_price`.
- [x] Tạo `App\Ai\Tools\CompareVehiclesTool` (`compare_vehicles`) sử dụng `ArrayType->items($schema->string())`, lọc `active()->vehicles()`, khử trùng theo `product->id` (`unique('id')`) trước khi tạo `/compare?ids=...`, thông báo gợi ý thêm xe khi chỉ khớp 1 xe duy nhất.
- [x] Tạo `App\Ai\Tools\SearchArticlesAndOffersTool` (`search_articles_and_offers`) chỉ đọc `Article::published()`.

## 2. Agent, Conversational Memory & Endpoint Reset
- [x] Cập nhật `config/showroom_ai.php` (`max_history_messages`, `history_ttl_hours`, `timeout_seconds`).
- [x] Cập nhật `App\Ai\Agents\ShowroomAssistant` với `#[MaxSteps(6)]`, `#[MaxTokens(1100)]`, đăng ký 3 Tools, cập nhật `messages()` và `instructions()` theo route thật (`/booking`, `/accessories`, `/catalog/{slug}`, `/compare`, `/tim-hieu-them/{slug}`).
- [x] Cập nhật `App\Services\Ai\ShowroomAssistantService` hỗ trợ `ask($message, $options)`, nạp lịch sử `Conversational` sau mốc reset, nhận diện `current_product`/`current_article`, bảo toàn toàn bộ assertions cũ.
- [x] Cập nhật `ShowroomAssistantController` & `routes/web.php` thêm `POST /ai/showroom-assistant/reset` xử lý an toàn khi `visitor_id` rỗng/cũ.

## 3. Đồng Bộ Ngữ Cảnh Sản Phẩm Vào Admin CRM
- [x] Tạo migration `add_context_product_to_ai_chat_sessions_table` thêm `context_product_id`, `context_product_name`, `context_product_slug`.
- [x] Cập nhật `AiChatSession`, `AiConversationTracker`, `Admin\AiConversationController`, `admin/ai-conversations/{index,show}.blade.php` hiển thị rõ sản phẩm khách đang xem trong Admin CRM (chỉ sản phẩm active).

## 4. Frontend Rich Action Cards, Progressive Reveal & Contextual Trigger
- [x] Cập nhật `resources/js/app.js`: `detectPageContext()`, `handleContextualAsk()`, `bmw-ai:ask`, `actionMeta()`, progressive reveal gác ngưỡng `visiblePartText(part, message.revealedChars).length > 0`, `resetEndpoint || '/ai/showroom-assistant/reset'`.
- [x] Cập nhật `ai-assistant-widget.blade.php` & `resources/css/app.css`: Rich Action Cards 0px, zero `x-html`, zero `rounded-*`, `@keydown.escape.window`, nút `"Làm mới"`, badge ngữ cảnh `Đang xem`.
- [x] Cập nhật `resources/views/products/show.blade.php`: `#ai-page-context` và nút `"Hỏi trợ lý AI về {{ $vehicle->name }}"` an toàn tuyệt đối trên trang phụ kiện.

## 5. Kiểm Thử & Nghiệm Thu
- [x] Tạo `tests/Feature/AiAssistantPhase22Test.php` bao phủ toàn diện 3 Tools, Tool Calling, Conversational Memory & Reset, Page Context + CRM sync và UI/XSS/0px constraints.
- [x] Chạy `vendor\bin\pint --dirty --format agent` (PASSED).
- [x] Chạy `php artisan optimize:clear` và `npm run build` (PASSED).
- [x] Chạy toàn bộ `php artisan test --compact`.

## 6. Phase 22.1 Hotfix UX/Markdown, UI shadcn/ui & Kiến Trúc RAG
- [x] Khắc phục mất Action Cards khi chuyển trang (`resources/js/app.js`): `maxStoredMessages: 16`, lưu/khôi phục `suggestions` & `actions`, gọi `persistState()` ngay sau phản hồi API, giới hạn tối đa 4 Action Cards theo độ ưu tiên.
- [x] Vá `[]()`, `(#)`, `javascript:alert(1)`, slug hallucinated (`/catalog/{slug}`, `/tim-hieu-them/{slug}`, `/accessories/{slug}/order`) và route cũ (`/dat-lich`, `/phu-kien`) trong `ShowroomAssistantService::sanitizeAndRepairMarkdownLinks()`, kèm chuyển sang `type: 'strong'` khi không khớp route; hoàn thiện `internalLink()`, `extractActions()`, `parseTextParts()` và heading `#` trong `buildBlocks()`.
- [x] Nâng cấp `CompareVehiclesTool` gợi ý `suggested_comparison_vehicle` cùng phân khúc và `suggested_compare_url` khi chỉ khớp 1 xe duy nhất sau lọc trùng; tinh chỉnh `ShowroomAssistant::instructions()`.
- [x] Tái thiết kế `ai-assistant-widget.blade.php` & `resources/css/app.css`: `sm:w-[420px] w-[calc(100vw-1.5rem)] max-w-[420px]`, bỏ avatar lặp lại ở từng tin nhắn, bong bóng shadcn/ui `bg-zinc-900/90 border-zinc-800/90`, lưới 2 cột `grid-cols-2` (`col-span-2` khi số thẻ lẻ), giữ `.scrollbar-none` và `0px border-radius`.
- [x] Xây dựng RAG: `resources/knowledge/bmw_showroom_knowledge.json` (7 chunks), `ShowroomKnowledgeBaseService` (đọc JSON an toàn với `File::exists()` + `is_array()`, Hybrid Retrieval dùng chung `ShowroomCatalogMatcher`), `SearchKnowledgeBaseTool` (`search_knowledge_base`, tổng 4 Tools), tự động chèn `[TÀI LIỆU TRI THỨC RAG ĐƯỢC XÁC THỰC]` trong `buildPrompt()`.
- [x] Mở rộng `tests/Feature/AiAssistantPhase22Test.php` (4 Tools + RAG + Sanitizer + Single-match compare + Widget shadcn/persistence), chạy `pint`, `optimize:clear`, `npm run build` (`app-BXsuYffs.css`, `app-B0NegWBV.js`), và `php artisan test --compact` (**124 passed, 1288 assertions**).
