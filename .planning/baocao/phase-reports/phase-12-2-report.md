# Phase 12.2 - Product Image Expansion for BMW 330i Sedan

## Audit Summary

- Product image model:
  - `Product::images()` exists.
  - `Product::primaryImage()` exists.
  - `Product::displayImageUrl()` exists.
  - `ProductImage` stores `product_id`, `path`, `is_primary`, `sort_order`.
- BMW 330i current image count before Phase 12.2:
  - 1 record.
  - primary: `images/cars/330i.png`.
- Image storage path:
  - existing assets are under `public/images/cars`.
  - `public/storage` is a junction to old `C:\Users\thaib\Downloads\tmdt_laravel\storage\app\public`.
  - Phase 12.2 uses `public/images/cars/330i` to avoid storage symlink risk.
- Detail page image usage before:
  - hero, design, technology and gallery all fell back to the same image when only one image existed.
- Risks:
  - storage symlink points to old folder.
  - no `role` column exists on `product_images`.
  - section repetition happens when image count is too low.

## Changes

- New images added:
  - `public/images/cars/330i/hero-front-three-quarter.png`
  - `public/images/cars/330i/side-profile.png`
  - `public/images/cars/330i/rear-three-quarter.png`
  - `public/images/cars/330i/cockpit-interior.png`
  - `public/images/cars/330i/design-detail-wheel-light.png`
  - `public/images/cars/330i/lifestyle-showroom.png`
  - `public/images/cars/330i/urban-motion.png`
  - `public/images/cars/330i/studio-front-three-quarter.png`
- Image mapping strategy:
  - hero: primary image.
  - design: image index 1.
  - technology: image index 3.
  - detail/gallery: ordered image collection.
  - lifestyle: image index 5.
  - fallback: first available image via `displayImageUrl()`.
- Detail page rendering changes:
  - `resources/views/products/show.blade.php` now uses `detailImageSet()`.
  - gallery renders up to 8 image URLs and skips duplicate lifestyle image in the secondary grid.
- Helper/model changes:
  - added `Product::detailImageSet()`.
- Seeder changes:
  - added `database/seeders/Bmw330iImageSeeder.php`.
  - updated `DatabaseSeeder` to call `Bmw330iImageSeeder` after `ProductSeeder`.
- ProductImage records:
  - after running `php artisan db:seed --class=Bmw330iImageSeeder`, BMW 330i has 9 image records.
  - new primary: `images/cars/330i/hero-front-three-quarter.png`.

## Preserved Logic

- Booking:
  - `appointments.create` remains unchanged.
  - `type=test_drive` remains available for car detail.
- Quote:
  - `type=quote` remains available.
- Compare:
  - `products.compare` remains unchanged.
  - compare now uses the new 330i primary image through existing `displayImageUrl()`.
- Routes:
  - `/catalog`
  - `/catalog/{product}`
  - `/compare`
- Admin unaffected:
  - no admin controllers/views changed.

## Files Changed

- `app/Models/Product.php`
- `resources/views/products/show.blade.php`
- `database/seeders/Bmw330iImageSeeder.php`
- `database/seeders/DatabaseSeeder.php`
- `tests/Feature/PublicUiPhase12_2Test.php`
- `public/images/cars/330i/hero-front-three-quarter.png`
- `public/images/cars/330i/side-profile.png`
- `public/images/cars/330i/rear-three-quarter.png`
- `public/images/cars/330i/cockpit-interior.png`
- `public/images/cars/330i/design-detail-wheel-light.png`
- `public/images/cars/330i/lifestyle-showroom.png`
- `public/images/cars/330i/urban-motion.png`
- `public/images/cars/330i/studio-front-three-quarter.png`

## Commands Run

```text
php artisan route:list | Select-String -Pattern 'catalog|product'
PASS.

rg audit commands for ProductImage/displayImageUrl/BMW 330i
PASS.

php artisan tinker read-only audit for BMW 330i images
PASS - before count 1, primary images/cars/330i.png.

php artisan make:seeder Bmw330iImageSeeder --no-interaction
PASS.

php artisan db:seed --class=Bmw330iImageSeeder --no-interaction
PASS.

php artisan tinker verification
PASS - after count 9, primary images/cars/330i/hero-front-three-quarter.png.

php artisan make:test --phpunit PublicUiPhase12_2Test --no-interaction
PASS.

php artisan view:clear
PASS.

php artisan view:cache
PASS.

vendor/bin/pint --dirty --format agent
PASS.

npm.cmd run build
PASS.

php artisan route:list --path=catalog -v
PASS - `/catalog` and `/catalog/{product}` still web routes.

php artisan test --compact tests/Feature/PublicUiPhase12_2Test.php
PASS - 3 tests / 26 assertions.

php artisan test --compact tests/Feature/PublicUiPhase12Test.php
PASS - 3 tests / 19 assertions.

php artisan test --compact
FAIL - 10 failed, 31 passed, 124 assertions.
```

## Manual / Browser QA

- BMW 330i detail:
  - hero image loads.
  - design image differs from hero.
  - technology/interior image loads.
  - gallery renders 8 distinct BMW 330i images.
  - no broken images.
  - no horizontal overflow.
- Related products:
  - product cards still render.
- Compare:
  - `/compare?ids=1,2` still renders.
  - BMW 330i compare image uses new primary image.
- Booking / quote:
  - `type=test_drive` and `type=quote` links still present on BMW 330i detail.
- Mobile:
  - BMW 330i detail has no horizontal overflow.
- Console:
  - no console errors observed during browser smoke QA.

## Known Issues

- Full suite still has the known old failures:
  - auth login redirect expected `/dashboard`, actual `/admin/products`.
  - `/register` returns 404.
  - `/settings/profile` returns 404.
  - `/settings/password` returns 404.
- These failures existed before Phase 12.2 and were not modified.

## Status

PASS CO GHI CHU.

Phase 12.2 product image expansion passed for BMW 330i Sedan, but full suite is still not clean because of pre-existing auth/register/settings failures.
