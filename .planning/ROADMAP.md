# Project Roadmap - Vehicle E-commerce v1.0

## Current documented status

- [x] Phase 12.2 completed with notes: BMW 330i product image expansion.
- [x] BMW 330i Sedan now has 9 ProductImage records after seeding.
- [x] Product detail renders up to 8 distinct BMW 330i images.
- [x] `PublicUiPhase12_2Test` pass: 3 tests / 26 assertions.
- [x] Phase 12 completed with notes: Public UI overhaul + product flow normalization.
- [x] Compare page image bug fixed with safe product image resolver.
- [x] Accessories no longer show test-drive CTA on detail pages.
- [x] Public UI updated for homepage, nav, footer, catalog, compare and detail pages.
- [x] `PublicUiPhase12Test` pass: 3 tests / 19 assertions.
- [x] Phase 11 completed with notes: Product detail BMW-inspired redesign.
- [x] `/catalog/{product}` keeps route name `products.show`.
- [x] Product detail preserves booking, quote, compare, specs modal and gallery.
- [x] Phase 10 completed with notes: Admin Dashboard Analytics + Custom Delete Modal.
- [x] `/dashboard` now uses `Admin\DashboardController@index`.
- [x] `/dashboard` has middleware `web`, `auth`, `verified`, `admin`.
- [x] `DashboardTest` pass: 4 tests / 10 assertions.
- [ ] Full `php artisan test` pass toan bo.

## Next maintenance tasks

- Dong bo lai auth/register/settings tests voi route that.
- Hoac khoi phuc route register/settings neu do la yeu cau nghiep vu.
- Kiem tra runtime dashboard tren trinh duyet.
- Kiem tra delete modal bang thao tac thuc te.
- Dong bo lai public storage link neu can hien thi upload moi tu `storage/app/public`.
- Kiem tra them admin product images vi admin van con fallback/URL cu ngoai scope Phase 12.
- Ap dung cau truc image set tu BMW 330i cho cac model uu tien tiep theo neu can.

Lộ trình phát triển hệ thống chuyên biệt cho xe hơi và xe máy, tập trung vào trải nghiệm đặt cọc và quản lý cấu hình xe linh hoạt với ngôn ngữ thiết kế BMW Modern.

## Phase 1: Foundation, Auth & BMW Showroom UI
- [x] Khởi tạo project Laravel 12 & Cấu hình MySQL.
- [x] Gỡ bỏ hoàn toàn Inertia/React/TS để tối ưu cho Blade Stack.
- [x] Cài đặt Laravel Breeze (Blade stack) & Alpine.js.
- [x] **BMW Showroom UI Overhaul**:
    - Thiết lập Design System: 0px radius, Outfit font, BMW Blue accent.
    - Xây dựng Layout Master & Navigation bold typography.
    - Xây dựng Welcome Page cinematic với Hero & Featured Cards.
- [x] Ổn định môi trường Build (Vite v6 & Tailwind v4).
- [x] Khởi tạo Git & Cleanup middleware tàn dư.

## Phase 2: Core Data Structure & Admin CRUD
- [x] Migration: Brands, Products (với JSON specs & SoftDeletes).
- [x] Xây dựng Admin CRUD cho Brands.
- [x] Xây dựng Admin CRUD cho Products:
    - Giao diện nhập liệu theo Brand Design (sharp rectangles).
    - Logic lưu trữ Specifications (JSON).
- [x] Seeders cho dữ liệu xe BMW mẫu (M5, S1000RR, i7).

## Phase 3: Public Catalog & Comparison Matrix
- [x] Trang chủ: Hiển thị xe mới nhất, xe nổi bật (BMW Style).
- [x] Trang danh sách (Catalog): Tích hợp Bộ lọc & Tìm kiếm (Service layer).
- [x] Hệ thống So sánh xe (Comparison Matrix):
    - Normalized JSON specs matrix.
    - Alpine.js persistent selection (LocalStorage).
- [x] Trang chi tiết xe: Hiển thị media gallery & specs từ JSON.

## Phase 4: Ordering & Stock Locking System
- [x] Hệ thống Giỏ hàng (Cart Service): Dual-mode (Session/Database) với Auto-merge.
- [x] Quy trình Đặt cọc (Checkout Flow): View Cart, Checkout, Success.
- [x] Bảo mật & Integrity (Stock Locking):
    - `DB::transaction` & `lockForUpdate`.
    - Deadlock prevention (Sorting by ID).
    - Price & Name Snapshots in `order_items`.
- [x] Tự động hóa: Task Scheduling cho đơn hàng hết hạn (restore stock).
- [x] Verification: Toàn bộ luồng nghiệp vụ đã được Feature Tested.

## Phase 5: Management & Final Polish (Current)
- [ ] Giao diện Admin: Quản lý đơn đặt cọc & Xác nhận thanh toán.
- [ ] Client Profile: Xem lịch sử đơn hàng.
- [ ] Tối ưu UI/UX, hướng dẫn bàn giao hệ thống.
