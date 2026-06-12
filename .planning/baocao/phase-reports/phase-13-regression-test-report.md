# Phase 13 Regression QA

## Test time

- 2026-06-12 23:11:30 +07:00
- Workspace: `C:\Users\thaib\du_an_code\bmw_laravel`
- Branch: `master`
- Remote: `https://github.com/qthai280902/bmw_laravel.git`

## Scope

- Accessory Order Flow.
- Admin Accessory Order Management.
- Product CTA Logic.
- Card alignment.
- Validation/security basics.
- Accessory order database/migration.

## Initial state

- Project path is correct: `bmw_laravel`.
- Working tree is not clean because Phase 13 implementation/planning files are modified/untracked.
- Route checks:
  - `php artisan route:list --path=accessories -v`: pass.
  - `php artisan route:list --path=admin/accessory -v`: pass.

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

## Product CTA logic

- Car detail `/catalog/bmw-330i-sedan`:
  - test-drive link present.
  - quote link present.
  - compare action present.
  - specs visible.
  - accessory order CTA absent.
- Motorbike detail `/catalog/bmw-s1000rr`:
  - test-drive link present.
  - quote link present.
  - compare action present.
  - no 500 or redirect issue.
- Accessory details checked:
  - `/catalog/tham-lot-san-m-performance`.
  - `/catalog/mu-bao-hiem-bmw-system-7-carbon`.
  - order CTA present.
  - contact CTA present.
  - `type=test_drive` absent.
  - quote appointment link absent.
  - compare button absent.

## Accessory order public flow

- Detail CTA click test:
  - clicked order CTA from `/catalog/tham-lot-san-m-performance`.
  - landed on `/accessories/tham-lot-san-m-performance/order`.
  - order form present.
- Form fields:
  - `customer_name`: present, required.
  - `customer_phone`: present, required.
  - `customer_address`: present, required.
  - `customer_email`: present, optional email.
  - `quantity`: present, default 1, min 1.
  - `notes`: present, optional.
  - product name visible.
- Validation:
  - empty submit blocked by HTML5 required validation.
  - missing phone blocked by HTML5 required validation.
  - `quantity=0` blocked by min validation.
  - invalid email blocked by email validation.
- Valid submit:
  - customer: `QA Regression 1781280571180`.
  - product: `Thảm lót sàn M Performance`.
  - quantity: 3.
  - success message visible.
  - database record created.

## Test order created

- Accessory order ID: `3`.
- Product: `Thảm lót sàn M Performance`.
- Customer name: `QA Regression 1781280571180`.
- Status after admin QA: `completed`.
- Admin notes after admin QA: `Regression QA completed order`.
- `confirmed_at`: `2026-06-12 16:10:30`.

## Admin accessory order QA

- Admin login with `admin@bmw.com`: pass.
- `/dashboard`:
  - loads.
  - dashboard content present.
  - sidebar has `Đơn phụ kiện`.
  - horizontal overflow: 0.
- `/admin/accessory-orders`:
  - loads.
  - module title present.
  - columns present: order id, customer, product, quantity/status/date.
  - test order `#3` visible.
- `/admin/accessory-orders/3`:
  - customer visible.
  - address visible.
  - email visible.
  - notes visible.
  - product and quantity visible.
  - status select visible.
  - admin notes field visible.
- Status workflow:
  - `pending -> confirmed`: pass.
  - `confirmed -> completed`: pass.
  - DB final status: `completed`.

## Security / validation QA

- Guest `/admin/accessory-orders`: 302 redirect to `/login`.
- Non-admin `/admin/accessory-orders`: 403.
- `/accessories/bmw-330i-sedan/order`: 404.
- Invalid admin status update:
  - request redirects with validation behavior.
  - order status stays `completed`.
  - admin notes stay unchanged.
- Full feature tests also covered:
  - accessory route rejects vehicle products.
  - non-admin cannot manage accessory orders.
  - accessory order required fields.
  - compare ignores accessory IDs.

## Database / migration QA

- `accessory_orders` table exists.
- Columns present:
  - `id`.
  - `product_id`.
  - `customer_name`.
  - `customer_phone`.
  - `customer_address`.
  - `customer_email`.
  - `quantity`.
  - `notes`.
  - `status`.
  - `admin_notes`.
  - `confirmed_at`.
  - `created_at`.
  - `updated_at`.
- Missing expected columns: none.
- No destructive migration was run.
- No full seed was run.

## Browser QA

- Pages checked:
  - `/`.
  - `/catalog`.
  - `/catalog?type=car`.
  - `/catalog?type=motorbike`.
  - `/accessories`.
  - `/compare?ids=1,2`.
  - `/compare?ids=1,16`.
  - `/catalog/bmw-330i-sedan`.
  - `/catalog/bmw-530i-sedan`.
  - `/catalog/bmw-m3-sedan`.
  - `/catalog/bmw-s1000rr`.
  - `/catalog/tham-lot-san-m-performance`.
  - `/catalog/mu-bao-hiem-bmw-system-7-carbon`.
  - `/accessories/tham-lot-san-m-performance/order`.
  - `/dashboard`.
  - `/admin/accessory-orders`.
  - `/admin/accessory-orders/3`.
- Desktop:
  - card row diff: 0px for catalog/accessories grids.
  - action footer diff: 0px for catalog/accessories grids.
  - horizontal overflow: 0.
- Mobile 390x900:
  - horizontal overflow: 0.
  - visible broken images: 0.
  - CTA buttons did not overlap.
- Console errors: 0.

## Known notes

- Browser QA created/updated dev DB accessory order `#3`; data was not deleted per instruction.
- Existing QA order `#2` from prior Phase 13 smoke remains in dev DB.
- Working tree is not clean because Phase 13 changes and planning reports are still unstaged/uncommitted.

## Status

PASS CO GHI CHU.

Reason: all required automated, browser, database and security checks passed. Notes remain for dev QA order data and dirty working tree.
