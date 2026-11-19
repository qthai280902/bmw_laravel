# Báo Cáo Nghiệm Thu Phase 28 — Độ Chịu Lỗi Email (`Mail Resilience`), Tự Động Hóa Vận Hành Showroom (`Task Scheduling`) & Điều Phối Sự Kiện Nâng Cao

- **Dự án**: `bmw_laravel`
- **Ngày hoàn tất**: `2026-11-20`
- **Trạng thái**: **PASS 100%** (`173 passed`, `2273 assertions` trong `33.27s`)

---

## 1. Tổng Quan Hạng Mục Đã Triển Khai

### Nhóm A — Trải nghiệm Email Cao cấp & Khả năng Chịu lỗi (`Mail Resilience`)
1. **Failover Mailer & Cảnh báo rơi kênh dự phòng**:
   - Cập nhật `config/mail.php`: cấu hình `'failover'` đọc từ `MAIL_FAILOVER_MAILERS` (mặc định `smtp,log`) kèm `'retry_after' => 60`, và cấu hình `'reply_to'` toàn cục (tự động tách email đầu tiên khi `MAIL_SHOWROOM_ADMIN` chứa nhiều email cách nhau bằng dấu phẩy).
   - Nâng cấp `App\Listeners\RecordAppointmentMailSent` lắng nghe `Illuminate\Mail\Events\MessageSent`: kiểm tra an toàn (tương thích 100% với driver `array` và `Mail::fake()`) khi mailer `failover` bị rơi sang `LogTransport`, tự động gọi `AdminNotificationDispatcher::notifyMailDeliveryFailure(...)` cảnh báo lên chuông `NotificationBell` của nhóm `admin` & `sales`.
2. **Phản hồi 2 chiều (`replyTo`), Độ ưu tiên khẩn (`PRIORITY_HIGH`) & Plain-Text (`text:`)**:
   - Gắn `replyTo` trỏ về hộp thư đội ngũ Sales (`AppointmentConfirmationMail::salesReplyToAddress()`) và bổ sung bản văn bản thuần `text:` (không tham chiếu `$message`) cho `AppointmentConfirmationMail`, `AppointmentReminderMail`, `CustomerPromotionMail`, và `PostTestDriveFollowupMail`.
   - Gắn `Symfony\Component\Mime\Email::PRIORITY_HIGH` thông qua tham số `using:` trong `HotLeadAlertMail::envelope()`.
3. **Nhúng ảnh nội tuyến (`$message->embed()` / `$message->embedData()`)**:
   - Bổ sung `Product::localImagePath()` để xác thực tệp ảnh cục bộ trên ổ đĩa; trong `appointment-confirmation.blade.php` và `reminder.blade.php` nhúng ảnh xe trực tiếp qua `$message->embed(...)` hoặc fallback badge PNG qua `$message->embedData(...)`, được bọc kiểm tra `@if(isset($message) && method_exists($message, 'embed') && method_exists($message, 'embedData'))` để an toàn tuyệt đối cho cả gửi mail thực tế lẫn trang xem trước `/admin/mail-preview/appointment/{appointment}`.

### Nhóm B — Tự động hóa Vận hành Định kỳ (`Task Scheduling`)
1. **Dọn lịch treo quá hạn 24h & Hậu mãi sau lái thử kèm Bảng dự toán trả góp**:
   - Migration `2026_11_20_000001_add_followup_email_sent_at_to_appointments_table` thêm cột `followup_email_sent_at` vào bảng `appointments`.
   - Tạo `App\Mail\Customer\PostTestDriveFollowupMail` (`@extends('emails.layouts.bmw-concierge')`, 100% inline sans-serif + `followup-text.blade.php`) tự động tính toán và trình bày **Bảng ước tính tài chính / trả góp tham khảo** (`20%` trả trước, `80%` vay `60 tháng`, lãi suất tham khảo `8.5%/năm`, tiền góp ước tính mỗi tháng).
   - Chuẩn hóa `Appointment::transitionStatusWithAutomation()` trong `Admin\AppointmentTable`, `Admin\ServiceBookingTable` và `Admin\AppointmentController`: khi chuyển sang `completed` sẽ tự động lập lịch `Mail::to($email)->later(now()->addHours(2), ...)` đúng 1 lần (`followup_email_sent_at`).
   - Tạo lệnh Artisan `appointments:cleanup-and-followup` (`CleanupAndFollowupAppointmentsCommand implements Isolatable`) tự động hủy các lịch `pending` quá hạn > 24 giờ (lưu `meta_data['auto_cancelled_reason']`) và quét bù email hậu mãi cho các lịch `completed` chưa gửi.
2. **Báo cáo tổng kết Showroom cuối ngày lúc 18:00 (`showroom:daily-summary`)**:
   - Tạo `App\Mail\Admin\ShowroomDailySummaryMail` (`emails/admin/daily-summary.blade.php` + `daily-summary-text.blade.php`, kế thừa `bmw-concierge`) và lệnh `showroom:daily-summary` (`SendShowroomDailySummaryCommand implements Isolatable`) tổng hợp chỉ số lịch hẹn mới, `HOT LEAD`, lịch dịch vụ chăm sóc xe, lịch đã xác nhận/hoàn thành, đơn phụ kiện và tổng doanh số đơn phụ kiện trong ngày gửi tới toàn bộ `MAIL_SHOWROOM_ADMIN`.
