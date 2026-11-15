# BÁO CÁO NGHIỆM THU PHASE 24.1: HOTFIX CONTEXT-AWARE ACTION CARDS & TỰ ĐỘNG TRÍCH XUẤT THỰC THỂ TỪ CÂU TRẢ LỜI AI

**Thời gian thực hiện**: Ngày 16 Tháng 11 Năm 2026  
**Chuyên môn**: Lead AI & Fullstack Architect  
**Trạng thái**: ĐÃ HOÀN THÀNH — **135 TESTS (1539 ASSERTIONS) PASS 100%**

---

## 1. NGUYÊN NHÂN GỐC RỄ ĐÃ KHẮC PHỤC

1. **Backend tính `suggestions` trước khi AI trả lời**: Trước Phase 24.1, `ShowroomAssistantService::ask()` gọi `suggestionsFor($message, $context)` trước khi nhận câu trả lời từ mô hình AI và chèn cứng `current_product` lên đầu mảng `suggestions`, khiến trang đang mở (ví dụ `/catalog/bmw-330i-sedan`) luôn áp đảo Action Cards ngay cả khi khách hỏi sang xe khác hoặc chính sách chung.
2. **Frontend `actionLabel()` ghi đè nhãn cụ thể**: Trong `resources/js/app.js`, mọi liên kết `/catalog/{slug}` đều bị gán cứng nhãn `'Xem chi tiết'` và `/tim-hieu-them/{slug}` thành `'Đọc bài viết'`, làm mất tên mẫu xe và tiêu đề bài viết thật trên thẻ gợi ý.

---

## 2. CÁC THAY ĐỔI KỸ THUẬT ĐÃ ÁP DỤNG

### A. Backend (`app/Services/Ai/ShowroomAssistantService.php` & `app/Ai/Agents/ShowroomAssistant.php`)
- **Trích xuất thực thể hậu phản hồi (`extractMentionedEntitySuggestions`)**: Sau khi `sanitizeAndRepairMarkdownLinks()` chuẩn hóa câu trả lời AI `$answer`, hệ thống quét toàn bộ `Product::active()` (sắp theo độ dài tên giảm dần) và `Article::published()` bằng cả 3 phương thức:
  1. URL nội bộ (`/catalog/{slug}`, `/accessories/{slug}/order`, `/tim-hieu-them/{slug}`).
  2. Tên đầy đủ theo ranh giới ký tự Unicode (`/(?<![\p{L}\p{N}])...(?![\p{L}\p{N}])/ui`).
  3. Alias dòng xe rút gọn duy nhất (`uniqueShortProductAliases`, chỉ kích hoạt khi duy nhất 1 sản phẩm active mang tên rút gọn như `BMW 530i`).
  - Các thực thể được sắp xếp theo đúng vị trí xuất hiện đầu tiên trong `$answer` kèm `subtitle` (`HỒ SƠ SẢN PHẨM`, `PHỤ KIỆN CHÍNH HÃNG`, `BÀI VIẾT & ƯU ĐÃI`), `label` chuẩn tên sản phẩm/bài viết và `kind`.
- **Ngắt dính `current_product` (`shouldIncludeCurrentProductSuggestion`)**: Chỉ giữ thẻ `current_product` khi câu hỏi/trả lời nhắc trực tiếp tên hoặc mã dòng xe đang xem, dùng đại từ chỉ định (`xe này`, `mẫu này`, `chiếc này`, `lăn bánh`, `trả góp`, `đặt cọc`, ...), hoặc khi AI không nhắc thực thể nào khác và không phải câu hỏi chính sách chung (`bảo hành`, `pin xe điện`, `chai pin`, `bảo dưỡng`, `cbs`, `thu cũ đổi mới`, `bằng lái`).
- **Tự động ghép thẻ So sánh & Bảo toàn test cũ**: Khi AI giới thiệu từ 2 mẫu xe trở lên và `current_product` đã được ngắt bỏ, tự động thêm thẻ `/compare?ids=...` (`SO SÁNH TRỰC TIẾP` → `So sánh N mẫu xe`) và nút `/booking?type=test_drive` trong giới hạn 4 thẻ; bảo toàn nhánh khớp từ khóa người dùng cho 2 test cũ (`Xem BMW 330i Sedan`, `Xem BMW S1000RR`).
- **Prompt `ShowroomAssistant::instructions()`**: Hướng dẫn AI gắn kèm liên kết Markdown chuẩn `[Tên xe](/catalog/{slug})` hoặc `[Tiêu đề bài viết](/tim-hieu-them/{slug})` khi tư vấn cụ thể.

### B. Frontend (`resources/js/app.js`)
- `actionLabel(url, fallbackLabel)`: Bảo toàn tên xe cụ thể cho `/catalog/{slug}` (tự động lược bỏ tiền tố `'Xem '` nếu có, chỉ fallback `'Xem chi tiết'` khi không có tên cụ thể) và giữ nguyên tiêu đề bài viết cho `/tim-hieu-them/{slug}`.
- `canonicalActionUrl()` & `extractActions()`: Khử trùng tuyệt đối theo URL chuẩn hóa (bỏ dấu `/` thừa cuối path), ưu tiên giữ nhãn + `subtitle` + `kind` chuẩn từ backend khi trùng URL với link inline trong đoạn văn, sắp xếp đúng trật tự xe AI giới thiệu → `/compare` → `/booking?type=test_drive` → `/booking?type=quote` → `/tim-hieu-them/{slug}` → `/booking?type=consult`, giới hạn tối đa 4 thẻ.
- `normalizeSuggestionList()` & `normalizeActionList()`: Bảo toàn thuộc tính `subtitle` và `kind` xuyên suốt vòng đời lưu/khôi phục `localStorage`.

---

## 3. KẾT QUẢ KIỂM CHỨNG

- **Kiểm thử Phase 24.1 (`Phase24SemanticRagEmbeddingTest::test_action_cards_follow_ai_mentioned_vehicles_and_drop_unrelated_page_context_product`)**: Xác nhận đầy đủ 4 kịch bản A/B/C/D (chuyển sang xe khác & auto-compare, khớp tên xe rút gọn duy nhất, ngắt thẻ khi hỏi chính sách pin EV, giữ thẻ khi hỏi về xe đang xem) + kiểm tra hợp đồng JS.
- **Cụm kiểm thử AI (`Phase24`, `AiShowroomAssistantTest`, `Phase16_2`, `AiAssistantPhase22Test`)**: **23 passed (284 assertions)**.
- **Vite Production Build (`npm run build`)**: **PASS** (`55 modules transformed`, `public/build/assets/app-y6mvR-xi.js`).
- **Pint & Full Test Suite (`vendor\bin\pint --dirty --format agent` + `php artisan test --compact`)**: **135 passed (1539 assertions)** trong **21.36s** (**100% PASS** — bảo toàn 134 tests / 1521 assertions trước đó + 1 test / 18 assertions mới Phase 24.1).
