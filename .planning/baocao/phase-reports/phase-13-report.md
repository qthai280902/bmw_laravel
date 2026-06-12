# Phase 13 - Product Flow + Accessory Order Module

## 1. Audit Summary

- `products.type` dang phan biet `car`, `motorbike`, `accessory`.
- Accessory truoc Phase 13 dang dung appointment quote/consult cho CTA dat hang, chua co order module rieng.
- `appointments` khong phu hop lam accessory order vi schema co appointment date/type thay vi address/quantity/internal notes.
- Catalog/accessories card can equal height va CTA aligned.
- Admin product image view con build `Storage::url()` truc tiep, gay request `/storage/...` khi local storage link sai.

## 2. Database

- Them migration `2026_06_12_151713_create_accessory_orders_table.php`.
- Them bang `accessory_orders` voi:
  - `product_id`.
  - customer name/phone/address/email.
  - `quantity`.
  - `notes`.
  - `status`.
  - `admin_notes`.
  - `confirmed_at`.
  - timestamps.
- `product_id` dung foreign key nullable + `nullOnDelete`.

## 3. Public Flow

- Them route:
  - `GET /accessories/{product:slug}/order`.
  - `POST /accessories/{product:slug}/order`.
- Them form dat hang phu kien rieng.
- Validate:
  - name/address/phone required.
  - email optional.
  - quantity min 1, default 1.
  - notes optional.
- Vehicle products khong duoc dung accessory order route.

## 4. Admin Flow

- Them module admin `Don phu kien`.
- Them sidebar link trong admin layout.
- Admin co the:
  - xem danh sach order.
  - filter theo status.
  - xem chi tiet order.
  - cap nhat status.
  - ghi chu noi bo.
  - xac nhan thu cong qua `confirmed_at`.

## 5. Product Flow Normalization

- Car/motorbike giu:
  - test drive.
  - quote.
  - compare.
  - specs modal.
- Accessory:
  - co order CTA.
  - co contact CTA.
  - khong test drive.
  - khong vehicle compare.
  - khong dung appointment quote cho dat hang.
- Compare flow bo qua accessory IDs.
- Appointment create va validation chan accessory cho test-drive/viewing.

## 6. UI

- Catalog/accessories cards dung flex column va `h-full`.
- Image card co height on dinh.
- CTA footer duoc day xuong day card.
- Desktop row card/action diff: 0px trong browser smoke.
- Mobile accessories khong horizontal overflow.
- Admin product index/edit dung `Product::displayImageUrl()` de tranh URL storage hong.

## 7. Route Verification

```text
php artisan route:list --path=accessories -v
PASS - /accessories va /accessories/{product}/order qua middleware web.

php artisan route:list --path=admin/accessory -v
PASS - admin accessory orders qua middleware web, auth, admin.
```

## 8. Commands Run

```text
php artisan migrate
PASS.

vendor/bin/pint --dirty --format agent
PASS.

php artisan view:cache
PASS.

npm.cmd run build
PASS.

php artisan test
PASS - 54 tests / 669 assertions.
```

Focused tests da chay va pass:

```text
php artisan test --compact tests/Feature/AccessoryOrderTest.php
PASS - 6 tests / 27 assertions.

php artisan test --compact tests/Feature/AdminAccessoryOrderTest.php
PASS - 4 tests / 14 assertions.

php artisan test --compact tests/Feature/PublicUiPhase12Test.php
PASS - 3 tests / 19 assertions.

php artisan test --compact tests/Feature/PublicUiPhase12_3Test.php
PASS - 3 tests / 484 assertions.
```

## 9. Browser QA

- `http://127.0.0.1:8000/catalog`: 12 cards, row card/action diff 0px, no horizontal overflow.
- `http://127.0.0.1:8000/catalog?type=car`: car cards aligned.
- `http://127.0.0.1:8000/catalog?type=motorbike`: motorbike cards aligned.
- `http://127.0.0.1:8000/accessories`: 10 accessory cards, order links present.
- Mobile 390px accessories: no horizontal overflow.
- Accessory detail:
  - order link present.
  - no test-drive link.
  - no quote appointment link.
  - no compare button.
- Order form:
  - all expected fields present.
  - quantity default 1.
  - valid submit success.
- Admin:
  - `/admin/accessory-orders` renders module.
  - `/admin/accessory-orders/2` renders QA order detail.
  - status update to confirmed works.
  - internal note saved.
- Browser image QA after patch:
  - admin product index image URLs use `/images/...`.
  - 403 storage image requests: 0.
  - console errors: 0.

## 10. Notes

- Browser smoke tao local QA accessory order `#2` trong dev database.
- `public/storage` local junction da duoc sua tu target cu `tmdt_laravel` sang workspace hien tai `bmw_laravel`.
- Khong chay seed toan bo trong Phase 13.
- Da chay migration moi cua Phase 13.

## 11. Status

PASS CO GHI CHU.
