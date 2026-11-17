# BÁO CÁO NGHIỆM THU PHASE 25.2: HYBRID PERSISTENT GUEST MEMORY, ĐỒNG BỘ ĐA TAB, BÀN GIAO NGỮ CẢNH CHAT SANG LEAD CRM & TỰ ĐỘNG DỌN DẸP VPS

**Thời gian thực hiện**: Ngày 17 Tháng 11 Năm 2026  
**Chuyên môn**: Fullstack Architect & AI Infrastructure Lead  
**Trạng thái**: ĐÃ HOÀN THÀNH — **151 TESTS (1750 ASSERTIONS) PASS 100%**

---

## 1. TRỤ CỘT 1: HYBRID PERSISTENT GUEST IDENTITY (COOKIE 365 NGÀY + AUTO RE-HYDRATION)

- **Server-Set Cookie Bền Vững**:
  - `App\Http\Controllers\Ai\ShowroomAssistantController` chuẩn hóa `resolveVisitorId()` (ưu tiên: payload/query hợp lệ $\rightarrow$ cookie `bmw_ai_visitor_id` $\rightarrow$ tự sinh mới khi chat) và dùng đồng nhất cho cả 4 endpoint (`__invoke`, `stream`, `history`, `reset`), đính kèm cookie `bmw_ai_visitor_id` thời hạn **365 ngày** (`HttpOnly = true`, `SameSite = Lax`).
- **API Khôi Phục Hội Thoại**:
  - Bổ sung route `GET /ai/showroom-assistant/history` (`ai.showroom-assistant.history`) và alias `GET /api/ai/assistant/history` (`ai.api.assistant.history`).
  - `App\Services\Ai\ShowroomAssistantService::getHistory()` và `fetchActiveHistoryRecords()` trích xuất hội thoại đang mở của `AiChatSession`, tái dựng Action Cards cho từng câu trả lời và giữ TTL mặc định `1440` giờ (60 ngày) trong `config/showroom_ai.php`.
  - Migration `2026_11_17_000001_add_ai_handover_columns_to_appointments_table` bổ sung `reset_after_message_id` trên `ai_chat_sessions` để nút **Làm mới** đánh dấu đóng phiên hiển thị client bền vững trong DB mà vẫn bảo toàn dữ liệu kiểm toán CRM.
- **Tự Động Khôi Phục Frontend**:
  - `resources/views/components/public/ai-assistant-widget.blade.php` truyền `historyEndpoint` và giữ nguyên hợp đồng không chứa `rounded-` / `x-html`.
  - `resources/js/app.js` bổ sung `rehydrateFromServer()` tự gọi `/history` khi `localStorage` trống tin nhắn user, nạp lại đoạn chat + Action Cards và đồng bộ `visitorId`.

---

## 2. TRỤ CỘT 2: ĐỒNG BỘ ĐA TAB THỜI GIAN THỰC (`BroadcastChannel` + `storage` EVENT)

- `resources/js/app.js` tích hợp `setupCrossTabSync()`, `broadcastSync()`, `syncVisitorId()` và `syncFromLocalStorage()` lắng nghe đồng thời `BroadcastChannel('bmw_ai_assistant_sync')` và sự kiện `window.addEventListener('storage', ...)` trên khóa state & visitor ID.
- Trước khi gửi câu hỏi mới trong `send()`, widget tự động đồng bộ trạng thái mới nhất từ `localStorage`; khi nhận xong phản hồi (JSON hoặc SSE `done`) hay bấm **Làm mới**, phát đồng bộ sang mọi tab khác mà giữ nguyên đầy đủ chữ ký chuỗi của `Phase17AiConversationTest` và `AiAssistantPhase22Test`.

---

## 3. TRỤ CỘT 3: BÀN GIAO NGỮ CẢNH CHAT AI SANG LEAD CRM & GIAO DIỆN GUEST-FIRST

