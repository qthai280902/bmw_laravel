# Báo Cáo Triển Khai Phase 31 — Chuẩn Hóa State Machine 1 Chiều & Tái Cấu Trúc Khối 4/5 Trong Quick Inspector Drawer

- **Dự án**: `bmw_laravel`
- **Ngày hoàn thành**: 2026-11-20
- **Trạng thái**: PASS 100% (**195 passed / 3,003 assertions** trong **39.20s**)

## 1. Khóa chặt State Machine 1 chiều ở Tầng Domain, Livewire & HTTP Controllers
- Bổ sung [`app/Exceptions/InvalidStatusTransitionException.php`](../../../app/Exceptions/InvalidStatusTransitionException.php) và hệ thống hàm kiểm soát trên [`app/Models/Appointment.php`](../../../app/Models/Appointment.php) và [`app/Models/AccessoryOrder.php`](../../../app/Models/AccessoryOrder.php):
  - `pending` → chỉ được chuyển sang `confirmed` hoặc `cancelled`.
  - `confirmed` → chỉ được chuyển sang `completed` hoặc `cancelled`.
  - `completed` & `cancelled` → trạng thái đóng (`isTerminalStatus()`), khóa mọi chuyển trạng thái nhảy cóc hoặc đảo ngược.
- Bảo toàn quyền cập nhật `admin_notes` trên [`app/Models/AccessoryOrder.php`](../../../app/Models/AccessoryOrder.php) khi giữ nguyên trạng thái đóng; chặn gửi lại VIP confirmation email trên hồ sơ đã đóng ở `Appointment::sendOfficialConfirmationEmail()`.
- Đồng bộ [`app/Http/Controllers/Client/GuestAppointmentManageController.php`](../../../app/Http/Controllers/Client/GuestAppointmentManageController.php) (`cancel` từ chối mọi trạng thái đóng) và lệnh `appointments:cleanup-and-followup` (chỉ chuyển `pending` quá hạn sang `cancelled`).
- Cập nhật `updateStatus()` & `resendConfirmationEmail()` trong `AppointmentTable`, `ServiceBookingTable`, `AccessoryOrderTable` cùng `Admin\AppointmentController@update` và `Admin\AccessoryOrderController@updateStatus` để bắt `InvalidStatusTransitionException` và báo lỗi rõ ràng thay vì sập request.
- Mở route xem trước riêng cho đơn phụ kiện: `GET /admin/mail-preview/accessory-order/{accessoryOrder}` (`admin.mail-preview.accessory-order`, hỗ trợ `?mode=confirmation` và `?mode=status`).

## 2. Tái cấu trúc UI Quick Inspector Drawer & Inline Table Actions
- Tách shared component [`resources/views/components/admin/crm-action-hub.blade.php`](../../../resources/views/components/admin/crm-action-hub.blade.php) gắn `wire:key` theo ID + trạng thái: chỉ hiển thị tối đa **2 nút hợp lệ** cho `pending`/`confirmed`, loại bỏ hoàn toàn các nút làm thay quyền tự phục vụ của khách hàng, và hiển thị thẻ khóa quy trình khi `completed`/`cancelled`.
- Tách shared component [`resources/views/components/admin/mail-preview-accordion.blade.php`](../../../resources/views/components/admin/mail-preview-accordion.blade.php) thu gọn mặc định (`x-data="{ openPreview: false }"`):
  - *Lịch hẹn Lái thử / Tư vấn*: 5 mẫu email (có `Hậu mãi trả góp`).
  - *Dịch vụ Chăm sóc xe*: 4 mẫu email (bỏ `Hậu mãi trả góp`).
  - *Đơn hàng Phụ kiện*: 2 mẫu email (`Thư xác nhận đặt phụ kiện`, `Thư cập nhật trạng thái đơn`).
- Đồng bộ Inline Table `<x-ui.custom-select>` (`allowedStatusOptions()`) và màn hình [`resources/views/admin/accessory-orders/show.blade.php`](../../../resources/views/admin/accessory-orders/show.blade.php).

## 3. Kiểm chứng & Kết quả
- Cập nhật đồng bộ 4 điểm kiểm thử cũ (`Phase26`, `Phase27`, `Phase28`, `Phase28_5`) đi tuần tự đúng vòng đời State Machine và nhãn nút mới.
- Bổ sung [`tests/Feature/Phase31CrmStateMachineAndActionHubTest.php`](../../../tests/Feature/Phase31CrmStateMachineAndActionHubTest.php): **3 passed (94 assertions)**.
- `vendor\bin\pint --dirty --format agent`: `passed`.
- `npm run build`: `55 modules transformed`, `public/build/assets/app-D76B9lLR.css` (`115.97 kB`) & `public/build/assets/app-Cq5J2GD5.js` (`375.78 kB`) trong `2.44s`.
- `php artisan optimize:clear`: hoàn tất sạch.
- `php artisan test --compact`: **195 passed (3,003 assertions)** trong **39.20s** (0 failures, 0 skipped).
