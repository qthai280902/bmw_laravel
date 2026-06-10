# Phase 12.3 Plan - All Product Image Expansion

## Goal

Expand product image coverage across the full public catalog so BMW 330i is no longer the only product with a rich image set.

## Scope

- Audit all seeded products and ProductImage records.
- Add local generated assets for all products that have fewer than 6 usable images.
- Keep BMW 330i Phase 12.2 assets working.
- Use organized public asset folders:
  - `public/images/cars/{product-slug}/`
  - `public/images/motorbikes/{product-slug}/`
  - `public/images/accessories/{product-slug}/`
- Add an idempotent catalog-wide image seeder.
- Preserve routes, schema, booking, quote, compare, admin and product detail logic.

## Audit Findings

- Total products: 25.
- Cars: 10.
- Motorbikes: 5.
- Accessories: 10.
- Products with enough images before Phase 12.3: 1 / 25.
- Products needing images before Phase 12.3: 24 / 25.
- Initial ProductImage count before the new seeder: 33.
- `public/storage` is a junction to the old `tmdt_laravel` workspace path.

## Implementation Plan

1. Generate local contact-sheet assets for all products except BMW 330i.
2. Crop each contact sheet into 6 stable product images.
3. Add `ProductImageExpansionSeeder`.
4. Update `ProductSeeder` primary image paths to local generated assets.
5. Fix `ProductSeeder` image upsert to match by path instead of `is_primary`.
6. Update `DatabaseSeeder` to call the catalog-wide image expansion seeder.
7. Add focused Phase 12.3 feature tests.
8. Run required commands and Browser QA.

## Verification Plan

- `php artisan db:seed --class=ProductImageExpansionSeeder --no-interaction`
- rerun the seeder and compare counts.
- `php artisan view:clear`
- `php artisan view:cache`
- `vendor/bin/pint --dirty --format agent`
- `npm.cmd run build`
- `php artisan route:list --path=catalog -v`
- `php artisan test --compact tests/Feature/PublicUiPhase12_2Test.php`
- `php artisan test --compact tests/Feature/PublicUiPhase12_3Test.php`
- `php artisan test --compact tests/Feature/PublicUiPhase12Test.php`
- Browser QA for car detail, accessory detail, catalog filters, compare and mobile viewports.
