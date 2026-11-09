# Phase 18 Checklist - Automated Email Notifications & Queue Module

## Bước 1: Tạo các lớp Event
- [x] Tạo `app/Events/AppointmentCreated.php` chứa `public Appointment $appointment`, sử dụng `Dispatchable, SerializesModels`.
- [x] Tạo `app/Events/AccessoryOrderCreated.php` chứa `public AccessoryOrder $order`, sử dụng `Dispatchable, SerializesModels`.

## Bước 2: Tạo các lớp Mailable
- [x] Tạo `app/Mail/Customer/AppointmentConfirmationMail.php` nhận `Appointment $appointment`, subject chuẩn hóa, view `emails.customer.appointment-confirmation`.
- [x] Tạo `app/Mail/Admin/AppointmentAlertMail.php` nhận `Appointment $appointment`, subject chuẩn hóa kèm tên khách, view `emails.admin.appointment-alert`.
- [x] Tạo `app/Mail/Customer/AccessoryOrderConfirmationMail.php` nhận `AccessoryOrder $order`, subject kèm tên phụ kiện, view `emails.customer.accessory-order-confirmation`.
- [x] Tạo `app/Mail/Admin/AccessoryOrderAlertMail.php` nhận `AccessoryOrder $order`, subject báo đơn mới, view `emails.admin.accessory-order-alert`.

## Bước 3: Tạo các lớp Queued Listener
- [x] Tạo `app/Listeners/SendAppointmentNotifications.php` implement `ShouldQueue`, gán `public string $queue = 'notifications'; public int $tries = 3;`.
- [x] Triển khai eager-load `loadMissing(['product', 'user'])`, kiểm tra null-safety cho email khách hàng, gửi mail cho khách (nếu có email) và luôn gửi mail cho admin showroom.
- [x] Tạo `app/Listeners/SendAccessoryOrderNotifications.php` implement `ShouldQueue`, gán `public string $queue = 'notifications'; public int $tries = 3;`.
- [x] Triển khai eager-load `loadMissing('product')`, kiểm tra null-safety cho `customer_email`, gửi mail cho khách (nếu có email) và luôn gửi mail cho admin showroom.

## Bước 4: Thiết kế Blade Templates cho Email (BMW Dark Luxury)
- [x] Tạo `resources/views/emails/layouts/bmw-transactional.blade.php` chuẩn container 600px, nền `#09090b`, thẻ card `#121215`, viền `1px solid #27272a`, font Inter/Be Vietnam Pro, 0px border-radius, footer thông tin showroom & hotline 24/7.
- [x] Tạo `resources/views/emails/customer/appointment-confirmation.blade.php` hiển thị đầy đủ thông tin lịch hẹn, thời gian, dòng xe, chi nhánh showroom và nút CTA.
- [x] Tạo `resources/views/emails/admin/appointment-alert.blade.php` dạng bảng Lead tổng hợp thông tin liên hệ và nút mở quản trị lịch hẹn `/admin/appointments`.
- [x] Tạo `resources/views/emails/customer/accessory-order-confirmation.blade.php` chi tiết phụ kiện, đơn giá, số lượng, tổng tiền và địa chỉ nhận hàng.
- [x] Tạo `resources/views/emails/admin/accessory-order-alert.blade.php` tóm tắt đơn hàng phụ kiện và liên kết trực tiếp tới `/admin/accessory-orders/{id}`.

## Bước 5: Tích hợp Dispatch Event tại Controllers & Cấu hình
- [x] Cập nhật `app/Http/Controllers/Client/AppointmentController.php` bắn event `AppointmentCreated` sau khi lưu appointment và ghi nhận touchpoint.
- [x] Cập nhật `app/Http/Controllers/AccessoryOrderController.php` bắn event `AccessoryOrderCreated` sau khi lưu đơn và ghi nhận touchpoint.
- [x] Cập nhật `config/mail.php` bổ sung `'showroom_admin' => env('MAIL_SHOWROOM_ADMIN', 'advisor@bmw.com')`.
- [x] Cập nhật `.env.example` bổ sung `MAIL_SHOWROOM_ADMIN="advisor@bmw.com"`.

## 4 Hiệu chỉnh kỹ thuật bổ sung
- [x] Gán cứng `public string $queue = 'notifications';` trên tất cả 4 lớp Mailable để đồng bộ hóa hàng đợi.
- [x] Bổ sung fallback an toàn cho Subject và nội dung Mailable khi `product` là null (`$order->product?->name ?? 'Phụ kiện chính hãng BMW'`).
- [x] Dùng `Model::create()` với dữ liệu hoàn chỉnh thay vì Factory trong `NotificationFlowTest` để kiểm thử độc lập và ổn định.
- [x] Cập nhật đồng bộ cấu hình trong `.env.example`.

## Bước 6: Kiểm thử và Nghiệm thu
- [x] Tạo `tests/Feature/Notification/NotificationFlowTest.php` với 6 test cases bao phủ toàn diện event dispatching, queuing và null-safety.
- [x] Chạy `php vendor/bin/pint --format agent`: passed 100%.
- [x] Chạy `php artisan optimize:clear`: xóa sạch cache cấu hình, routes, events, views.
- [x] Chạy `php artisan test --compact tests/Feature/Notification/NotificationFlowTest.php`: 6 passed (14 assertions).
- [x] Chạy toàn bộ test suite `php artisan test`: 114 passed (1147 assertions) — PASS 100%.
