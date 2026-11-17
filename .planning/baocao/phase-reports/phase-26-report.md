# BÁO CÁO NGHIỆM THU PHASE 26: TRUNG TÂM THÔNG BÁO ADMIN, EMAIL SHOWROOM KÈM BROCHURE XE, `HTTP::POOL()` & `LARAVEL CONTEXT`

**Thời gian thực hiện**: Ngày 17 Tháng 11 Năm 2026  
**Chuyên môn**: Fullstack Architect & Laravel Infrastructure Lead  
**Trạng thái**: ĐÃ HOÀN THÀNH — **156 TESTS (1830 ASSERTIONS) PASS 100%**

---

## 1. TRỤ CỘT 1: TRUNG TÂM THÔNG BÁO THỜI GIAN THỰC TRÊN ADMIN (`NOTIFICATIONS` + `LIVEWIRE\ADMIN\NOTIFICATIONBELL`)

- **Cơ Sở Dữ Liệu & Scope Quản Trị**:
  - Migration `2026_11_17_000002_create_notifications_table` tạo bảng `notifications` chuẩn Laravel (`uuid` primary key, morph `notifiable`, `data`, `read_at`).
  - `App\Models\User` duy trì trait `Notifiable` và bổ sung `scopeAdmins()` nhất quán logic `@bmw.com` với `isAdmin()`.
- **Hai Lớp Notification Nghiệp Vụ (`app/Notifications/Admin/`)**:
  - `NewAppointmentLeadNotification` (`database` channel): lưu trữ `kind => 'appointment_lead'`, thông tin khách/lịch hẹn/xe, phân loại `lead_priority` (`hot`/`warm`/`normal`), `lead_intent`, `ai_lead_note`, `has_ai_chat`, `ai_chat_summary` và `action_url` tìm kiếm trực tiếp trên CRM.
  - `NewAccessoryOrderNotification` (`database` channel): lưu trữ `kind => 'accessory_order'`, mã đơn, khách hàng, sản phẩm, số lượng, `total_amount` và `action_url`.
  - `App\Services\Admin\AdminNotificationDispatcher`: điều phối gửi thông báo tới tất cả tài khoản Admin một cách idempotent, bọc an toàn `try / catch` và xóa cache `admin_notifications_digest:{$admin->id}` ngay khi có lead/đơn mới.
  - Tích hợp vào `App\Observers\AppointmentObserver::created()` (chạy ngay sau `linkAppointment()` & `classifyAndSave()`) và `App\Http\Controllers\AccessoryOrderController::store()`.
- **Livewire Topbar Component (`App\Livewire\Admin\NotificationBell`)**:
  - Nhúng trực tiếp trên thanh header của `resources/views/components/admin-layout.blade.php`, bảo vệ bằng `AuthorizesAdminLivewire` (`403` với non-admin/guest).
  - Giao diện `resources/views/livewire/admin/notification-bell.blade.php` chuẩn BMW Dark Luxury `0px border-radius` (`rounded-none`), `wire:poll.15s="refreshDigest"`, chấm đỏ nhấp nháy khi có `HOT LEAD` chưa đọc, bộ lọc 3 tab (`Tất cả`, `Chưa đọc`, `HOT LEAD`), action `markAsRead()` và `markAllAsRead()`.
  - Áp dụng **chốt chặn 1 SQL query duy nhất** bên trong `Cache::remember("admin_notifications_digest:{$userId}", 60, ...)` (`limit(25)->get()`), tính toán `unread_count`, `has_unread_hot_lead` và bộ lọc trên Collection trong RAM, bảo toàn ngưỡng `<= 8` SQL queries trên `GET /admin/accessory-orders` kể cả lúc cache lạnh.

---

## 2. TRỤ CỘT 2: TỰ ĐỘNG HÓA EMAIL SHOWROOM, BROCHURE `ATTACHMENT::FROMDATA` & BROWSER PREVIEW

- **Nâng Cấp `App\Mail\Customer\AppointmentConfirmationMail`**:
  - Giữ nguyên `implements ShouldQueue` và `$queue = 'notifications'` (bảo toàn 100% `NotificationFlowTest` & `Phase23UserJourneyE2ETest`).
  - Bổ sung `attachments()` sử dụng `Attachment::fromData(...)` tự sinh tài liệu HTML thông số kỹ thuật xe (`BMW-{slug}-Brochure.html`, `text/html`) chứa giá niêm yết, tiền đặt cọc tham khảo và toàn bộ `translated_specs`; cập nhật view `resources/views/emails/customer/appointment-confirmation.blade.php` hiển thị callout hồ sơ kỹ thuật đính kèm.
