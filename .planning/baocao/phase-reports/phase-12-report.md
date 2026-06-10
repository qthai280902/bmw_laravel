# Phase 12 - Public UI Overhaul + Product Flow Normalization

## Audit Summary

- Public routes kept:
  - `/`
  - `/catalog`
  - `/catalog/{product:slug}`
  - `/compare`
  - `/accessories`
  - `/booking`
  - `/services`
- Public layout:
  - `<x-app-layout>`
  - `resources/views/layouts/app.blade.php`
  - `resources/views/layouts/navigation.blade.php`
- Product type logic:
  - `products.type`
  - `App\Enums\VehicleType`
  - real values: `car`, `motorbike`, `accessory`
- Accessories:
  - identified by `type=accessory`
  - category examples: `Phu kien O to`, `Phu kien Xe may`
- Compare image root cause:
  - compare only loaded `primaryImage`
  - view emitted `Storage::url()` for seeded `images/cars/*.png`
  - public storage junction still points to old `Downloads\tmdt_laravel`
  - fallback used external `placehold.co`

## Fixes

- Added `Product::displayImageUrl()` and `Product::publicImageUrl()`.
- Public image resolver now:
  - uses selected image when available
  - prefers direct public assets like `images/cars/*.png`
  - only uses storage URL when the public storage path is actually reachable
  - falls back to internal `images/cars/hero.png`
- Added `Product::isAccessory()`.
- Updated public product queries to eager load:
  - `category`
  - `primaryImage`
  - `images`
- Updated accessory CTAs:
  - no test-drive CTA on accessory detail
  - uses `Dat hang ngay`, `Nhan bao gia`, `Lien he tu van`, `Lien he mua hang`
  - uses existing `appointments.create` quote/consult flow and contact route
- Updated vehicle CTAs:
  - kept test drive
  - kept quote
  - kept compare
  - kept specs modal
- Overhauled:
  - homepage
  - navigation
  - footer
  - catalog/listing
  - compare
  - product detail
  - accessory detail
  - product cards

## Preserved Logic

- Booking route preserved: `appointments.create`.
- Quote flow preserved: `type=quote`.
- Test drive flow preserved for car/motorbike: `type=test_drive`.
- Compare route preserved: `products.compare`.
- Compare JS key preserved: `bmw_comparison_ids`.
- Product detail route preserved: `products.show`.
- Admin dashboard Phase 10 was not changed.
- Auth/register/settings old issues were not modified.

## Files Changed

- `routes/web.php`
- `app/Http/Controllers/ProductController.php`
- `app/Models/Product.php`
- `app/Services/VehicleSearchService.php`
- `resources/css/app.css`
- `resources/views/welcome.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/navigation.blade.php`
- `resources/views/components/vehicle-card.blade.php`
- `resources/views/products/index.blade.php`
- `resources/views/products/show.blade.php`
- `resources/views/products/compare.blade.php`
- `tests/Feature/PublicUiPhase12Test.php`

## Commands Run

```text
php artisan route:list | Select-String -Pattern 'catalog|compare|appointment|booking|accessories|services'
PASS.

rg audit commands for compare, booking, image and accessory patterns
PASS.

php artisan tinker read-only checks for categories, product types and accessory slug
PASS.

php artisan make:test --phpunit PublicUiPhase12Test --no-interaction
PASS.

php artisan test --compact tests/Feature/PublicUiPhase12Test.php
PASS - 3 tests / 19 assertions.

php artisan view:clear
PASS.

php artisan view:cache
PASS - Blade templates cached successfully.

vendor/bin/pint --dirty --format agent
PASS.

php artisan route:list
PASS - 54 routes shown.

npm.cmd run build
PASS.

php artisan test --compact
FAIL - 10 failed, 28 passed, 98 assertions.
```

## Browser Smoke QA

- Homepage:
  - no broken images
  - no horizontal overflow
- Catalog:
  - no broken images
  - detail links present
  - no horizontal overflow
- Compare:
  - no broken images
  - no external placeholder text
  - detail links present
- Car detail:
  - `type=test_drive` present
  - `type=quote` present
  - compare CTA present
  - specs modal opens
- Accessory detail:
  - `type=test_drive` absent
  - `type=quote` present
  - `type=consult` present
  - `Dat hang ngay` and `Lien he mua hang` visible
  - compare CTA absent
- Mobile:
  - catalog and detail have no horizontal overflow
- Console:
  - no browser console errors observed during smoke QA.

## Known Issues

- Full test suite still has the known old failures:
  - auth login redirect expected `/dashboard`, actual `/admin/products`
  - `/register` returns 404
  - `/settings/profile` returns 404
  - `/settings/password` returns 404
- These were known before Phase 12 and were not changed in this phase.

## Status

PASS CO GHI CHU.

Phase 12 public UI and flow normalization scope passed, but the full suite is still not clean because of pre-existing auth/register/settings failures.
