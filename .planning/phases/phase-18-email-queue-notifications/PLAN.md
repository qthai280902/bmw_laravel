# Phase 18 Plan - Automated Email Notifications & Queue Module

## 1. Mục Tiêu & Bối Cảnh Kiến Trúc

Nâng cấp hệ thống tương tác và xác nhận tự động cho hai luồng chuyển đổi cốt lõi của website showroom BMW:
1. **Đặt lịch hẹn / Lái thử / Báo giá / Dịch vụ CRM** (`appointments`).
2. **Đặt mua phụ kiện chính hãng BMW** (`accessory_orders`).

### Kiến trúc tổng thể: Event-Driven + Asynchronous Queue

- **Tách biệt chu kỳ I/O**: Quá trình tạo dữ liệu trong Controller phản hồi ngay lập tức đến người dùng. Tác vụ gửi email được tách biệt hoàn toàn thông qua Sự kiện (Events) và Hàng đợi (Laravel Queue).
- **Hàng đợi chuyên biệt**: Sử dụng queue `notifications` với cấu hình retry tự động (`public int $tries = 3;`) và kết nối queue theo môi trường (`database`/`sync`/`redis`).
- **Giao diện BMW Dark Luxury**: Email dạng HTML Transactional tuân thủ bảng mã màu BMW Dark Luxury (`#09090b` nền chính, `#121215` bề mặt card, `1px solid #27272a` đường viền, `#ffffff` tiêu đề, `#a1a1aa` văn bản phụ, `#1C69D4` nút bấm hành động CTA, triệt để `0px border-radius`, inline CSS tương thích 100% email client).
- **Null-Safety toàn diện**: Không ném ngoại lệ khi khách hàng không điền email (`guest_email` hoặc `customer_email` null), đồng thời có fallback an toàn cho tiêu đề (Subject) và nội dung khi sản phẩm `product` là null (ví dụ các dịch vụ bảo dưỡng, thu cũ đổi mới).

---

## 2. Thiết Kế Chi Tiết Từng Tầng

### 2.1. Tầng Sự Kiện (Events)
- `App\Events\AppointmentCreated`:
  - Thuộc tính: `public Appointment $appointment`
  - Traits: `Dispatchable`, `SerializesModels`
  - Kích hoạt tại: `App\Http\Controllers\Client\AppointmentController::store()`
- `App\Events\AccessoryOrderCreated`:
  - Thuộc tính: `public AccessoryOrder $order`
  - Traits: `Dispatchable`, `SerializesModels`
  - Kích hoạt tại: `App\Http\Controllers\AccessoryOrderController::store()`

### 2.2. Tầng Hàng Đợi & Lắng Nghe (Queued Listeners)
- `App\Listeners\SendAppointmentNotifications`:
  - Thực thi: `ShouldQueue` với `public string $queue = 'notifications'; public int $tries = 3;`
  - Eager-load quan hệ an toàn: `$appointment->loadMissing(['product', 'user']);`
  - Xác định người nhận: `$recipientEmail = $appointment->user?->email ?? $appointment->guest_email;`
  - Logic gửi:
    - Nếu có email khách hàng: gửi `AppointmentConfirmationMail`.
    - Luôn gửi email cảnh báo Lead mới cho Showroom Admin (`AppointmentAlertMail`) tới địa chỉ `config('mail.showroom_admin', 'advisor@bmw.com')`.
- `App\Listeners\SendAccessoryOrderNotifications`:
  - Thực thi: `ShouldQueue` với `public string $queue = 'notifications'; public int $tries = 3;`
  - Eager-load: `$order->loadMissing('product');`
  - Logic gửi:
    - Nếu `!empty($order->customer_email)`: gửi `AccessoryOrderConfirmationMail`.
    - Luôn gửi email thông báo đơn phụ kiện mới cho Showroom Admin (`AccessoryOrderAlertMail`) tới `config('mail.showroom_admin', 'advisor@bmw.com')`.

### 2.3. Tầng Lớp Mail (Mailables)
Tất cả 4 lớp Mailable đều implement `ShouldQueue` và gán cứng `public string $queue = 'notifications';`:
1. `App\Mail\Customer\AppointmentConfirmationMail`:
   - Subject: `[BMW Showroom] Xác nhận yêu cầu {$typeLabel} — Mã hẹn #{$appointment->id}`
   - View: `emails.customer.appointment-confirmation`
2. `App\Mail\Admin\AppointmentAlertMail`:
   - Subject: `[LEAD MỚI] Yêu cầu {$typeLabel} từ {$customerName} — #{$appointment->id}`
   - View: `emails.admin.appointment-alert`
