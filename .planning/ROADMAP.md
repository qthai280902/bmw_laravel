# Project Roadmap - Vehicle E-commerce v1.0

Lộ trình phát triển hệ thống chia theo các Phase để đảm bảo tính ổn định và kiểm soát chất lượng.

## Phase 1: Foundation & Authentication
- [x] Khởi tạo project Laravel 12 & Cấu hình Database.
- [x] Cài đặt Laravel Breeze (Blade stack) cho Authentication.
- [x] Thiết lập thư mục `Actions` và `Services` cho kiến trúc Service Pattern.
- [x] Xây dựng Layout Master với Tailwind CSS.

## Phase 2: Core Data Structure & Admin CRUD
- [ ] Migration: Brands, Products (với JSON specs & SoftDeletes).
- [ ] Xây dựng Admin CRUD cho Brands.
- [ ] Xây dựng Admin CRUD cho Products (Xử lý lưu specifications JSON).
- [ ] Seeders cho dữ liệu xe mẫu (Ô tô & Xe máy).

## Phase 3: Public Catalog & User Interface
- [ ] Trang chủ: Hiển thị xe mới nhất, xe nổi bật.
- [ ] Trang danh sách: Tích hợp Bộ lọc & Tìm kiếm (Service layer).
- [ ] Trang chi tiết xe: Hiển thị specs linh hoạt từ JSON.

## Phase 4: Ordering & Stock Locking System
- [ ] Logic Giỏ hàng (Cart Service).
- [ ] Checkout Action: Tạo Order, trừ Stock, thiết lập expiry.
- [ ] Laravel Task Scheduling: Job tự động hoàn stock sau 24h.
- [ ] Giao diện Trang thanh toán (Bank Transfer UI).

## Phase 5: Order Management & Final Polish
- [ ] Giao diện Admin: Quản lý đơn đặt cọc, xác nhận thanh toán.
- [ ] Client: Xem lịch sử đơn hàng.
- [ ] Tối ưu UI/UX, fix bugs & Review kiến trúc cuối cùng.
