# Phase 12.3 - All Product Image Expansion

## Audit Summary

- Total products audited: 25.
- Products by type:
  - cars: 10.
  - motorbikes: 5.
  - accessories: 10.
- Products with enough images before Phase 12.3: 1 / 25.
- Products needing images before Phase 12.3: 24 / 25.
- ProductImage count before Phase 12.3 seeder: 33.
- ProductImage count after Phase 12.3 seeder: 162.
- ProductImage count after rerun: 162.
- Remote ProductImage URL count after seeder: 0.
- Storage strategy:
  - use `public/images/...` for generated/static assets.
  - avoid `public/storage` because it points to old `C:\Users\thaib\Downloads\tmdt_laravel\storage\app\public`.

## Changes

- Added 144 generated and cropped local assets:
  - 54 car images.
  - 30 motorbike images.
  - 60 accessory images.
- Added folders:
  - `public/images/cars/{product-slug}/`
  - `public/images/motorbikes/{product-slug}/`
  - `public/images/accessories/{product-slug}/`
- Added `database/seeders/ProductImageExpansionSeeder.php`.
- Updated `database/seeders/DatabaseSeeder.php`.
- Updated `database/seeders/ProductSeeder.php`.
- Added `tests/Feature/PublicUiPhase12_3Test.php`.

## Seeder Safety

- `ProductImageExpansionSeeder` is idempotent.
- It removes remote URL ProductImage records for the known expanded seeded products.
- It resets `is_primary` for each product before setting the first mapped image as primary.
- It uses `updateOrCreate(['path' => ...])` to prevent duplicate ProductImage rows.
- It keeps stable `sort_order` values.
- Rerun result:
  - before first run: 33 ProductImage records.
  - after first run: 162 ProductImage records.
  - after rerun: 162 ProductImage records.
  - duplicate paths: 0.
  - duplicate sort_order values: 0.
  - products with non-1 primary images: 0.

## Preserved Logic

- Booking: vehicle detail still includes `type=test_drive`.
- Quote: vehicle and accessory detail still include `type=quote`.
- Compare: `/compare?ids=1,2` still renders with product primary images.
- Routes: no route changes.
- Admin: no admin controller/view logic changed.

## Commands Run

```text
php artisan db:seed --class=ProductImageExpansionSeeder --no-interaction
PASS.

php artisan db:seed --class=ProductImageExpansionSeeder --no-interaction
PASS - rerun stayed at 162 ProductImage records.

php artisan view:clear
PASS.

php artisan view:cache
PASS.

vendor/bin/pint --dirty --format agent
PASS - fixed formatting in ProductImageExpansionSeeder.

npm.cmd run build
PASS.

php artisan route:list --path=catalog -v
PASS - /catalog and /catalog/{product} remain web routes.

php artisan test --compact tests/Feature/PublicUiPhase12_2Test.php
PASS - 3 tests / 26 assertions.

php artisan test --compact tests/Feature/PublicUiPhase12_3Test.php
PASS - 3 tests / 481 assertions.

php artisan test --compact tests/Feature/PublicUiPhase12Test.php
PASS - 3 tests / 19 assertions.

php artisan test --compact
FAIL - 10 failed, 34 passed, 605 assertions.
```

## Browser QA

- Car detail checked:
  - `/catalog/bmw-330i-sedan`
  - `/catalog/bmw-530i-sedan`
  - `/catalog/bmw-m3-sedan`
- Accessory detail checked:
  - `/catalog/mu-bao-hiem-bmw-system-7-carbon`
  - `/catalog/tham-lot-san-m-performance`
- Catalog checked:
  - `/catalog`
  - `/catalog?type=car`
  - `/catalog?type=motorbike`
  - `/accessories`
- Compare checked:
  - `/compare?ids=1,2`
- Viewports checked:
  - desktop 1280px.
  - mobile 390px.
  - tablet 768px.
- Result:
  - visible broken images: 0 after scroll checks.
  - horizontal overflow: false.
  - console errors: 0.
  - accessory detail has no `type=test_drive`.

## Known Issues

- Full suite still has the known old failures:
  - login redirect expected `/dashboard`, actual `/admin/products`.
  - `/register` returns 404.
  - `/settings/profile` returns 404.
  - `/settings/password` returns 404.
- These failures were not modified in Phase 12.3.

## Status

PASS CO GHI CHU.
