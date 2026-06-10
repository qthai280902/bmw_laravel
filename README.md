<div align="center">
  <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/BMW.svg" alt="BMW Logo" width="100"/>
  <h1>BMW Premium E-Commerce & CRM System</h1>
  <p>Hệ thống Thương mại Điện tử và Quản lý Khách hàng (CRM Lead-Gen) cao cấp, chuyên biệt cho dòng sản phẩm BMW Ô tô & Motorrad. Được xây dựng trên nền tảng Laravel 12 với ngôn ngữ thiết kế Modern High-Fidelity Dark Theme.</p>

  <p>
    <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20.svg?style=for-the-badge&logo=laravel" alt="Laravel 12" />
    <img src="https://img.shields.io/badge/PHP-8.2+-777BB4.svg?style=for-the-badge&logo=php" alt="PHP 8.2" />
    <img src="https://img.shields.io/badge/Tailwind_CSS-v4-38B2AC.svg?style=for-the-badge&logo=tailwind-css" alt="Tailwind CSS" />
    <img src="https://img.shields.io/badge/Alpine.js-8BC0D0.svg?style=for-the-badge&logo=alpine.js" alt="Alpine.js" />
    <img src="https://img.shields.io/badge/MySQL-4479A1.svg?style=for-the-badge&logo=mysql" alt="MySQL" />
  </p>
</div>

---

## 🌟 Tính Năng Cốt Lõi (Core Features)

### 1. Showroom Trực Tuyến & Đặt Cọc (E-commerce)
- **Danh mục Sản phẩm:** Trưng bày các dòng xe BMW Ô tô, BMW Motorrad và Phụ kiện chính hãng.
- **Stock Locking:** Hệ thống đặt cọc (Deposit) an toàn với cơ chế khóa tồn kho thời gian thực (`Pessimistic Locking`), chống Overselling tuyệt đối.
- **Dynamic Attributes:** Quản lý thông số kỹ thuật xe linh hoạt bằng JSON type.

### 2. Dịch vụ Hậu mãi & CRM (Aftersales & CRM)
- **Hệ thống Lịch hẹn (Appointments):** Đặt lịch đa dạng bao gồm:
  - Lái thử & Xem xe (Test Drive).
  - Dịch vụ Chăm sóc / Bảo dưỡng (Maintenance).
  - Thu cũ đổi mới (Trade-in) với Dynamic Cascading Dropdowns.
- **CRM Meta Data:** Xử lý linh hoạt thông tin khách hàng (như thông tin xe hiện tại đang đi, yêu cầu đặc biệt) thông qua cột `meta_data` JSON.

### 3. Chợ Xe Cũ Đồng Cấp (C2C Marketplace)
- Cho phép khách hàng đăng bán xe cũ thông qua hệ thống kiểm duyệt.
- Tự động map xe cũ vào biến thể của sản phẩm xe mới hiện có (Ký sinh - Parasitic Model), giúp tái sử dụng thông số kỹ thuật và tối ưu UI/UX.

### 4. Admin Panel (High-Performance Admin)
- Giao diện quản trị phong cách Zinc-950 mạnh mẽ.
- Tối ưu hóa Database: Triệt tiêu hoàn toàn lỗi **N+1 Query** bằng Eager Loading.
- **Null-Safe:** Toàn bộ View được bọc lót bảo vệ chống crash `500 Server Error` khi dữ liệu quan hệ bị thiếu hụt.

## 💻 Tech Stack (Công nghệ sử dụng)

- **Backend:** Laravel 12 (Service/Action Pattern)
- **Frontend:** Blade Components, Tailwind CSS v4 (@theme directive), Alpine.js cho các tương tác UI phức tạp.
- **Database:** MySQL / PostgreSQL.
- **Code Quality:** Laravel Pint (PSR-12), Eager Loading Optimization.

## 🚀 Cấu Trúc Dự Án (Architecture Pattern)

Dự án tuân thủ nghiêm ngặt mô hình **Service/Action Pattern**:
- **Controllers:** Giữ mỏng (Thin), chỉ làm nhiệm vụ tiếp nhận Request và trả về Response.
- **Actions/Services:** Đảm nhận logic nghiệp vụ cốt lõi (VD: `ProcessDepositAction`, `ConfirmOrderPaymentAction`).
- **Form Requests:** Validation tập trung và chặt chẽ.
- **Enums:** Chuẩn hóa các trạng thái (Order Status, Appointment Status, Role).

## 📦 Hướng Dẫn Cài Đặt (Installation)

1. **Clone repository về máy:**
   ```bash
   git clone https://github.com/qthai280902/bmw_laravel.git
   cd bmw_laravel
   ```

2. **Cài đặt thư viện (Dependencies):**
   ```bash
   composer install
   npm install
   ```

3. **Cấu hình môi trường (.env):**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   > ⚠️ Vui lòng mở file `.env` và cấu hình thông tin kết nối Database của bạn (ví dụ: `DB_DATABASE=bmw_showroom`, `DB_USERNAME=root`, `DB_PASSWORD=`).

4. **Khởi tạo dữ liệu và Storage:**
   ```bash
   php artisan storage:link
   php artisan migrate:fresh --seed
   ```
   > *Lệnh seed sẽ tự động tạo dữ liệu mẫu bao gồm Admin, Khách hàng, Sản phẩm BMW, Dịch vụ và dữ liệu CRM giả lập.*

5. **Biên dịch Frontend và chạy ứng dụng:**
   Mở Terminal 1 (để watch các thay đổi CSS/JS):
   ```bash
   npm run dev
   ```
   Mở Terminal 2 (để chạy server PHP):
   ```bash
   php artisan serve
   ```
   🎉 Ứng dụng của bạn hiện đã chạy tại: `http://127.0.0.1:8000`

## 🔐 Tài Khoản Truy Cập Mẫu (Demo Credentials)

Sau khi chạy lệnh `seed` thành công, bạn có thể đăng nhập bằng các tài khoản sau:
- **Admin:** `admin@example.com` / `password`
- **Khách hàng:** `user@example.com` / `password`

## 📄 Giấy Phép (License)
Dự án được xây dựng với mục đích học tập, nghiên cứu kiến trúc hệ thống E-commerce & CRM. Các thương hiệu, hình ảnh BMW thuộc bản quyền của tập đoàn BMW.
