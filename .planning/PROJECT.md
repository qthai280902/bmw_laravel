# Vehicle E-commerce Project (Cars & Motorbikes)

Hệ thống thương mại điện tử chuyên biệt cho kinh doanh Ô tô và Xe máy, tập trung vào trải nghiệm đặt cọc và quản lý cấu hình xe linh hoạt.

## 🎯 Tầm nhìn & Mục tiêu
- **Trải nghiệm:** Khách hàng tìm kiếm, duyệt thông tin xe chi tiết và đặt hàng dễ dàng.
- **Linh hoạt:** Xử lý được sự khác biệt thông số giữa Ô tô và Xe máy mà không làm phức tạp database.
- **Tin cậy:** Luồng đặt cọc (Deposit) minh bạch, quản lý tồn kho chính xác theo thời gian thực (Locking mechanism).

## 🏗️ Tiêu chuẩn kiến trúc (Architectural Standards)
Dự án tuân thủ nghiêm ngặt các tiêu chuẩn sau:

### 1. Code Style & Pattern
- **Pattern:** Service/Action Pattern. 
  - `Controllers`: Chỉ nhận Request và trả Response (Slim Controllers).
  - `Actions/Services`: Chứa toàn bộ Business Logic.
- **Validation:** 100% sử dụng `FormRequest`. Tuyệt đối không validate trong Controller.
- **Data Integrity:** 
  - Sử dụng `SoftDeletes` cho các Model quan trọng (Products, Orders, Brands, Users).
  - Giá tiền lưu bằng `BIGINT` (đơn vị VNĐ) để tránh sai số dấu phẩy động.
- **Database:** Sử dụng `json` column cho cột `specifications` trong bảng `products` để lưu thông số kỹ thuật linh hoạt.

### 2. Tech Stack
- **Backend:** Laravel 12.x + MySQL.
- **Authentication:** Laravel Breeze (Blade stack).
- **Frontend:** Blade Templates + Tailwind CSS.
- **Scheduling:** Laravel Task Scheduling cho luồng phục hồi tồn kho.

## 🛠️ Quy tắc phát triển
- Luôn viết Test (Feature Test) cho các luồng nghiệp vụ quan trọng (Checkout, Stock Lock).
- Tuân thủ PSR-12 và Laravel Pint.
- Mọi thay đổi Database phải thông qua Migration.
