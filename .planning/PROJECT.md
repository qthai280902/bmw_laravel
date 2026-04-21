# Vehicle E-commerce Project (Cars & Motorbikes)

Hệ thống thương mại điện tử chuyên biệt cho kinh doanh Ô tô và Xe máy, tập trung vào trải nghiệm đặt cọc và quản lý cấu hình xe linh hoạt với ngôn ngữ thiết kế BMW Modern.

## 🎯 Tầm nhìn & Mục tiêu
- **Trải nghiệm:** Khách hàng tìm kiếm, duyệt thông tin xe chi tiết theo phong cách "Luxury Showroom".
- **Linh hoạt:** Xử lý được sự khác biệt thông số giữa Ô tô và Xe máy qua hệ thống JSON specifications.
- **Tin cậy:** Luồng đặt cọc (Deposit) minh bạch, quản lý tồn kho chính xác theo thời gian thực (Stock Locking).

## 🏗️ Tiêu chuẩn kiến trúc (Architectural Standards)

### 1. Code Style & Pattern
- **Pattern:** Service/Action Pattern. 
  - `Controllers`: Chỉ nhận Request và trả Response (Thin Controllers).
  - `Actions/Services`: Chứa toàn bộ Business Logic.
- **Validation:** 100% sử dụng `FormRequest`.
- **Data Integrity:** `SoftDeletes` cho Products, Orders, Brands.
- **Database:** Kiểu dữ liệu `json` cho thông số kỹ thuật xe.

### 2. Tech Stack
- **Backend:** Laravel 12.x + MySQL.
- **Frontend:** Pure Blade Templates + Tailwind CSS v4 (No Inertia/React).
- **Interactivity:** Alpine.js.

### 3. UI/UX Standards (BMW-Inspired)
- **Geometry:** Strict **0px border-radius** (Industrial Design).
- **Typography:** Google Fonts **Outfit**.
    - Display Headlines: Weight 300, Uppercase.
    - Navigation: Weight 900, Bold.
- **Accent:** BMW Blue (`#1C69D4`) cho interactive elements.
- **Layout:** High-contrast Showroom rhythm.

## 🛠️ Quy tắc phát triển
- Viết Feature Test cho Checkout và Stock Locking.
- Tuân thủ PSR-12 và Laravel Pint.
- Document mọi thay đổi nghiệp vụ vào `.planning/`.
