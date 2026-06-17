<div align="center">
  <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/BMW.svg" alt="BMW Logo" width="96"/>

  <h1>BMW Showroom & CRM Lead-Gen Platform</h1>

  <p>
    Nền tảng showroom BMW trực tuyến kết hợp CRM Lead-Gen, AI Assistant, quản trị sản phẩm, lịch hẹn, đơn phụ kiện, bài viết SEO và theo dõi lịch sử tư vấn khách hàng.
  </p>

  <p>
    <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20.svg?style=for-the-badge&logo=laravel" alt="Laravel 12" />
    <img src="https://img.shields.io/badge/PHP-8.4+-777BB4.svg?style=for-the-badge&logo=php" alt="PHP 8.4+" />
    <img src="https://img.shields.io/badge/Tailwind_CSS-v4-38B2AC.svg?style=for-the-badge&logo=tailwind-css" alt="Tailwind CSS" />
    <img src="https://img.shields.io/badge/Alpine.js-8BC0D0.svg?style=for-the-badge&logo=alpine.js" alt="Alpine.js" />
    <img src="https://img.shields.io/badge/Gemini_AI-4285F4.svg?style=for-the-badge&logo=google" alt="Gemini AI" />
    <img src="https://img.shields.io/badge/MySQL%20%2F%20PostgreSQL-4479A1.svg?style=for-the-badge&logo=mysql" alt="Database" />
  </p>
</div>

---

## Tổng quan dự án

`bmw_laravel` là một hệ thống showroom BMW mô phỏng quy trình bán hàng và chăm sóc khách hàng hiện đại, gồm:

* Website public giới thiệu xe BMW, BMW Motorrad và phụ kiện.
* Trang chi tiết sản phẩm theo phong cách landing page cao cấp.
* Bộ lọc catalog, so sánh xe, đặt lịch lái thử, yêu cầu báo giá.
* Đặt hàng phụ kiện.
* CMS bài viết “Tìm hiểu thêm”.
* Admin dashboard quản trị sản phẩm, danh mục, lịch hẹn, đơn phụ kiện, bài viết và cấu hình site.
* AI Showroom Assistant dùng Gemini API để tư vấn xe/phụ kiện/bài viết.
* Multi-key Gemini rotation để tăng độ ổn định khi gặp rate limit.
* CRM tracking lịch sử khách hỏi AI, liên kết với khách hàng khi họ đặt lịch hoặc để lại thông tin.

Dự án được xây dựng trên Laravel 12, Blade Components, Tailwind CSS v4, Alpine.js và kiến trúc Service/Action Pattern.

---

## Tính năng chính

### 1. Public BMW Showroom

* Trang chủ phong cách premium showroom.
* Catalog sản phẩm gồm:

  * BMW Cars.
  * BMW Motorrad.
  * BMW Accessories.
* Trang chi tiết sản phẩm dạng landing page:

  * Hero section.
  * Gallery.
  * Overview.
  * Design / Technology / Technical data.
  * CTA đặt lịch lái thử, nhận báo giá hoặc đặt phụ kiện.
* Hệ thống hình ảnh local cho toàn bộ sản phẩm seed.
* Responsive desktop / tablet / mobile.

---

### 2. Catalog & Compare

* Bộ lọc sản phẩm theo loại.
* Hiển thị ảnh chính/fallback an toàn.
* So sánh xe qua URL dạng:

```text
/compare?ids=1,2
```

* Normalize ID đầu vào:

  * bỏ ID trùng.
  * bỏ ID không hợp lệ.
  * chặn accessory khỏi compare nếu logic chỉ cho vehicle.
  * giữ thứ tự chọn hợp lệ.
  * giới hạn số xe so sánh.

---

### 3. Booking, Quote & Accessory Order

Public form hỗ trợ:

* Tư vấn chung.
* Đặt lịch lái thử.
* Yêu cầu báo giá.
* Đặt hàng phụ kiện.

Các form được tích hợp `ai_visitor_id` để liên kết với lịch sử tư vấn AI nếu khách đã từng chat trước đó.

---

### 4. Article CMS / Tìm hiểu thêm

* Admin quản lý bài viết.
* Hỗ trợ trạng thái draft / published.
* Public chỉ hiển thị bài published.
* Draft direct URL trả 404.
* Danh mục bài viết SEO.
* Card bài viết có ảnh, excerpt và responsive layout.

---

### 5. AI Showroom Assistant

Trợ lý AI public dùng Gemini API để hỗ trợ:

