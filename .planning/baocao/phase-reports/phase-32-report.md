# Báo Cáo Triển Khai Phase 32 — Đồng Bộ Typography, Soft Luxury UI, Hệ Thống Mã Kép, Điều Phối & Biểu Đồ Sales, Hướng Dẫn `(?)` & Bộ Lọc Nâng Cao

- **Dự án**: `bmw_laravel`
- **Ngày hoàn thành**: 2026-11-21
- **Trạng thái**: PASS 100% (**202 passed / 3,218 assertions** trong **24.06s**)

## 1. Thống nhất Typography (`Be Vietnam Pro`), Soft Luxury & Trang Khách Tự Phục Vụ
- **1 bộ font duy nhất**: Loại bỏ hoàn toàn `JetBrains Mono`, `font-mono` và các `italic` dư thừa khỏi [`resources/css/app.css`](../../../resources/css/app.css), layouts (`app.blade.php`, `guest.blade.php`, `admin-layout.blade.php`), `admin/ai-conversations/show.blade.php` và `admin/articles/_form.blade.php`.
- **Mở khóa bo góc mềm cho Public & Shared Components**: Cập nhật `@theme` (`--radius-sm: 0.375rem` → `--radius-xl: 1rem`) và gỡ sạch `rounded-none` trên Public/shared views (`livewire/pagination/bmw`, `vendor/pagination/tailwind`, `custom-select`, `catalog-browser`, `article-hub`, `booking-form-wizard`, `accessory-order-calculator`, `services`, `products/index`, `accessory-orders/create`, `primary/secondary/danger-button`, `text-input`).
- **Tái thiết kế [`resources/views/appointments/guest-manage.blade.php`](../../../resources/views/appointments/guest-manage.blade.php)**: Chuyển sang chuẩn **Clean Minimalist Luxury** (`#F1F5F9` / `#FFFFFF` `max-w-2xl rounded-2xl`), hiển thị đầy đủ **Mã khách hàng** (`#SĐT_STT`), **Mã ngày** (`dd/mm/yyyy/NN`), **Chuyên viên phụ trách**, xác nhận tham dự, đổi giờ hẹn (`08:00`–`20:00`) và hủy lịch.

## 2. Hệ Thống Mã Kép (`#SĐT_STT` & `dd/mm/yyyy/NN`)
- **Migration**: [`2026_11_21_000001_add_dual_codes_and_assigned_to_crm_tables.php`](../../../database/migrations/2026_11_21_000001_add_dual_codes_and_assigned_to_crm_tables.php) bổ sung `customer_order_code` (unique), `daily_sequence_code` (unique), `assigned_to` (`foreignId -> users`) và composite index `(assigned_to, status)` độc lập trên `appointments` và `accessory_orders`.
- **Service [`app/Services/Crm/DocumentCodeGenerator.php`](../../../app/Services/Crm/DocumentCodeGenerator.php)**: Chuẩn hóa đầu số `+84`/`84` (11 số) về `0...`, rỗng về `0000000000`; tính STT kế tiếp bằng ép kiểu `(int)` hậu tố trong PHP; khóa đồng bộ qua `Cache::lock` + `DB::transaction` theo múi giờ `Asia/Ho_Chi_Minh`.
- **Phủ dữ liệu & Hiển thị**: Tích hợp tự động qua `static::creating` trên `Appointment` & `AccessoryOrder`; hiển thị song song Mã kép trên toàn bộ 13 view Email (HTML & `-text.blade.php`), 3 bảng CRM & Quick Inspector Drawer, `admin/accessory-orders/show.blade.php` và hỗ trợ tìm kiếm trực tiếp theo cả `#SĐT_STT` lẫn `dd/mm/yyyy/NN`.