- **Bổ Sung `App\Mail\Admin\HotLeadAlertMail`**:
  - Gửi tức thì từ `App\Listeners\SendAppointmentNotifications` tới email Admin & `mail.showroom_admin` khi `lead_priority === 'hot'`, tiêu đề `[HOT LEAD]`, gắn `tags: ['hot-lead']` và `metadata: ['appointment_id', 'lead_priority']`, hiển thị `ai_lead_note` & `ai_chat_summary` qua view `resources/views/emails/admin/hot-lead-alert.blade.php`.
- **Bổ Sung `App\Mail\Customer\AccessoryOrderStatusMail`**:
  - Tự động gửi từ `App\Models\AccessoryOrder::transitionToStatus()` khi trạng thái đơn thay đổi (`$previousStatus !== $status`) và có `customer_email`, hiển thị `#ACC-{id}`, sản phẩm, số lượng, đơn giá, tổng tiền VNĐ và trạng thái qua view `resources/views/emails/customer/accessory-order-status.blade.php`.
  - Bọc `try / catch` toàn diện tại `SendAppointmentNotifications`, `SendAccessoryOrderNotifications` và `AccessoryOrder::transitionToStatus()` để không bao giờ phát sinh lỗi `500` khi thiếu SMTP.
- **Cổng Xem Trước Email Cho Admin**:
  - Mở route `GET /admin/mail-preview/appointment/{appointment}` (`admin.mail-preview.appointment` dưới `['auth', 'admin']`) tại `Admin\AppointmentController::previewMail()` trả về trực tiếp `AppointmentConfirmationMail`.
  - Bổ sung liên kết **`Xem mẫu Email xác nhận`** (`target="_blank"`) trên bảng và modal của `resources/views/livewire/admin/appointment-table.blade.php`.

---

## 3. TRỤ CỘT 3: TĂNG TỐC `HTTP::POOL()` & TRUY VẾT `LARAVEL CONTEXT`

- **Concurrent Vector Sync Với `Http::pool()`**:
  - Bổ sung `embedBatch(array $texts, int $poolSize = 8)` và `embedInputBatchesConcurrently(array $inputBatches, int $poolSize = 8)` vào `App\Services\Ai\CloudflareEmbeddingService`, gửi song song các cụm request tới `@cf/google/embeddinggemma-2` kết hợp cơ chế cooldown & tự động failover sang tài khoản Cloudflare dự phòng cho từng mục lỗi.
  - Bổ sung `ShowroomKnowledgeBaseService::syncDatabaseEmbeddings()` và cập nhật `SyncAiEmbeddingsCommand` (`ai:sync-embeddings`) dùng `embedInputBatchesConcurrently()`, bảo toàn trọn vẹn assertion `Http::assertSentCount(3)` / `(6)` của `Phase24SemanticRagEmbeddingTest`.
- **Truy Vết Vận Hành Với `Laravel Context`**:
  - Gắn `Context::add('ai_visitor_id', $visitorId)` đồng nhất tại `ShowroomAssistantController::resolveVisitorId()`, `Client\AppointmentController@store` và `AccessoryOrderController@store`.
  - Gắn `Context::add('gemini_key_slot', $candidate['index'])` tại `GeminiAgentRunner::withGeminiCandidate()` và `ShowroomAssistantService::stream()`.

---

## 4. KẾT QUẢ KIỂM CHỨNG

- **Migration (`php artisan migrate --force`)**: `2026_11_17_000002_create_notifications_table` — **DONE** (`155.47ms`).
- **Laravel Pint (`vendor\bin\pint --dirty --format agent`)**: `{"tool":"pint","result":"passed"}`.
- **Vite Production Build (`npm run build`)**: **PASS** (`55 modules transformed`, `public/build/manifest.json` `0.27 kB`, `public/build/assets/app-D-Z_VYqk.css` `99.29 kB`, `public/build/assets/app-doQGNT_j.js` `370.25 kB` trong `2.10s`).
- **Cache Clear (`php artisan optimize:clear`)**: **DONE**.
- **Full Test Suite (`php artisan test --compact`)**: **156 passed (1830 assertions)** trong **31.28s** (**100% PASS** — bao gồm toàn bộ 151 tests cũ + 5 tests mới trong `tests/Feature/Phase26RealtimeNotificationsMailAndPoolTest.php`).
