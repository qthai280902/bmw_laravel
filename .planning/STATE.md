# Project State

## Current Position
- **Status:** In Progress (Phase 5) 🛠️
- **Current Phase:** 5. Management & Final Polish
- **Next Task:** Implement Admin Order Management & Client History

## Recent Activity (Phase 4 Highlights)
- Triển khai **CartService** đa chế độ (Session cho khách, DB cho thành viên) với logic Merge tự động.
- Phát triển **ProcessDepositAction** tích hợp **Stock Locking** và **Pessimistic Locking** (`lockForUpdate`).
- Vá lỗ hổng bảo mật **Deadlock** bằng kỹ thuật sắp xếp sản phẩm theo ID trước khi khóa.
- Implement **Snapshot Integrity** cho đơn hàng (lưu giá và tên xe tại thời điểm đặt).
- Cấu hình **Task Scheduler** tự động hủy đơn quá hạn và hoàn tồn kho.
- Hoàn tất bộ **Feature Tests** cho toàn bộ luồng Ordering & Stock.

## Key Decisions
- **Deadlock Prevention:** Luôn sắp xếp items theo `product_id` trước khi acquire locks.
- **Data Integrity:** Sử dụng snapshots trong `order_items` thay vì join động để bảo toàn lịch sử.
- **Stock Restoration:** Tự động hoàn stock sau 24h nếu chưa thanh toán (tối ưu hóa tỉ lệ chuyển đổi).

## Open Issues / Tech Debt
- Cần thêm UI quản lý đơn hàng cho Admin để xác nhận thanh toán thủ công.
- Tích hợp QR thanh toán thật (hiện đang dùng placeholder).
