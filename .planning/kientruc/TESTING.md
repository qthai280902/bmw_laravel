# Testing

## Lenh thuong dung

- `php artisan test`
- `php artisan test --compact tests/Feature/DashboardTest.php`
- `vendor/bin/pint --dirty --format agent`
- `npm.cmd run build`
- `php artisan view:cache`

## Trang thai hien tai

- `DashboardTest` pass: 4 tests / 10 assertions.
- Phase 11 HTTP smoke `/catalog/bmw-330i-sedan`: status 200.
- Phase 11 view compile: `view:cache` pass.
- Phase 11 Vite build: `npm.cmd run build` pass.
- Full suite: 10 failed, 25 passed, 79 assertions trong lan chay Phase 11.
- Khong duoc ghi full suite pass.

## Loi full suite con lai

Chi tiet xem `../bug/build/php-artisan-test.md`.
