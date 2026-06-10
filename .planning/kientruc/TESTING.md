# Testing

## Lenh thuong dung

- `php artisan test`
- `php artisan test --compact tests/Feature/DashboardTest.php`
- `php artisan test --compact tests/Feature/PublicUiPhase12Test.php`
- `php artisan test --compact tests/Feature/PublicUiPhase12_2Test.php`
- `php artisan test --compact tests/Feature/PublicUiPhase12_3Test.php`
- `vendor/bin/pint --dirty --format agent`
- `npm.cmd run build`
- `php artisan view:cache`

## Trang thai hien tai

- GitHub Actions Verify & Test local reproduce/fix:
  - `composer install --no-interaction --prefer-dist --optimize-autoloader`: pass.
  - `php artisan config:clear`: pass.
  - `php artisan view:clear`: pass.
  - `php artisan view:cache`: pass.
  - `vendor/bin/pint --dirty --format agent`: pass.
  - `vendor/bin/pint --test`: pass.
  - `npm.cmd run build`: pass.
  - `php artisan test --compact`: pass, 44 tests / 628 assertions.
- Auth/register/settings tests da duoc dong bo voi behavior hien tai:
  - login redirect den `admin.products.index`.
  - public register disabled, `/register` tra 404.
  - profile route dung `/profile`.
  - password route dung `/password`.
- `DashboardTest` pass: 4 tests / 10 assertions.
- `PublicUiPhase12Test` pass: 3 tests / 19 assertions.
- `PublicUiPhase12_2Test` pass: 3 tests / 26 assertions.
- `PublicUiPhase12_3Test` pass: 3 tests / 484 assertions.
- Phase 12.3 seeder verification:
  - before first run: 33 ProductImage records.
  - after first run: 162 ProductImage records.
  - after rerun: 162 ProductImage records.
  - remote ProductImage URLs: 0.
  - duplicate paths: 0.
  - duplicate sort_order values: 0.
  - bad primary image products: 0.
- Phase 12.3 browser QA:
  - car detail pages checked for 330i, 530i and M3.
  - accessory detail pages checked for helmet and floor mats.
  - catalog, car filter, motorbike filter, accessories and compare checked.
  - mobile 390px, tablet 768px and desktop checked.
  - visible broken images: 0 after scroll checks.
  - horizontal overflow: false.
  - console errors: 0.
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
- Full suite: pass 44 tests / 628 assertions sau GitHub Actions fix.

## Loi full suite cu

Chi tiet lich su va fix xem `../bug/build/php-artisan-test.md`.