3. `App\Mail\Customer\AccessoryOrderConfirmationMail`:
   - Subject: `[BMW Showroom] Xác nhận đơn đặt phụ kiện #{$order->id} — {$productName}` (với fallback: `'Phụ kiện chính hãng BMW'`)
   - View: `emails.customer.accessory-order-confirmation`
4. `App\Mail\Admin\AccessoryOrderAlertMail`:
   - Subject: `[ĐƠN PHỤ KIỆN MỚI] Đơn hàng #{$order->id} từ {$customerName}`
   - View: `emails.admin.accessory-order-alert`

### 2.4. Tầng Giao Diện Email (Blade Templates)
- Layout gốc: `resources/views/emails/layouts/bmw-transactional.blade.php`:
  - Khung chuẩn container 600px canh giữa, nền đen sâu `#09090b`.
  - Header thương hiệu: slogan `SHEER DRIVING PLEASURE` màu xanh BMW `#1C69D4` và logo chữ `BMW SHOWROOM`.
  - Footer chuẩn hóa: Hotline 24/7 `1800-BMW-SERIES`, địa chỉ Showroom Hà Nội & TP. HCM.
- View khách hàng:
  - `resources/views/emails/customer/appointment-confirmation.blade.php`: Hiển thị chi tiết mã lịch hẹn, loại yêu cầu, dòng xe/dịch vụ, thời gian hẹn, chi nhánh và ghi chú.
  - `resources/views/emails/customer/accessory-order-confirmation.blade.php`: Hiển thị thông tin phụ kiện, số lượng, tổng tiền tạm tính, địa chỉ nhận hàng và số điện thoại liên hệ.
- View nội bộ Showroom:
  - `resources/views/emails/admin/appointment-alert.blade.php`: Bảng tóm lược thông tin khách hàng, số điện thoại, email, loại hẹn và nút CTA liên kết đến `/admin/appointments`.
  - `resources/views/emails/admin/accessory-order-alert.blade.php`: Bảng tóm lược người đặt, số điện thoại, địa chỉ, đơn giá, tổng tiền và nút CTA liên kết đến `/admin/accessory-orders/{id}`.

### 2.5. Tầng Điều Khiển & Cấu Hình
- Dispatch Events sau khi lưu dữ liệu và ghi nhận AI Touchpoint (`$tracker->linkCustomerTouchpoint(...)`).
- Cập nhật `config/mail.php` thêm `'showroom_admin' => env('MAIL_SHOWROOM_ADMIN', 'advisor@bmw.com')`.
- Cập nhật `.env.example` với biến môi trường `MAIL_SHOWROOM_ADMIN="advisor@bmw.com"`.

---

## 3. Chiến Lược Kiểm Thử Tự Động (Verification Strategy)

- **Bộ kiểm thử tính năng (`tests/Feature/Notification/NotificationFlowTest.php`)**:
  1. `test_appointment_creation_dispatches_event()`: Dùng `Event::fake([AppointmentCreated::class])`, xác nhận event được bắn kèm đúng ID sản phẩm và email.
  2. `test_accessory_order_creation_dispatches_event()`: Dùng `Event::fake([AccessoryOrderCreated::class])`, xác nhận event được bắn khi gửi đơn phụ kiện thành công.
  3. `test_appointment_listener_queues_mail_for_customer_and_admin()`: Dùng `Mail::fake()`, chạy listener trực tiếp và xác nhận cả 2 mail đều được đưa vào queue `notifications`.
  4. `test_appointment_listener_handles_null_customer_email_safely()`: Kiểm thử trường hợp khách không nhập email, xác nhận listener chạy trơn tru, không gửi mail khách, chỉ gửi mail admin và không ném ngoại lệ.
  5. `test_accessory_order_listener_queues_mail_for_customer_and_admin()`: Xác nhận đơn phụ kiện có email được đưa vào queue cho cả khách và admin.
  6. `test_accessory_order_listener_handles_null_customer_email_safely()`: Kiểm thử null email cho đơn hàng phụ kiện.
- **Kỷ luật dữ liệu**: Khởi tạo bản ghi thử nghiệm bằng `Model::create()` với đầy đủ các trường bắt buộc (`specifications`, `description`, `category_id`, ...).
- **Hàng rào bảo toàn**: Chạy toàn bộ 108 tests hiện tại của hệ sinh thái `bmw_laravel`, đảm bảo 100% test cases pass và nâng tổng số test lên 114 tests.
