# BÁO CÁO NGHIỆM THU PHASE 22.1: HOTFIX UX/MARKDOWN, TÁI THIẾT KẾ UI SHADCN/UI & KIẾN TRÚC RAG SHOWROOM AI

**Thời gian thực hiện**: Ngày 14 Tháng 11 Năm 2026  
**Chuyên môn**: Lead Fullstack & AI Architect  
**Trạng thái**: ĐÃ HOÀN THÀNH — **124 TESTS PASS 100% (1288 ASSERTIONS)**

---

## 1. BỐI CẢNH & MỤC TIÊU
Xử lý triệt để các lỗi phát sinh khi kiểm thử thực tế trên trình duyệt sau Phase 22 (mất Action Cards khi chuyển trang, lỗi hiển thị markdown link rỗng `[]()`, lặp câu dẫn rập khuôn và so sánh trùng tên xe), tái thiết kế khung Chatbot theo phong cách **shadcn/ui Generative UI (0px border-radius)** và xây dựng **Hệ thống RAG (Retrieval-Augmented Generation)** chuẩn hóa tri thức showroom BMW.

---

## 2. CHI TIẾT CÁC HẠNG MỤC TRIỂN KHAI

### 2.1. Bảo Toàn Action Cards Khi Chuyển Trang & Giới Hạn Tối Đa 4 Thẻ (`resources/js/app.js`)
- Điều chỉnh `maxStoredMessages: 16` để lưu tối đa 16 tin nhắn gần nhất trong `localStorage`, gọn nhẹ và ổn định.
- Cập nhật `persistState()` lưu kèm `suggestions` và `actions` (chỉ giữ `label`, `subtitle`, `kind`, `url` đã xác thực qua `internalLink()`).
- Gọi `this.pruneMessages(); this.persistState();` ngay thời điểm nhận phản hồi API bên trong `send()` (song song với hiệu ứng Progressive Reveal), bảo đảm không mất thẻ khi người dùng click điều hướng ngay lập tức.
- Cập nhật `restoreState()` thông qua `normalizeSuggestionList()` và `normalizeActionList()`, ưu tiên khôi phục nguyên vẹn mảng `actions` đã lưu qua `createAssistantMessage(..., savedActions)`.
- Bổ sung `actionPriority()` và `prioritizeAndLimitActions(..., 4)` sắp xếp ổn định theo cấp độ thực dụng (**Chi tiết xe / Đặt phụ kiện → Đặt lái thử → Nhận báo giá → So sánh xe → Ưu đãi / Bài viết → Tư vấn / Danh mục**).

### 2.2. Triệt Tiêu Link Rỗng `[]()`, Vá Slug Sai, Route Cũ & Tối Ưu So Sánh Trùng Xe
- **`App\Services\Ai\ShowroomAssistantService::sanitizeAndRepairMarkdownLinks()`**:
  - Giữ nguyên tuyệt đối các phản hồi văn bản thuần không chứa link markdown.
  - Bắt trọn mọi link markdown kể cả URL chứa ngoặc đơn XSS (`javascript:alert(1)`), link rỗng `[]()`, link `#`.
  - Xác thực slug `/catalog/{slug}`, `/tim-hieu-them/{slug}`, `/accessories/{slug}/order` với DB active/published; nếu LLM sinh sai slug → tự đối chiếu tên nhãn để sửa về đúng route/slug thật.
  - Ánh xạ tự động route cũ `/dat-lich` → `/booking` và `/phu-kien` → `/accessories`.
  - Chuyển mọi link không rõ route hoặc bị chặn về chữ in đậm `**Nhãn**`.
- **`resources/js/app.js`**:
  - Chặn `#` và chuỗi bắt đầu bằng `#` trong `internalLink()`, xử lý link rỗng/bị từ chối trong `parseTextParts()` thành `type: 'strong'` tương thích trực tiếp với Progressive Reveal (`visiblePartText`).
  - Loại bỏ câu dẫn kết cụt thừa sau khi trích xuất link/path và hỗ trợ tiêu đề markdown `^#{1,4}` trong `buildBlocks()`.
