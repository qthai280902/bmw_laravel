# BÁO CÁO NGHIỆM THU PHASE 27: TỰ ĐỘNG HÓA VÒNG ĐỜI LỊCH HẸN & EMAIL SHOWROOM (`.ICS`, SIGNED URLS, TASK SCHEDULING 24H, CHIẾN DỊCH KHUYẾN MÃI, RATE LIMITING & PHÂN QUYỀN 3 VAI TRÒ `ADMIN` / `SALES` / `MARKETING`)

**Thời gian thực hiện**: Ngày 18 Tháng 11 Năm 2026  
**Chuyên môn**: Fullstack Architect & Laravel Security/Automation Lead  
**Trạng thái**: ĐÃ HOÀN THÀNH — **166 TESTS (2020 ASSERTIONS) PASS 100%**

---

## 1. TRỤ CỘT 1: PHÂN QUYỀN 3 VAI TRÒ NHÂN SỰ SHOWROOM (`ADMIN` / `SALES` / `MARKETING`)

- **Cơ Sở Dữ Liệu, Model & Factory**:
  - Migration `2026_11_18_000001_add_role_to_users_table` bổ sung cột `role` (`string`, default `'admin'`, indexed) vào bảng `users`.
  - `App\Models\User` định nghĩa hằng số `ROLE_ADMIN`, `ROLE_SALES`, `ROLE_MARKETING`, cùng các helper `roles()`, `roleValues()`, `normalizedRole()`, `roleLabel()`, `isSuperAdmin()`, `isSales()`, `isMarketing()`, `canAccessCrm()`, `canAccessContent()`, `canManageSystem()` và query scope `scopeSalesAndAdmins()`.
  - Giữ nguyên cổng kiểm tra Allowlist `User::isAdmin()` làm điều kiện tiên quyết vào `/admin/*` (email `@bmw.com` hoặc thuộc `config('auth.admin_emails')`). `UserFactory` mặc định `role = 'admin'` và bổ sung state `sales()`, `marketing()`.
- **Middleware, Livewire & Guard Chống Tự Hạ Quyền**:
  - Tạo `App\Http\Middleware\EnsureUserHasRole` (alias `'role'` trong `bootstrap/app.php`) kết hợp `AuthorizesAdminLivewire` (`authorizeCrm()`, `authorizeContent()`, `authorizeSystemManagement()`).
  - `/dashboard`, `/admin/appointments*`, `/admin/accessory-orders*`, `/admin/ai-conversations*`, `admin.mail-preview.appointment` và `NotificationBell` chỉ cho phép `admin` + `sales` (`canAccessCrm()`).
  - `/admin/products*`, `/admin/categories*`, `/admin/articles*` và `POST /admin/customers/promotions` chỉ cho phép `admin` + `marketing` (`canAccessContent()`).
  - `GET /admin/customers` mở cho cả 3 vai trò (`admin`, `sales`, `marketing`), đồng thời chỉ hiển thị biểu mẫu phát chiến dịch khi `Auth::user()?->canAccessContent()`.
  - `/admin/users*` và `/admin/site-settings` chỉ cho phép `admin` (`canManageSystem()`).
  - `Admin\UserController@store` tạo nhân sự mới (`email_verified_at => now()`) và `Admin\UserController@updateRole` chặn admin đang đăng nhập tự hạ vai trò của chính mình xuống `sales` hoặc `marketing`.
  - `AuthenticatedSessionController@store` điều hướng `sales` về `admin.appointments.index`, các vai trò quản trị khác về `admin.products.index`.
  - `AdminNotificationDispatcher` và `HotLeadAlertMail` chỉ gửi thông báo đơn hàng / lịch hẹn / `[HOT LEAD]` tới nhóm `salesAndAdmins()`.

---

## 2. TRỤ CỘT 2: THẺ LỊCH HẸN ĐIỆN THOẠI `.ICS` (`ILLUMINATE\CONTRACTS\MAIL\ATTACHABLE`)

- `App\Models\Appointment` implements trực tiếp `Illuminate\Contracts\Mail\Attachable`, cung cấp `toMailAttachment()` và `toIcsContent()` sinh nội dung chuẩn RFC 5545 (`VCALENDAR`, `VERSION:2.0`, `VEVENT`, `UID`, `DTSTAMP`, `DTSTART`, `DTEND`, `SUMMARY`, `DESCRIPTION`, `LOCATION`) dưới tên tệp `BMW-Appointment-{$id}.ics` (`mime: text/calendar`).
- `AppointmentConfirmationMail::attachments()` đính kèm song song file `.ics` `$this->appointment` cùng hồ sơ xe `BMW-{$slug}-Brochure.html` (`text/html`).
- `AppointmentReminderMail::attachments()` đính kèm trực tiếp `$this->appointment` (`.ics`).

---

## 3. TRỤ CỘT 3: CỔNG TỰ QUẢN LÝ LỊCH HẸN KHÔNG CẦN ĐĂNG NHẬP (`SIGNED URLS`)

