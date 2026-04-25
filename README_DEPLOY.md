# Hướng dẫn triển khai lên Laravel Cloud

Dự án đã được tối ưu hóa để sẵn sàng triển khai lên **Laravel Cloud** (cloud.laravel.com). Dưới đây là các bước bạn cần thực hiện:

## 1. Cài đặt Laravel Cloud CLI
Nếu bạn chưa cài đặt, hãy chạy lệnh sau:
```bash
curl -sLS https://laravel.cloud/install | php
```
Hoặc tải xuống từ trang chủ Laravel Cloud.

## 2. Kết nối Dự án
Chạy lệnh xác thực và kết nối repo của bạn:
```bash
cloud login
cloud repo:config
```
*Lưu ý: Tệp `.cloud/config.json` đã được tạo sẵn để lưu trữ ID ứng dụng của bạn sau khi cấu hình.*

## 3. Cấu hình Biến môi trường (.env)
Trên Dashboard của Laravel Cloud, hãy đảm bảo bạn đã thêm các biến sau:
- `APP_KEY` (Chạy `php artisan key:generate --show` để lấy)
- `APP_ENV=production`
- `APP_DEBUG=false`
- `DB_CONNECTION=mysql` (Laravel Cloud sẽ cung cấp thông tin DB)
- Các biến database khác sẽ được Laravel Cloud tự động inject nếu bạn sử dụng dịch vụ Database của họ.

## 4. Lệnh Triển khai (Deploy)
Để đẩy mã nguồn lên server:
```bash
cloud deploy
```

## 5. Script Hậu triển khai (Post-deployment)
Trong mục cấu hình Deployment trên Dashboard, bạn nên thiết lập các lệnh sau để đảm bảo dữ liệu luôn mới nhất:
```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan db:seed --class=CategorySeeder --force
php artisan db:seed --class=ProductSeeder --force
```

---
**Ghi chú:** Website BMW Showroom sử dụng Vite và Tailwind v4. Laravel Cloud sẽ tự động nhận diện và chạy `npm install && npm run build` trong quá trình build nếu phát hiện file `package.json`.
