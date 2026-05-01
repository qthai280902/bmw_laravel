# 1. TỔNG QUAN KIẾN TRÚC (Architecture Overview)
- Stack công nghệ: Laravel 11/12, PostgreSQL (Render), UI: Tailwind CSS (Zinc-950 Dark Theme, Floating Cards), Alpine.js/Vanilla JS.
- Mô hình: Chuyển đổi từ E-commerce truyền thống sang Hệ thống Lead-Gen CRM chuyên sâu cho ngành Ô tô.

# 2. SỰ TIẾN HÓA CỦA CƠ SỞ DỮ LIỆU (Database Evolution)
- Tóm tắt cách thiết kế DB linh hoạt: Dùng JSON `meta_data` trong bảng `appointments` để xử lý đa nghiệp vụ (Trade-in, Bảo dưỡng) mà không cần phình to schema.
- Cơ chế Lock: Áp dụng Pessimistic Locking (`lockForUpdate()`) để chống Overselling và Race Condition.

# 3. HÀNH TRÌNH PHÁT TRIỂN (Phase-by-Phase Roadmap)
- Phase 1-3: Xây dựng nền tảng E-commerce cơ bản, thiết lập Database Schema ban đầu và UI Tailwind CSS Zinc-950.
- Phase 4: Xử lý Order Integrity, áp dụng Pessimistic Locking bảo vệ tính toàn vẹn giao dịch.
- Phase 5: Xây dựng Admin Panel tiêu chuẩn, quản trị danh mục và sản phẩm.
- Phase 6-7: Mở rộng hệ thống After-sales (Lái thử, Bảo dưỡng) với nền tảng CRM.
- Phase 8: Việt hóa và Localize giao diện sang tiêu chuẩn tiếng Việt.
- Phase 9: Mở rộng Domain Logic & Component Hóa (C2C Marketplace, Dynamic Forms, Kiểm toán Admin tiêu diệt N+1 Query).

# 4. NHỮNG LỖI CHÍ MẠNG & CÁCH GIẢI QUYẾT (Critical Bugs & Fixes)
- Bug 1: Deadlock Database khi nhiều user thao tác -> Giải quyết bằng cách cưỡng chế Sorting mảng ID trước khi Transaction.
- Bug 2: Crash Null Pointer (Lỗi 500) ở trang Admin do dữ liệu Guest/Orphan Data -> Giải quyết bằng Null-safe Operator (`?->`) và `firstOrCreate()`.
- Bug 3: Lỗi 403 Forbidden chặn Hotlink ảnh từ Wikimedia -> Đổi chiến lược sang CDN Unsplash / Download Internal Storage.
- Bug 4: N+1 Query làm nghẽn Admin Panel -> Giải quyết bằng chiến dịch Eager Loading toàn diện (`with()`).

# 5. CHIẾN LƯỢC DEPLOYMENT (Môi trường Production)
- Cấu hình Docker (Apache + PHP 8.2).
- Tách biệt `Build Command` và `Release Command` trên Render.com để tránh Race Condition khi chạy Seeder/Migration trên Cloud.
