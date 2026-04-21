# Software Requirements Specification (SRS) - Version 1

Dưới đây là danh sách các yêu cầu chi tiết cho v1.0 của hệ thống bán xe.

## 1. Module Người dùng (Customer)
- **[REQ-01-01] Đăng ký/Đăng nhập:** Sử dụng Laravel Breeze (Email, Mật khẩu).
- **[REQ-01-02] Duyệt sản phẩm:** Xem danh sách xe Ô tô & Xe máy.
- **[REQ-01-03] Tìm kiếm & Lọc:** Tìm theo tên, hãng xe (Brand), khoảng giá.
- **[REQ-01-04] Chi tiết sản phẩm:** Hiển thị thông số kỹ thuật (từ JSON spec), giá tiền, số tiền cọc yêu cầu.
- **[REQ-01-05] Giỏ hàng (Cart):** Quản lý danh sách xe định đặt cọc.
- **[REQ-01-06] Đặt cọc (Checkout):** 
    - Chọn phương thức (Bank Transfer / Online).
    - Tạo đơn hàng trạng thái `pending`.
    - Trừ tạm thời số lượng tồn kho (`stock`).
- **[REQ-01-07] Lịch sử đơn hàng:** Khách hàng xem lại các đơn đã đặt và trạng thái cọc.

## 2. Module Quản trị (Admin)
- **[REQ-02-01] Quản lý Hãng (Brands):** CRUD tên hãng, logo.
- **[REQ-02-02] Quản lý Xe (Products):** 
    - CRUD thông tin xe.
    - Cấu hình thuộc tính linh hoạt qua JSON (specs).
    - Thiết lập giá bán và mức cọc cố định (`deposit_amount`).
- **[REQ-02-03] Quản lý Đơn hàng (Orders):** 
    - Xem danh sách đơn hàng.
    - Xác nhận thanh toán cọc thủ công (cho Bank Transfer).
- **[REQ-02-04] Quản lý Người dùng:** Xem danh sách khách hàng.

## 3. Module Hệ thống (System)
- **[REQ-03-01] Tự động giải phóng Stock:** 
    - Job quét các đơn `pending` quá 24h.
    - Chuyển trạng thái đơn sang `cancelled` và hoàn lại `stock`.
- **[REQ-03-02] Lưu trữ giá:** Sử dụng `BIGINT` cho mọi trường liên quan đến tiền tệ.
- **[REQ-03-03] Xóa mềm:** Hỗ trợ `SoftDeletes` trên tất cả các bảng dữ liệu chính.