- **`App\Ai\Tools\CompareVehiclesTool` & `App\Ai\Agents\ShowroomAssistant`**:
  - Khi 2 tên xe lọc trùng chỉ còn 1 xe (`status: needs_more_vehicles`, giữ nguyên `compare_url: null`), tự động gợi ý `suggested_comparison_vehicle` (1 xe active cùng phân khúc/dòng xe gần nhất theo giá) kèm `suggested_compare_url` (`/compare?ids=A,B`).
  - Tinh chỉnh `instructions()` hạn chế lặp `"BMW Showroom"` (>2 lần), bỏ mở đầu `"Kính thưa Quý khách"`, không tự viết câu dẫn link thừa.

### 2.3. Tái Thiết Kế UI Chatbot Theo Chuẩn shadcn/ui & Mobile Responsive
- **`resources/views/components/public/ai-assistant-widget.blade.php` & `resources/css/app.css`**:
  - Mở rộng khung chat trên desktop `sm:w-[420px]`, giữ responsive an toàn mobile `w-[calc(100vw-1.5rem)] max-w-[420px]`, chiều cao `sm:h-[min(640px,calc(100vh-5rem))]`, và gắn `.scrollbar-none` trên khung cuộn tin nhắn.
  - Loại bỏ cột avatar lặp lại bên trái từng tin nhắn AI để mở rộng vùng hiển thị văn bản (`max-w-[94%]`), thay bằng badge header gọn (`#70A7FF` + `BMW AI`).
  - Bong bóng AI chuẩn shadcn/ui tối giản (`border border-zinc-800/90 bg-zinc-900/90`), lưới Action Cards `grid grid-cols-2 gap-1.5` (tự động `col-span-2 border-l-2 border-l-[#1C69D4]` cho thẻ đầu khi số thẻ lẻ để lưới luôn cân đối).
  - Bảo toàn tuyệt đối `0px border-radius`, zero `x-html`, zero `href="#"`.

### 2.4. Hệ Thống RAG (Knowledge Base + Retrieval Service + Tool + Prompt Grounding)
- **`resources/knowledge/bmw_showroom_knowledge.json`**: Gồm 7 chunks tri thức chuẩn hóa (`warranty_policy`, `maintenance_cbs`, `test_drive_rules`, `payment_and_deposit`, `trade_in_program`, `motorrad_guide`, `strict_boundaries`).
- **`App\Services\Ai\ShowroomKnowledgeBaseService`**: Nạp JSON an toàn qua `File::exists()` & `is_array()` (không bao giờ phát sinh exception khi thiếu hoặc hỏng file JSON), triển khai Hybrid Retrieval dùng chung `ShowroomCatalogMatcher` (bao gồm `scoreArticleQuery()` được trích xuất từ `SearchArticlesAndOffersTool` để không trùng lặp logic).
- **`App\Ai\Tools\SearchKnowledgeBaseTool` (`search_knowledge_base`)**: Tool thứ 4 đăng ký trên `ShowroomAssistant::tools()`, tra cứu tri thức RAG + sản phẩm active + bài viết published.
- **`ShowroomAssistantService::buildPrompt()`**: Tự động bơm tối đa 3 đoạn tri thức liên quan nhất vào khối `[TÀI LIỆU TRI THỨC RAG ĐƯỢC XÁC THỰC]` kèm nguyên tắc điều hướng `/booking?type=consult` khi thiếu dữ liệu công khai.

---

## 3. KẾT QUẢ KIỂM CHỨNG HỆ THỐNG

- **Kiểm thử tự động cụm AI (`AiAssistantPhase22Test`, `AiAssistantWidgetTest`, `AiShowroomAssistantTest`)**: **20 passed (320 assertions)** trong **6.39s**.
- **Kiểm thử toàn bộ hệ thống (`php artisan test --compact`)**: **124 passed (1288 assertions)** trong **20.48s** (100% PASS).
- **Định dạng mã nguồn (`vendor\bin\pint --dirty --format agent`)**: `{"tool":"pint","result":"passed"}`.
- **Biên dịch tài nguyên (`npm run build`)**: Thành công trong **946ms** (`55 modules transformed`, `public/build/assets/app-BXsuYffs.css` 96.50 kB, `public/build/assets/app-B0NegWBV.js` 100.66 kB).
- **Làm mới bộ nhớ đệm (`php artisan optimize:clear`)**: Thành công (`config`, `cache`, `compiled`, `events`, `routes`, `views`).