3. **Lịch định kỳ `routes/console.php` & Docker Compose**:
   - Cấu hình toàn bộ 7 tác vụ định kỳ theo múi giờ `Asia/Ho_Chi_Minh`: `ai:prune-conversations` (`03:30`), `appointments:send-reminders` (mỗi giờ), `appointments:cleanup-and-followup` (mỗi giờ `07:00–21:00`), `showroom:daily-summary` (`18:00`, `emailOutputOnFailure`), `ai:sync-embeddings` (`02:00`, `runInBackground`), `prune-read-admin-notifications` (Chủ nhật `03:00`), và `queue:prune-failed --hours=168` (Chủ nhật `03:15`).
   - Cập nhật `docker-compose.yml` bổ sung 2 service `queue` và `scheduler` (`restart: unless-stopped`, dùng chung cấu hình `x-app-common` với `app`).

### Nhóm C — Điều phối Sự kiện Nâng cao, Mã hóa Hàng đợi & Nhật ký Kiểm toán 3 Vai trò
1. **Mã hóa Payload (`ShouldBeEncrypted`), Retry Backoff & Cảnh báo lỗi gửi thư**:
   - `SendPromotionCampaignEmails` implement `ShouldBeEncrypted`, dùng `InteractsWithQueue`, `$tries = 3`, `backoff(): [10, 60, 180]`, và hook `failed()` bắn `MailDeliveryFailureNotification` qua `AdminNotificationDispatcher::notifyMailDeliveryFailure(...)` tới riêng nhóm `admin` + `sales` (không gửi `marketing`), hiển thị badge `CẢNH BÁO MAIL` trong `NotificationBell`.
2. **Gom nhịp đồng bộ Vector AI (`SyncCatalogEmbeddingListener`)**:
   - Tạo `CatalogEmbeddingSyncRequested` (`ShouldDispatchAfterCommit`) và `SyncCatalogEmbeddingListener` (`ShouldQueue`, `debounceId()`, `withDelay()` trả về `30s` và `0s` khi chạm ngưỡng `maxWait` `120s`, kết hợp Cache token để bỏ qua các job cũ và chỉ gọi `CloudflareEmbeddingService::syncModel()` 1 lần với dữ liệu mới nhất).
   - Tích hợp vào `Admin\ProductController` & `Admin\ArticleController` thông qua `ProductObserver::withoutImmediateSync()` / `ArticleObserver::withoutImmediateSync()`, bảo toàn 100% kiểm thử đồng bộ trực tiếp Model của `Phase24SemanticRagEmbeddingTest`.
3. **Nhật ký kiểm toán 3 vai trò (`AdminActivitySubscriber`)**:
   - Migration `2026_11_20_000002_create_admin_activity_logs_table` + Model `AdminActivityLog`.
   - Đăng ký `AdminActivitySubscriber` qua `Event::subscribe(AdminActivitySubscriber::class)` trong `AppServiceProvider::boot()` (sử dụng phương thức `on*` tránh tự động đăng ký trùng lặp trong Laravel 12), ghi nhận đăng nhập/đăng xuất nhân sự nội bộ, đổi trạng thái lịch hẹn/dịch vụ, gửi chiến dịch khuyến mãi, tạo/đổi vai trò nhân sự và cập nhật giá/trạng thái sản phẩm.
   - Hiển thị bảng **"Nhật Ký Hoạt Động Nhân Sự Gần Đây"** (15 bản ghi mới nhất, `whitespace-nowrap`, chuẩn BMW Dark Luxury `0px`) trên `/admin/users`.

---

## 2. Kết Quả Kiểm Thử & Nghiệm Thu

| Hạng mục kiểm chứng | Lệnh thực thi | Kết quả |
| --- | --- | --- |
| Migration DB | `php artisan migrate --force` | **PASS** (2 migrations mới `DONE`) |
| Chuẩn mã nguồn Pint | `vendor\bin\pint --dirty --format agent` | **PASS** (`{"tool":"pint","result":"passed"}`) |
| Biên dịch tài nguyên | `npm run build` | **PASS** (`55 modules`, `app-Bd4mQXiL.css`, `app-6-NfUb3n.js`) |
| Dọn cache & lịch/sự kiện | `php artisan optimize:clear`, `schedule:list`, `event:list` | **PASS** (7 schedules, 0 duplicate listeners) |
| Kiểm thử chuyên sâu Phase 28 | `php artisan test --compact tests/Feature/Phase28MailResilienceSchedulingAndEventsTest.php` | **5 passed (102 assertions)** |
| Toàn bộ Test Suite | `php artisan test --compact` | **173 passed (2273 assertions)** trong `33.27s` |