## 3. Điều Phối Sales & Trang Thống Kê Hiệu Suất (`/admin/users/{user}/performance`)
- **Phân công tự động & thủ công**: [`app/Services/Crm/SalesAssignmentService.php`](../../../app/Services/Crm/SalesAssignmentService.php) chọn **Least-Loaded Sales** (`saveQuietly()`, giữ `null` an toàn khi chưa có nhân viên `role = sales`), kết hợp [`app/Notifications/Admin/LeadAssignedNotification.php`](../../../app/Notifications/Admin/LeadAssignedNotification.php) và badge `PHÂN CÔNG SALES` trên `NotificationBell`.
- **Caching & Invalidation**: `ShowroomCacheService::salesRoster()` kết hợp `leftJoin('users as assigned_sales', ...)` trên 3 bảng CRM giúp giữ ngân sách **<= 8 SQL queries** khi cache lạnh; tự động xóa cache khi tạo/đổi vai trò tại `UserController@store` và `UserController@updateRole`.
- **Admin điều phối nhanh & chia đều**: Chọn/chuyển Sales ngay trong Quick Inspector Drawer trên 3 bảng CRM (chỉ dành cho `admin`, non-admin trả `403`), và nút chia đều hồ sơ chưa phân công tại `POST /admin/customers/distribute-sales`.
- **Trang Hiệu suất Sales**: `GET /admin/users/{user}/performance` (`admin.users.performance`) & `GET /admin/sales/{user}/performance` (`admin.sales.performance`) tại [`resources/views/admin/users/performance.blade.php`](../../../resources/views/admin/users/performance.blade.php) gồm 4 thẻ KPI, **Biểu đồ cột nhóm SVG** (Lịch hẹn xe / Dịch vụ / Phụ kiện), **Biểu đồ Donut SVG** phân bổ trạng thái + Alpine tooltip (không dùng CDN ngoài). `admin` xem tất cả; `sales` chỉ xem của mình (`403` khi truy cập nhân sự khác).

## 4. Nút Hướng Dẫn `(?)`, Bộ Lọc Toàn Hệ Thống & Làm Sạch Dữ Liệu
- **Help Popover `(?)`**: [`app/Support/AdminPageGuideRegistry.php`](../../../app/Support/AdminPageGuideRegistry.php) & [`resources/views/components/admin/help-popover.blade.php`](../../../resources/views/components/admin/help-popover.blade.php) tích hợp vào `<x-admin.page-header>` trên mọi màn hình Admin; làm gọn `<x-admin.empty-state>` ở 3 bảng CRM.
- **Nâng cấp bộ lọc**:
  - `AppointmentTable`, `ServiceBookingTable`, `AccessoryOrderTable`: lọc `sales_id` (`Tất cả`, `Của tôi`, `Chưa phân công`, từng Sales), `date_preset` (`Hôm nay`, `7 ngày qua`, `30 ngày qua`, `Tháng này`, `Tùy chọn ngày`) và bộ đếm số bộ lọc đang bật.
  - `Admin\CustomerController`: thêm `sales_id`, `recent`, `lead_priority` + nút chia đều.
  - `Admin\ProductController`: thêm `stock_status` (`in_stock`, `low_stock`, `out_of_stock`) & `price_range` (`under_2b`, `2b_5b`, `over_5b`).
  - `Admin\AiConversationController`: thêm `lead_level` (`hot`, `warm`, `cold`) & `converted` (`yes`, `no`).
  - `admin/articles/index.blade.php`: thêm bộ đếm lọc đang bật.
- **Dọn dẹp & Seed**: [`app/Console/Commands/CleanAndSyncShowroomDataCommand.php`](../../../app/Console/Commands/CleanAndSyncShowroomDataCommand.php) (`showroom:clean-and-sync-data {--dry-run}`) và [`database/seeders/DatabaseSeeder.php`](../../../database/seeders/DatabaseSeeder.php).

## 5. Kiểm chứng & Kết quả
- `php artisan migrate --force`: `2026_11_21_000001_add_dual_codes_and_assigned_to_crm_tables` (`171.27ms DONE`).
- `vendor\bin\pint --dirty --format agent`: `passed`.
- `npm run build`: `55 modules transformed`, `public/build/assets/app-M1zqqU7L.css` (`120.28 kB`) & `public/build/assets/app-Cq5J2GD5.js` (`375.78 kB`) trong `2.58s`.
- `php artisan optimize:clear`: hoàn tất sạch.
- `php artisan showroom:clean-and-sync-data`: khởi tạo thêm `3` tài khoản Sales chuẩn, cấp mã kép bổ sung cho `9` lịch hẹn và phân bổ đều `9` hồ sơ cho đội Sales (`0` bản ghi rác).
- Đồng bộ 4 test cũ liên quan bo góc/giao diện sáng (`Phase25`, `Phase30`, `AiAssistantPhase22Test`, `Phase27`) và bổ sung [`tests/Feature/Phase32TypographyDualCodesSalesPerformanceAndHelpTest.php`](../../../tests/Feature/Phase32TypographyDualCodesSalesPerformanceAndHelpTest.php) (**6 passed / 110 assertions**).
- `php artisan test --compact`: **202 passed (3,218 assertions)** trong **24.06s** (0 failures, 0 skipped).
