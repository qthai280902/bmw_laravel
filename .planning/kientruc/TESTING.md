# Testing

## Lenh thuong dung

- `php artisan test`
- `php artisan test --compact tests/Feature/DashboardTest.php`
- `php artisan test --compact tests/Feature/PublicUiPhase12Test.php`
- `php artisan test --compact tests/Feature/PublicUiPhase12_2Test.php`
- `vendor/bin/pint --dirty --format agent`
- `npm.cmd run build`
- `php artisan view:cache`

## Trang thai hien tai

- `DashboardTest` pass: 4 tests / 10 assertions.
- `PublicUiPhase12Test` pass: 3 tests / 19 assertions.
- `PublicUiPhase12_2Test` pass: 3 tests / 26 assertions.
- Phase 11 HTTP smoke `/catalog/bmw-330i-sedan`: status 200.
- Phase 12.2 browser smoke QA:
  - `/catalog/bmw-330i-sedan` renders 8 distinct BMW 330i images.
  - no broken images observed.
  - no horizontal overflow observed.
  - `/compare?ids=1,2` still renders and uses the new 330i primary image.
- Phase 12 browser smoke QA:
  - homepage no broken images / no horizontal overflow.
  - catalog no broken images / no horizontal overflow.
  - compare no broken images and no external placeholder.
  - car detail keeps test-drive, quote, compare and specs modal.
  - accessory detail has no test-drive href and uses quote/consult/contact CTAs.
- Phase 11 view compile: `view:cache` pass.
- Phase 11 Vite build: `npm.cmd run build` pass.
- Phase 12 view compile: `view:cache` pass.
- Phase 12 Vite build: `npm.cmd run build` pass.
- Phase 12.2 view compile: `view:cache` pass.
- Phase 12.2 Vite build: `npm.cmd run build` pass.
- Full suite: 10 failed, 31 passed, 124 assertions trong lan chay Phase 12.2.
- Khong duoc ghi full suite pass.

## Loi full suite con lai

Chi tiet xem `../bug/build/php-artisan-test.md`.
