# Phase 12 / 12.2 / 12.3 Regression QA

## Test time

- 2026-06-12 23:11:30 +07:00
- Workspace: `C:\Users\thaib\du_an_code\bmw_laravel`
- Branch: `master`
- Remote: `https://github.com/qthai280902/bmw_laravel.git`

## Scope

- Phase 12: Public UI Overhaul + Product Flow Alignment.
- Phase 12.2: BMW 330i Sedan image expansion.
- Phase 12.3: all product image expansion.

## Initial state

- Project path is correct: `bmw_laravel`.
- Working tree is not clean because Phase 13 implementation/planning files are still modified/untracked.
- Route checks:
  - `php artisan route:list --path=catalog -v`: pass.
  - `php artisan route:list --path=accessories -v`: pass.

## Commands run

```text
php artisan config:clear
PASS.

php artisan view:clear
PASS.

php artisan view:cache
PASS.

vendor/bin/pint --test
PASS.

npm.cmd run build
PASS.

php artisan test
PASS - 54 tests / 669 assertions.
```

## Public route HTTP checks

- `/`: 200.
- `/catalog`: 200.
- `/catalog?type=car`: 200.
- `/catalog?type=motorbike`: 200.
- `/accessories`: 200.
- `/compare?ids=1,2`: 200.
- `/compare?ids=1,16`: 200, accessory ID ignored safely.
- `/catalog/bmw-330i-sedan`: 200.
- `/catalog/bmw-530i-sedan`: 200.
- `/catalog/bmw-m3-sedan`: 200.
- `/catalog/bmw-s1000rr`: 200.
- `/catalog/tham-lot-san-m-performance`: 200.
- `/catalog/mu-bao-hiem-bmw-system-7-carbon`: 200.

## Browser QA

- Homepage:
  - header/nav present.
  - footer present.
  - horizontal overflow: 0.
  - `href="#"`: 0.
  - visible broken images: 0.
  - console errors: 0.
- Catalog:
  - `/catalog`: 12 cards, row card/action diff 0px.
  - `/catalog?type=car`: 10 cards, row card/action diff 0px.
  - `/catalog?type=motorbike`: 5 cards, row card/action diff 0px.
  - CTA alignment pass.
  - visible broken images after lazy-load settle: 0.
  - console errors: 0.
- Accessories listing:
  - 10 accessory cards.
  - order links present.
  - test-drive links: 0.
  - compare buttons: 0.
  - row card/action diff 0px.
  - visible broken images after lazy-load settle: 0.
- Compare:
  - `/compare?ids=1,2` renders 2 vehicle cards.
  - `/compare?ids=1,16` renders 1 vehicle card and ignores accessory ID safely.
  - specs visible.
  - broken images: 0.
- Mobile viewport 390x900:
  - `/catalog`, car filter, motorbike filter, `/accessories`, accessory detail, accessory order form checked.
  - horizontal overflow: 0.
  - visible broken images: 0.
  - CTA buttons did not overlap.

## Image data QA

- Total products: 25.
- Cars: 10.
- Motorbikes: 5.
- Accessories: 10.
- Products with at least 6 images: 25 / 25.
- BMW 330i images: 9.
- Other cars: 7 images each.
- Motorbikes: 6 images each.
- Accessories: 6 images each.
- Remote image URLs: 0.
- Duplicate ProductImage path per product: 0.
- Duplicate `sort_order` per product: 0.
- Products with bad primary count: 0.
- Storage link:
  - `public/storage` target is `C:\Users\thaib\du_an_code\bmw_laravel\storage\app\public`.
  - target no longer points to `tmdt_laravel`.

## Notes

- Browser first-pass image checks showed transient `naturalWidth=0` for lazy/offscreen images. After scroll wait/lazy-load settle and direct asset checks, visible broken images were 0.
- No Phase 12/12.2/12.3 regression requiring a code patch was found.

## Status

PASS CO GHI CHU.

Reason: functional/browser/test checks passed, but the working tree is intentionally not clean because Phase 13 changes are still unstaged/uncommitted.