- Mở cặp route public `GET /appointments/{appointment}/manage` (`appointments.guest.manage`) và `POST /appointments/{appointment}/manage` (`appointments.guest.update`) dưới `middleware('signed')`.
- Bổ sung `Appointment::temporaryManageUrl()` và `Appointment::temporaryManageUpdateUrl()` với thời hạn 14 ngày; gắn nút `"XÁC NHẬN / QUẢN LÝ LỊCH HẸN"` trong cả `AppointmentConfirmationMail` và `AppointmentReminderMail`.
- `Client\GuestAppointmentManageController` và view `resources/views/appointments/guest-manage.blade.php` (BMW Dark Luxury `0px`) hỗ trợ 3 thao tác không cần tài khoản: `confirm`, `reschedule` (tự động reset `reminder_sent_at => null` để kích hoạt lại nhắc lịch 24h cho ngày hẹn mới), và `cancel`. Mọi truy cập thiếu chữ ký hoặc sai chữ ký đều bị chặn `403`.

---

## 4. TRỤ CỘT 4: THEO DÕI TRẠNG THÁI GỬI MAIL (`MESSAGESENT`) & NHẮC LỊCH TỰ ĐỘNG 24H

- Migration `2026_11_18_000002_add_email_tracking_columns_to_appointments_table` bổ sung `confirmation_email_sent_at` và `reminder_sent_at`.
- `AppointmentConfirmationMail::headers()` gắn `X-BMW-Mail-Kind => 'appointment_confirmation'` và `X-BMW-Appointment-ID`; listener `RecordAppointmentMailSent` lắng nghe `Illuminate\Mail\Events\MessageSent` để tự động cập nhật `confirmation_email_sent_at`.
- `Admin\AppointmentTable` bổ sung badge trạng thái `ĐÃ GỬI MAIL XÁC NHẬN` / `CHƯA GỬI MAIL` và action `resendConfirmationEmail()`.
- `App\Console\Commands\SendAppointmentRemindersCommand` (`appointments:send-reminders`, implements `Isolatable`) chạy định kỳ mỗi giờ (`0 * * * *` trong `routes/console.php`), gửi duy nhất 1 lần `AppointmentReminderMail` cho lịch hẹn `pending` hoặc `confirmed` nằm trong khoảng `(now(), now()->addHours(24)]`.

---

## 5. TRỤ CỘT 5: CHIẾN DỊCH GỬI EMAIL ƯU ĐÃI KHÁCH HÀNG CŨ

- Tạo Event `App\Events\PromotionCampaignDispatched` (`ShouldDispatchAfterCommit`), Listener `App\Listeners\SendPromotionCampaignEmails` (`ShouldQueue`, `ShouldBeUnique` trên queue `notifications`) và Mailable `App\Mail\CustomerPromotionMail` (`resources/views/mail/promotions/offer.blade.php`).
- `Admin\CustomerController::sendPromotions()` hỗ trợ lọc đối tượng theo `audience` (`all`, `completed`, `by_product`), `product_id`, và `only_completed`, tự động khử trùng lặp email trước khi phát từng thư cá nhân hóa.

---

## 6. TRỤ CỘT 6: RATE LIMITING, `DEFER()` & `MAIL_SHOWROOM_ADMIN` ĐA ĐỊA CHỈ

- Bổ sung `config('mail.booking_rate_limit_per_minute')` (`BOOKING_RATE_LIMIT_PER_MINUTE`, mặc định `5` ở production/app, cấu hình `60` trong `phpunit.xml` để tương thích ngược E2E và override `5` trong test Phase 27) cùng `RateLimiter::for('showroom-booking')` gắn trên `POST /booking` (`throttle:showroom-booking`), trả HTTP `429` khi vượt ngưỡng.
- `Client\AppointmentController@store` bọc `linkCustomerTouchpoint()` và `event(new AppointmentCreated($appointment))` trong `defer(...)` nhằm trả response tức thì cho khách đặt lịch.
- `AdminNotificationDispatcher::showroomAdminEmails()` phân tách danh sách email cách nhau bởi dấu phẩy từ `MAIL_SHOWROOM_ADMIN`, áp dụng đồng bộ cho `SendAppointmentNotifications` và `SendAccessoryOrderNotifications`.

---

## 7. KẾT QUẢ KIỂM CHỨNG THỰC TẾ

- **Migration (`php artisan migrate --force`)**: `2026_11_18_000001_add_role_to_users_table` (`181.11ms DONE`) & `2026_11_18_000002_add_email_tracking_columns_to_appointments_table` (`23.45ms DONE`).
- **Laravel Pint (`vendor\bin\pint --dirty --format agent`)**: `{"tool":"pint","result":"passed"}`.
- **Vite Production Build (`npm run build`)**: **PASS** (`55 modules transformed`, `public/build/manifest.json` `0.27 kB`, `public/build/assets/app-MZFuK-5S.css` `99.85 kB`, `public/build/assets/app-doQGNT_j.js` `370.25 kB` trong `5.59s`).
- **Cache Clear & Scheduler (`php artisan optimize:clear` & `php artisan schedule:list`)**:
  - `0  * * * *  php artisan appointments:send-reminders`
  - `30 3 * * *  php artisan ai:prune-conversations`
- **Kiểm thử Phase 27 (`php artisan test --compact tests/Feature/Phase27ShowroomAutomationAndRolesTest.php`)**: **6 passed (170 assertions)** trong **14.25s**.
- **Full Test Suite (`php artisan test --compact`)**: **166 passed (2020 assertions)** trong **36.15s** (**100% PASS**, bảo toàn toàn bộ tests cũ + `<= 8` SQL queries khi cache lạnh trên `/admin/accessory-orders` và `/admin/appointments`).
