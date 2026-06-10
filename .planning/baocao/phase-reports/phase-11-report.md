# Phase 11 - Product Detail BMW Redesign

## Muc tieu

- Dai tu trang chi tiet xe cong khai theo phong cach BMW.vn-inspired.
- Giu nguyen route va cac hanh dong booking/quote/compare/specs/gallery.
- Khong copy code, layout hay asset tu BMW.vn.
- Khong dung anh placeholder ngoai.

## File da sua/tao

- `app/Http/Controllers/ProductController.php`
- `resources/views/products/show.blade.php`
- `.planning/phases/phase-11-product-detail-bmw-redesign/PLAN.md`
- `.planning/phases/phase-11-product-detail-bmw-redesign/SUMMARY.md`
- `.planning/phases/phase-11-product-detail-bmw-redesign/CHECKLIST.md`
- `.planning/baocao/phase-reports/phase-11-report.md`

## Route chi tiet xe

- `GET|HEAD /catalog/{product}`
- Name: `products.show`
- Controller: `App\Http\Controllers\ProductController@show`
- Middleware: `web`
- View: `resources/views/products/show.blade.php`
- Layout: `<x-app-layout>`

## Thay doi chinh

- Tao hero full-bleed dung anh san pham/fallback noi bo.
- Them anchor nav: overview, design, technology, technical data, services, booking.
- Render thong so tu `translated_specs` va `specifications`.
- Them related vehicles cung category voi eager load va `limit(3)`.
- Giu booking/quote links dung route helper.
- Giu compare global `toggleComparison(id)`.
- Giu specs modal Alpine.

## Lenh da chay va ket qua

```text
php artisan view:clear
PASS.

php artisan view:cache
PASS - Blade templates cached successfully.

php artisan route:list
PASS.

vendor/bin/pint --dirty --format agent
PASS.

npm.cmd run build
PASS - Vite build thanh cong.

php artisan test --compact
FAIL - 10 failed, 25 passed, 79 assertions.
```

## Smoke test

```text
GET http://127.0.0.1:8000/catalog/bmw-330i-sedan
PASS - StatusCode=200.
```

HTML checks:

- Co section `overview`, `technical-data`, `booking`.
- Co link `type=test_drive`.
- Co link `type=quote`.
- Co `toggleComparison(...)`.
- Co `showDetailedSpecs`.
- Khong co `href="#"`.

## Full suite chua pass

- Full suite con 10 fail.
- Nhom loi la auth/register/settings da ghi nhan tu Phase 10:
  - Auth redirect expected `/dashboard`, actual `/admin/products`.
  - `/register` 404 do route register dang bi comment.
  - `/settings/profile` va `/settings/password` 404 do route that khac.
- Khong co bang chung cac loi nay do Phase 11 gay ra.

## Trang thai

```text
PASS CO GHI CHU.
Phase 11 co the chot o pham vi product detail, nhung full suite van con loi cu ngoai Phase 11.
```
