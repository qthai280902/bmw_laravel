# Phase 13 - Product Flow + Accessory Order Module

## Scope

- Chuan hoa public product flow theo loai san pham.
- Bo sung module dat hang phu kien rieng, khong dung chung bang `appointments`.
- Can chinh catalog/accessories card de chieu cao va CTA thang hang.
- Bo sung admin list/detail/update status cho accessory orders.
- Khong pha Phase 10/11/12/12.2/12.3.

## Audit Result

- `products.type` dang dung enum `VehicleType`: `car`, `motorbike`, `accessory`.
- Car/motorbike can giu `test_drive`, `quote`, `compare`, specs modal.
- Accessory khong nen dung test-drive/viewing/compare.
- Accessory truoc Phase 13 dang dung appointment quote/consult cho CTA dat hang, chua dung semantic order rieng.
- `appointments` co `appointment_date`, `type`, `meta_data`, khong phu hop cho order co address/quantity/internal notes.
- Admin product images con build URL bang `Storage::url()` truc tiep, de sinh request `/storage/...` hong khi local storage link sai.

## Implementation Plan

- Tao bang `accessory_orders`.
- Tao model, request validation va public controller cho order form.
- Tao admin controller va views cho index/show/update status.
- Them public routes:
  - `GET /accessories/{product:slug}/order`.
  - `POST /accessories/{product:slug}/order`.
- Them admin routes:
  - `GET /admin/accessory-orders`.
  - `GET /admin/accessory-orders/{accessoryOrder}`.
  - `PATCH /admin/accessory-orders/{accessoryOrder}/status`.
- Them Product helpers:
  - `isVehicle()`.
  - `canTestDrive()`.
  - `canCompare()`.
  - `canOrderAccessory()`.
- Cap nhat catalog/detail/card UI de accessory dung order CTA rieng.
- Cap nhat compare/search/appointment flow de khong dua accessory vao vehicle-only flow.
- Them focused feature tests va browser smoke.

## Verification Plan

- `php artisan migrate`
- Route list cho public/accessory admin routes.
- `php artisan test` focused va full suite.
- `vendor/bin/pint --dirty --format agent`
- `php artisan view:cache`
- `npm.cmd run build`
- Browser smoke desktop/mobile cho catalog, accessories, detail, order form, admin order detail/update.
