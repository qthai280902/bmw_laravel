# BMW Showroom E-commerce Platform

Hệ thống thương mại điện tử cao cấp chuyên biệt cho dòng sản phẩm BMW (Ô tô & Xe máy), được xây dựng trên nền tảng Laravel 12 với ngôn ngữ thiết kế **BMW Modern High-Fidelity Dark Theme**.

![BMW Showcase](https://images.unsplash.com/photo-1617469767053-d3b508a042a2?auto=format&fit=crop&q=80&w=1200)

## 💎 Điểm nổi bật (Highlights)

- **BMW Signature Design**: Giao diện tối giản, sang trọng với 0px border-radius, font chữ Outfit và bảng màu đặc trưng của BMW.
- **Hệ thống đặt cọc (Stock Locking)**: Quy trình thanh toán đặt cọc an toàn với cơ chế khóa tồn kho thời thực (`lockForUpdate`), đảm bảo tính chính xác tuyệt đối của giao dịch.
- **After-sales Appointment**: Hệ thống đặt lịch lái thử, xem xe và bảo hành/bảo trì được tối ưu hóa cho từng dòng xe.
- **VIP Tier Optimization**: Tự động phân hạng khách hàng (Gold, Silver, Bronze) dựa trên lịch sử mua hàng, xử lý hiệu năng cao ngăn chặn N+1 queries.

## 🛠️ Công nghệ cốt lõi (Tech Stack)

- **Backend**: Laravel 12.x (PHP 8.2+)
- **Frontend**: Blade Templates + Alpine.js
- **Styling**: Tailwind CSS v4 (Dùng @theme directive)
- **Database**: MySQL với JSON specifications cho thông số xe.
- **Code Style**: Laravel Pint (PSR-12 Compliance)

## 🚀 Cấu trúc dự án (Architecture)

Dự án tuân thủ nghiêm ngặt mô hình **Service/Action Pattern**:
- **Controllers**: Giữ mỏng (Thin), chỉ điều phối các request.
- **Actions/Services**: Chứa logic nghiệp vụ cốt lõi (ví dụ: `ProcessDepositAction`, `ConfirmOrderPaymentAction`).
- **Policy/Requests**: Quản lý quyền truy cập và xác thực dữ liệu chặt chẽ.

## 📦 Hướng dẫn cài đặt (Installation)

1. **Clone repository**:
   ```bash
   git clone [your-repo-url]
   cd tmdt_laravel
   ```

2. **Cài đặt dependencies**:
   ```bash
   composer install
   npm install
   ```

3. **Cấu hình môi trường**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   > Cấu hình database trong `.env` (ví dụ: DB_DATABASE=bmw_showroom, DB_USERNAME=root, DB_PASSWORD=)

4. **Khởi tạo dữ liệu và Storage**:
   ```bash
   php artisan storage:link
   php artisan migrate:fresh --seed
   ```

5. **Biên dịch Frontend và chạy ứng dụng**:
   Mở terminal 1:
   ```bash
   npm run dev
   ```
   Mở terminal 2:
   ```bash
   php artisan serve
   ```
   Ứng dụng sẽ chạy tại: `http://127.0.0.1:8000`

## 🧪 Kiểm thử (Testing)

Dự án được bảo vệ bởi bộ Feature Tests toàn diện:
```bash
php artisan test --filter=Phase6AfterSalesTest
php artisan test --filter=Phase4OrderingSystemTest
```

## 📜 Giấy phép (License)

Dự án này được phát triển cho mục đích học tập và trình diễn công nghệ. Mọi quyền liên quan đến thương hiệu BMW thuộc về BMW Group.

---
