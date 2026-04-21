# Project Roadmap - Vehicle E-commerce v1.0

Lộ trình phát triển hệ thống chuyên biệt cho xe hơi và xe máy, đã được cập nhật sau khi hoàn thành Phase 1.

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
- [ ] Migration: Brands, Products (với JSON specs & SoftDeletes).
- [ ] Xây dựng Admin CRUD cho Brands.
- [ ] Xây dựng Admin CRUD cho Products:
    - Giao diện nhập liệu theo Brand Design (sharp rectangles).
    - Logic lưu trữ Specifications (JSON).
- [ ] Seeders cho dữ liệu xe BMW mẫu (M5, S1000RR, i7).

## Phase 3: Public Catalog & Search Service
- [ ] Trang chủ: Hiển thị xe mới nhất, xe nổi bật (BMW Style).
- [ ] Trang danh sách: Tích hợp Bộ lọc & Tìm kiếm (Service layer).
- [ ] Trang chi tiết xe: Hiển thị specs linh hoạt từ JSON.

## Phase 4: Ordering & Stock Locking System
- [ ] Logic Giỏ hàng (Cart Service).
- [ ] Checkout Action: Tạo Order, trừ Stock, thiết lập expiry.
- [ ] Laravel Task Scheduling: Tự động hoàn stock sau 24h.
- [ ] Giao diện Thanh toán (Bank Transfer với QR placeholder).

## Phase 5: Management & Final Polish
- [ ] Giao diện Admin: Quản lý đơn đặt cọc & Xác nhận thanh toán.
- [ ] Client Profile: Xem lịch sử đơn hàng.
- [ ] Tối ưu UI/UX, hướng dẫn bàn giao hệ thống.