* Tư vấn xe theo nhu cầu.
* Gợi ý sedan, SUV/SAV, BMW Motorrad.
* Tư vấn BMW S1000RR và các mẫu Motorrad trong DB.
* Gợi ý phụ kiện.
* Dẫn link nội bộ:

  * xem chi tiết sản phẩm.
  * đặt lịch lái thử.
  * nhận báo giá.
  * so sánh xe.
  * đặt phụ kiện.
  * đọc bài viết.

AI Assistant không tự tạo booking/order. Người dùng vẫn phải xác nhận qua form public.

---

### 6. Gemini Multi-Key Rotation

Hệ thống hỗ trợ nhiều Gemini API key để failover khi gặp rate limit.

Hỗ trợ:

* `GEMINI_API_KEY`
* `GEMINI_API_KEYS`
* Round-robin rotation.
* Cooldown key khi gặp `RateLimitedException` / HTTP 429.
* Không retry vô hạn.
* Nếu toàn bộ key bị rate limit, trả fallback thân thiện.
* Automated tests không gọi Gemini thật.

Ví dụ `.env`:

```env
GEMINI_API_KEY=key_chinh
GEMINI_API_KEYS=key_phu_1,key_phu_2,key_phu_3

GEMINI_KEY_COOLDOWN_SECONDS=120
GEMINI_KEY_ROTATION=round_robin
```

Hoặc:

```env
GEMINI_API_KEYS=key_1,key_2,key_3,key_4,key_5
```

---

### 7. Admin CRM & AI Conversation History

Admin có module xem lịch sử người dùng tương tác với AI.

Hệ thống ghi nhận:

* `visitor_id`
* IP / masked IP / IP hash
* user agent hash
* tin nhắn user
* phản hồi assistant
* page URL / referrer
* provider/model/reason
* trạng thái session
* liên kết appointment hoặc accessory order nếu khách chuyển đổi

Luồng CRM:

```text
Khách hỏi AI
→ hệ thống lưu visitor_id + IP + messages
→ khách đặt lịch hoặc đặt phụ kiện
→ session AI được link sang appointment/order
→ admin thấy tên khách thay vì chỉ IP
```

IP gốc không bị overwrite. Admin chỉ đổi display label từ IP sang tên khách nếu đã có thông tin liên hệ.

---

### 8. Admin Panel

Admin quản lý:

* Dashboard analytics.
* Products.
* Categories.
* Appointments.
* Accessory Orders.
* Articles.
* Site Settings.
* AI Conversations.

Admin UI đã được polish lại theo hướng:

* Dark premium dashboard.
* Sidebar rõ active state.
* KPI cards.
* Tables có badge/action rõ hơn.
* Form layout thống nhất.
* Delete modal custom, không dùng confirm mặc định.
* Responsive ở mức admin-friendly.

---

## Tech Stack

### Backend

* Laravel 12
* PHP 8.4+
* Service/Action Pattern
* Form Request Validation
* Eloquent ORM
* Laravel Pint
* Laravel AI SDK

### Frontend

* Blade Components
* Tailwind CSS v4
* Alpine.js
* Vite
* Responsive UI
* Dark premium BMW-inspired design

### Database

* MySQL local/development
* PostgreSQL hoặc MySQL tùy môi trường deploy
* SQLite cho CI/testing nếu workflow cấu hình

### AI

* Gemini API
* Multi-key rotation
* Rate-limit fallback
* Public-only context
* Admin CRM conversation tracking

---

## Cấu trúc thư mục nổi bật

```text
app/
├── Ai/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   └── Ai/
├── Models/
└── Services/
    └── Ai/

resources/
├── css/
├── js/
└── views/
    ├── admin/
    ├── articles/
    ├── appointments/
    ├── products/
    ├── accessory-orders/
    └── components/

database/
├── migrations/
├── seeders/
└── factories/

.planning/
├── baocao/
├── bug/
├── kientruc/
└── phases/
```

---

## Yêu cầu môi trường

Khuyến nghị:

```text
PHP >= 8.4
Composer 2.x
Node.js >= 20
npm
MySQL hoặc PostgreSQL
```

Local gần nhất đã được kiểm thử với:

```text
PHP 8.5.x local
GitHub Actions CI: PHP 8.4
Laravel 12.x
Node 24.x local
```

---

## Cài đặt local

### 1. Clone repository

```bash
git clone https://github.com/qthai280902/bmw_laravel.git
cd bmw_laravel
```

### 2. Cài dependencies

```bash
composer install
npm install
```

Hoặc dùng npm clean install nếu đã có `package-lock.json`:

```bash
npm ci
```

### 3. Tạo file môi trường

```bash
cp .env.example .env
php artisan key:generate
```

Trên Windows PowerShell:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

### 4. Cấu hình database

Ví dụ MySQL local:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vehicle_ecommerce
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Cấu hình AI Assistant

Không bắt buộc có key nếu chỉ chạy test cơ bản. Nếu muốn dùng AI live:

