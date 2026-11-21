# Phase 33 Report — Logic Tự Phục Vụ, Bảo Mật Mã Giao Dịch, Date/Time Picker Tiếng Việt, Public Vuông Sắc Nét `0px`, Phân Hệ Kế Toán Super Admin, Đại Tu Form Sản Phẩm & Dọn Sạch CRM

- **Phase:** 33
- **Completed at:** 2026-11-21 (`Asia/Ho_Chi_Minh`)
- **Status:** Completed (100% tests pass — `208 passed, 3356 assertions` in `22.53s`)

## 1. Scope Delivered
1. **Guest Self-Service Confirmation & Reschedule Invalidation (`GuestAppointmentManageController` & `guest-manage.blade.php`):**
   - Separated appointment time from confirmation timestamp: *"Đã ghi nhận xác nhận đến Showroom cho lịch hẹn `H:i d/m/Y` (xác nhận lúc `H:i d/m/Y`)."*
   - Rescheduling an already-confirmed appointment invalidates `meta_data['guest_confirmed_at']`, preserves `invalidated_guest_confirmed_at` (rendered with `line-through opacity-60`), displays an Amber notice requiring specialist review, and re-enables confirmation for the new slot.
2. **3-Tier Transaction Code Architecture (`DocumentCodeGenerator`):**
   - Public Reference Code (`customer_order_code`): `BMW-YYMMDD-XXXX` (vehicle appointments), `SRV-YYMMDD-XXXX` (service bookings), `ACC-YYMMDD-XXXX` (accessory orders) using safe alphabet `23456789ABCDEFGHJKLMNPQRSTUVWXYZ`.
   - Internal Accounting Voucher Code (`daily_sequence_code`): `CT-YYMMDD-001` (resets daily in `Asia/Ho_Chi_Minh`).
   - Internal Masked Customer Tag (`customer_internal_code`): `KH-XXXX-NN` (last 4 phone digits + booking count).
   - Stripped all raw `#ID` (`#12`, `#ACC-12`, `#BMW-00012`) and internal codes from customer-facing emails and `guest-manage.blade.php`.
   - Synchronized `CleanAndSyncShowroomDataCommand` (`showroom:clean-and-sync-data`) to upgrade legacy codes to Phase 33 formats.
3. **2-Step Vietnamese Date & Time Picker (`<x-ui.date-time-picker>`):**
   - Replaced English Flatpickr with a pure Alpine 2-step Vietnamese picker (*Hôm nay*, *Ngày mai*, *Ngày kia* + Vietnamese calendar locking past dates + 9 showroom slots `08:30`–`17:30`).
   - Automatically disables past slots on Today and resets `selectedTime` if switching from a future date to Today when the chosen morning slot has already passed in `Asia/Ho_Chi_Minh`.
4. **Public Sharp `0px` (`rounded-none`) Sync & Article Fixes:**
   - Restored `0px` (`rounded-none`) across Public pages while preserving soft rounded corners in `.admin-cockpit`, Emails, and `.guest-luxury`.
   - Removed the search input box from `/tim-hieu-them` (`article-hub.blade.php`).
   - Fixed homepage `Đọc tiếp` (`welcome.blade.php:167`) from a static `<span>` to a clickable `<a href="{{ route('articles.show', $article) }}">`.
5. **Accounting & Financial Ledgers (`/admin/accounting*` — Top Super Admin Only):**
   - Added `users.is_super_admin` (`true` only for `thai@bmw.com`), `User::isTopSuperAdmin()`, and `EnsureTopSuperAdmin` (`top_super_admin`) middleware returning `403 Forbidden` for regular `admin`, `sales`, and `marketing`.
   - Built `AccountingController`, `AccountingDailyClosing`, `/admin/accounting`, `/admin/accounting/days/{date}`, `POST /admin/accounting/days/{date}/close`, and `GET /admin/accounting/export` (UTF-8 BOM `\xEF\xBB\xBF` CSV stream).
6. **Product Create/Edit Overhaul (`resources/views/admin/products/_form.blade.php`):**
   - Added real-time VNĐ dot formatting (`2.499.000.000`) + Vietnamese text approximation (*"≈ 2,5 tỷ VNĐ"*), Vietnamese spec label mapping + 1-click BMW spec templates, Media Library primary toggle (`primary_image_id`) & image deletion (`delete_image_ids[]`) + Drag & Drop preview, and a Sticky Action Bar.
7. **CRM Data Reset Command (`showroom:reset-crm-data --force`):**
   - Built and executed `ResetCrmDataCommand` clearing `interaction_timelines`, `ai_chat_messages`, `ai_chat_sessions`, `notifications`, `accounting_daily_closings`, `appointments`, `accessory_orders`, and regular customer accounts while preserving 100% staff accounts (`thai@bmw.com`, `*@bmw.com`), `products`, `product_images`, `categories`, `articles`, and `site_settings`.

## 2. Verification Results
- `vendor\bin\pint --dirty --format agent`: `passed`
- `npm run build`: `app-DCyA5r7o.css` (`120.04 kB`), `app-Cq5J2GD5.js` (`375.78 kB`) built in `2.15s`
- `php artisan migrate --force`: `2026_11_21_000002_add_customer_internal_code_and_accounting_closings ... DONE`
- `php artisan showroom:reset-crm-data --force`: `DONE`
- `php artisan test --compact`: **`208 passed (3356 assertions)`** in **`22.53s`**
