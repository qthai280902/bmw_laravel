# Bug Report: GitHub Actions Verify & Test Failed After Phase 17

## 1. Triệu chứng
- Workflow `Verify & Test` (`.github/workflows/verify.yml`) trên nhánh master luôn bị fail.
- Job failed: `laravel-tests`.
- Failed in 10 seconds: Thường cho thấy failed ở bước chuẩn bị (setup/composer install) thay vì trong lúc test logic (vốn sẽ mất nhiều thời gian hơn).

## 2. Root Cause (Nguyên nhân thật)
- Dự án đã nâng version PHP của local system lên 8.5 do `laravel/ai` SDK hoặc các dependencies liên quan yêu cầu tối thiểu PHP >= 8.3 (ví dụ Illuminate/framework require `php: ^8.3` trong composer.lock).
- Tuy nhiên, file `.github/workflows/verify.yml` vẫn đang hard-code step `Setup PHP` dùng `php-version: '8.2'`.
- Khi GitHub Actions gọi `composer install`, Composer kiểm tra version PHP và báo lỗi không thỏa mãn requirement của `composer.lock`, dẫn đến workflow fail cực nhanh (trong 10s).
- Ngoài ra, workflow thiếu bước tạo file SQLite (`database/database.sqlite`) nếu fallback test cần dùng, và thiếu bước `setup-node` cũng như `vendor/bin/pint --test` so với quy trình chạy local tiêu chuẩn.

## 3. Các file thay đổi
- `.github/workflows/verify.yml`: Nâng cấp PHP version lên 8.4 (đủ thỏa mãn `^8.3` của composer.lock), thêm setup Node, tạo file DB sqlite, bổ sung các biến môi trường test.

## 4. Vì sao fix đúng
- Đồng bộ version PHP giữa local (PHP 8.5.7) và CI (PHP 8.4) giúp `composer install` pass và package tương thích. Dùng 8.4 trong CI thay vì 8.5 để đảm bảo tính ổn định cao hơn (vì composer.lock chỉ require ^8.3).
- Sửa lại `npm install` thành `npm ci` và setup Node giúp Frontend build chuẩn hơn.
- Thêm biến env test vào workflow đảm bảo cấu hình chạy test không phụ thuộc env ẩn hoặc cache sai.

## 5. Commands đã chạy local (PASS)
```bash
composer validate
npm.cmd ci
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan view:cache
vendor\bin\pint --test
npm.cmd run build
php artisan test
```

## 6. GitHub Actions Status
- Chưa push theo yêu cầu. Chờ lệnh confirm từ USER.

## 7. Ghi chú còn lại
- Đảm bảo CI không hard-code `.env` chứa real key, chỉ dùng rỗng hoặc testing mock key.
- Testing Suite sử dụng tính năng mock AI API nên không phụ thuộc network thực hay credit.