```env
AI_ASSISTANT_ENABLED=true
AI_ASSISTANT_PROVIDER=gemini
AI_ASSISTANT_MODEL=gemini-2.5-flash

GEMINI_API_KEY=
GEMINI_API_KEYS=
GEMINI_KEY_COOLDOWN_SECONDS=120
GEMINI_KEY_ROTATION=round_robin
```

Không commit `.env` lên GitHub.

### 6. Chạy migration và seed

```bash
php artisan migrate --seed
```

Nếu muốn reset dữ liệu dev:

```bash
php artisan migrate:fresh --seed
```

### 7. Storage link

```bash
php artisan storage:link
```

### 8. Build frontend

Development:

```bash
npm run dev
```

Production build:

```bash
npm run build
```

### 9. Chạy server local

```bash
php artisan serve
```

Mở:

```text
http://127.0.0.1:8000
```

---

## Lệnh kiểm thử

### Kiểm tra code style

```bash
vendor/bin/pint --test
```

Tự format:

```bash
vendor/bin/pint
```

### Build frontend

```bash
npm run build
```

### Chạy test suite

```bash
php artisan test
```

### Bộ lệnh verify đầy đủ

```bash
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan view:cache
vendor/bin/pint --test
npm run build
php artisan test
```

Trên Windows:

```powershell
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan view:cache
vendor\bin\pint --test
npm.cmd run build
php artisan test
```

---

## GitHub Actions / CI

Workflow Verify & Test kiểm tra:

* Composer install.
* Laravel config/test environment.
* Pint.
* npm build.
* PHPUnit test suite.

CI dùng PHP 8.4 để tương thích với dependency lockfile.

Nếu GitHub Actions fail, kiểm tra tab Actions trên GitHub hoặc dùng GitHub CLI nếu đã cài:

```bash
gh run list --repo qthai280902/bmw_laravel --limit 3
gh run view --repo qthai280902/bmw_laravel --log-failed
```

---

## Demo credentials

Sau khi chạy seeder, kiểm tra `database/seeders/` để biết tài khoản chính xác.

Thông tin thường dùng trong môi trường dev:

```text
Admin: admin@example.com
Password: password
```

Nếu tài khoản không tồn tại, hãy tạo admin bằng seeder hoặc tinker theo logic hiện có của dự án.

---

## Bảo mật

Dự án có các nguyên tắc bảo mật sau:

* `.env` không được commit.
* Không log Gemini API key.
* Không dump toàn bộ config.
* Không đưa dữ liệu private CRM/admin vào AI prompt.
* Admin routes được bảo vệ bằng middleware auth/admin/verified.
* AI endpoint có throttle.
* Automated tests không gọi Gemini thật.
* `.env.example` chỉ chứa placeholder.

Kiểm tra `.env`:

```bash
git ls-files .env
git check-ignore -v .env
```

Kết quả an toàn:

```text
git ls-files .env
# không hiện gì
```

---

## Planning & Reports

Dự án duy trì thư mục `.planning` để ghi lại tiến độ, kiến trúc, bug và QA.

Một số khu vực quan trọng:

```text
.planning/STATE.md
.planning/kientruc/AI-ASSISTANT.md
.planning/kientruc/ADMIN-CRM.md
.planning/kientruc/SECURITY.md
.planning/kientruc/TESTING.md
.planning/baocao/final-qa/final-full-site-qa-report.md
.planning/bug/
```

---

## Trạng thái kiểm thử gần nhất

Các đợt QA gần nhất đã ghi nhận:

```text
php artisan test: PASS, 108 tests / 1128 assertions
npm run build: PASS
Pint: PASS
Final Full-Site QA: PASS CÓ GHI CHÚ
GitHub Actions workflow: đã cập nhật PHP 8.4
```

Ghi chú: cần push lên GitHub và kiểm tra Actions run mới để xác nhận hosted CI pass thật.

---

## Lưu ý về thương hiệu

Dự án được xây dựng phục vụ mục đích học tập, nghiên cứu kiến trúc Laravel E-commerce, CRM và AI Assistant.

BMW, BMW Motorrad, logo, tên xe và các hình ảnh liên quan là tài sản thương hiệu của BMW Group hoặc chủ sở hữu tương ứng. Dự án này không đại diện cho BMW Group và không dùng cho mục đích thương mại chính thức nếu chưa có quyền sử dụng thương hiệu/hình ảnh phù hợp.

---

## License

Dự án phục vụ mục đích học tập và nghiên cứu. Vui lòng kiểm tra quyền sử dụng tài nguyên hình ảnh, logo và dữ liệu thương hiệu trước khi triển khai công khai hoặc sử dụng thương mại.
