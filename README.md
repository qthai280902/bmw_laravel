# BMW Showroom & CRM Lead-Gen Platform

Hệ thống Showroom Kỹ thuật số và Quản lý Khách hàng Tiềm năng (CRM Lead-Gen) cao cấp, thiết kế độc quyền cho các dòng xe BMW (Ô tô & Motorrad). Được xây dựng với kiến trúc tối ưu hiệu suất và ngôn ngữ thiết kế **Modern High-Fidelity Dark Theme (Zinc-950)**.

![BMW Showcase](https://images.unsplash.com/photo-1555096462-c1c5eb4e4d64?q=80&w=1200&auto=format&fit=crop)
> *Lưu ý: Thay thế ảnh trên bằng screenshot thực tế của dự án (`docs/hero.png`) trước khi gửi cho nhà tuyển dụng.*

## 💎 Điểm nổi bật (Core Features)

- **Premium UI/UX**: Giao diện Flat & Floating Component tối giản, bảng màu Zinc Dark Theme, typography sắc nét với font **Inter**.
- **Dynamic CRM Architecture**: Hệ thống đặt lịch thông minh xử lý đa nghiệp vụ (Trải nghiệm xe mới, Trade-in Đổi xe cũ, Dịch vụ Hậu mãi) sử dụng cấu trúc `JSON meta_data` linh hoạt.
- **Concurrency Stock Locking**: Xử lý an toàn các giao dịch đặt cọc giá trị cao với cơ chế Khóa bi quan (`pessimistic locking - lockForUpdate`), triệt tiêu hoàn toàn nguy cơ Overselling và Deadlock.
- **Admin Analytics & Performance**: Bảng điều khiển quản trị tối ưu, giải quyết triệt để N+1 Queries bằng Eager Loading diện rộng và bảo vệ bằng bọc lót Null-safe.

## 🛠️ Công nghệ cốt lõi (Tech Stack)

- **Backend**: Laravel 12.x (PHP 8.2+)
- **Frontend**: Blade Templates + Alpine.js / Vanilla JS (No-reload cascading dropdowns)
- **Styling**: Tailwind CSS v4 (Cấu hình biến cục bộ, loại bỏ inline-styles)
- **Database**: PostgreSQL (Tối ưu cho môi trường Render.com Cloud)
- **Code Quality**: Laravel Pint (PSR-12 Compliance)

## 🚀 Cấu trúc dự án (Architecture)

Dự án loại bỏ sự cồng kềnh của E-commerce truyền thống để tập trung vào luồng Lead-Gen:
- **Null-Safe Views**: View layer được thiết kế để không bao giờ Crash kể cả khi Orphan Data xuất hiện.
- **Context-Aware Components**: Các Blade Component (Form, Card) có khả năng tự thay đổi trạng thái (Dynamic State) dựa trên URL query hoặc tương tác JS của người dùng.
- **Strict Authorization**: Middleware bảo vệ Admin panel chặt chẽ, chống mass-assignment và cô lập hoàn toàn Guest users.

## 📦 Hướng dẫn cài đặt (Local Environment)

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
   > Cấu hình database kết nối tới PostgreSQL hoặc MySQL của bạn trong file `.env`.

4. **Khởi tạo dữ liệu & File System**:
   ```bash
   php artisan storage:link
   php artisan migrate:fresh --seed
   ```
   *(Lưu ý: Seeder tự động nạp hình ảnh qua Unsplash CDN để tránh lỗi chặn Hotlink).*

5. **Biên dịch Assets và chạy Server**:
   Mở terminal 1:
   ```bash
   npm run build
   ```
   Mở terminal 2:
   ```bash
   php artisan serve
   ```
   Truy cập: `http://127.0.0.1:8000`

## ☁️ Deployment (Render.com/Docker)

Dự án đã được thiết lập sẵn `Dockerfile` chuẩn Production (Apache + PHP 8.2). 
- Đảm bảo thiết lập biến môi trường `APP_ENV=production` và `APP_DEBUG=false`.
- **Lưu ý Cloud:** Mọi lệnh di chuyển dữ liệu (`migrate --force`, `vehicle:sync`) phải được cấu hình trong **Release Command** của Render, tuyệt đối không chạy ở Startup Script (CMD) để tránh khóa Database.

## 📜 Giấy phép (License)

Dự án này được phát triển cho mục đích học tập kiến trúc phần mềm và trình diễn công nghệ. Hình ảnh và thương hiệu BMW thuộc bản quyền của BMW Group.
```
