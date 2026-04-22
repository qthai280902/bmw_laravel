# Project Roadmap - Vehicle E-commerce v1.0

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