- **Schema & Models**:
  - Migration `2026_11_17_000001_add_ai_handover_columns_to_appointments_table` bổ sung `ai_conversation_id` (nullable, index) và `ai_chat_summary` (nullable text) trên `appointments`.
  - `App\Models\Appointment` bổ sung `aiChatSession()` (`BelongsTo`); `App\Models\AiChatSession` bổ sung `appointments()` (`HasMany`).
- **Liên Kết Idempotent & Chấm Điểm Lead Kết Hợp Chat**:
  - `App\Services\Ai\AiConversationTracker`: Bổ sung `linkAppointment()` và `summarizeSession()`, kết hợp guard idempotent đầu `linkCustomerTouchpoint()` để `AppointmentObserver::created()` và `AppointmentController@store` không bao giờ liên kết trùng hoặc phá vỡ fallback `ip_recent` của Phase 17.
  - `App\Services\Ai\AppointmentLeadClassifier` nhận thêm ngữ cảnh `ai_chat_summary` để chấm điểm `lead_priority` sát thực tế.
  - Form `/booking` (`resources/views/livewire/booking-form-wizard.blade.php` & `AppointmentController@store`) tự nhận `ai_visitor_id` từ cookie `bmw_ai_visitor_id` ngay cả khi client mất `localStorage`.
- **Admin CRM Chat Inspector Modal & Điều Hướng Guest-First**:
  - `App\Livewire\Admin\AppointmentTable` & `resources/views/livewire/admin/appointment-table.blade.php` hiển thị badge **`ĐÃ CHAT AI`**, tóm tắt `ai_chat_summary`, nút **`Xem lịch sử chat AI`** và Drawer/Modal `0px border-radius` xem toàn bộ đoạn chat của Lead.
  - `resources/views/layouts/navigation.blade.php` chuẩn hóa `auth()->user()->isAdmin()`; `resources/views/layouts/app.blade.php` bổ sung liên kết **`Cổng quản trị`** kín đáo ở footer.

---

## 4. TRỤ CỘT 4: TỰ ĐỘNG DỌN DẸP DATABASE VPS (`ai:prune-conversations`)

- `App\Console\Commands\PruneAiConversationsCommand` (`php artisan ai:prune-conversations {--days=60}`): xóa theo transaction các `AiChatSession` và `AiChatMessage` vãng lai quá hạn nhưng bảo toàn vĩnh viễn các phiên gắn với `Appointment`.
- Đăng ký lịch chạy tự động `dailyAt('03:30')` trong `routes/console.php`.

---

## 5. KẾT QUẢ KIỂM CHỨNG

- **Migration (`php artisan migrate --force`)**: `2026_11_17_000001_add_ai_handover_columns_to_appointments_table` — **DONE** (`151.70ms`).
- **Lệnh dọn dẹp VPS (`php artisan ai:prune-conversations --days=60`)**: **PASS** (`Đã dọn dẹp 0 phiên hội thoại vãng lai quá 60 ngày (0 tin nhắn). Bảo toàn 0 phiên hội thoại gắn với Lead CRM.`).
- **Laravel Pint (`vendor\bin\pint --dirty --format agent`)**: `{"tool":"pint","result":"passed"}`.
- **Vite Production Build (`npm run build`)**: **PASS** (`55 modules transformed`, `public/build/manifest.json` 0.27 kB, `public/build/assets/app-B0B2lJgg.css` 98.19 kB, `public/build/assets/app-doQGNT_j.js` 370.25 kB trong `1.63s`).
- **Cache Clear (`php artisan optimize:clear`)**: **DONE**.
- **Full Test Suite (`php artisan test --compact`)**: **151 passed (1750 assertions)** trong **33.16s** (**100% PASS** — bao gồm toàn bộ 146 tests cũ + 5 tests mới trong `tests/Feature/Phase25_2HybridPersistentGuestAndCrmHandoverTest.php`).
