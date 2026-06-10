# Phase 10 - Admin Dashboard Analytics + Custom Delete Modal

## Muc tieu

- Xay dung Admin Dashboard Analytics trung tam cho BMW CRM.
- Thay `confirm()` mac dinh bang Custom Delete Modal.
- Khong pha kien truc hien tai.
- Khong dung layout sai.
- Khong query bua cot/schema chua xac minh.

## File da sua/tao

- `routes/web.php`
- `app/Http/Controllers/Admin/DashboardController.php`
- `resources/views/admin/dashboard.blade.php`
- `resources/views/components/admin/delete-confirm-modal.blade.php`
- `resources/views/components/admin-layout.blade.php`
- `resources/views/admin/products/index.blade.php`
- `resources/views/admin/categories/index.blade.php`
- `database/factories/AppointmentFactory.php`
- `tests/Feature/DashboardTest.php`

## Route dashboard sau Phase 10

- `GET|HEAD /dashboard`
- Controller: `App\Http\Controllers\Admin\DashboardController@index`
- Name: `dashboard`
- Middleware:
  - `web`
  - `auth`
  - `verified`
  - `admin`

## Dashboard analytics da them

- Tong lead/lich hen.
- Lich hen hom nay.
- Lead chua xu ly.
- Xu huong lead 7 ngay gan nhat.
- Phan bo lead theo type.
- Top xe/dong xe duoc quan tam.
- 10 lich hen moi nhat.
- Khoi lead dac biet cho Trade-In va Service/Aftersales.
- Eager load `user`, `product.category`.
- Khong dung `Model::all()`.

## Custom Delete Modal

- Tao component `resources/views/components/admin/delete-confirm-modal.blade.php`.
- Nhung trong `resources/views/components/admin-layout.blade.php`.
- Thay inline `onsubmit="return confirm(...)"` o:
  - `resources/views/admin/products/index.blade.php`
  - `resources/views/admin/categories/index.blade.php`
- Form dung class `admin-delete-form`.
- Form co `data-confirm-message`.
- Grep cuoi khong con `confirm(` trong:
  - `resources/views/admin`
  - `resources/views/components`
  - `resources/views/layouts`

## Lenh da chay va ket qua

```text
php artisan route:list --path=dashboard -v
PASS - dashboard co middleware web, auth, verified, admin.

php artisan view:clear
PASS.

php artisan view:cache
PASS - Blade templates cached successfully.

vendor/bin/pint --dirty --format agent
PASS.

npm run build
FAIL tren PowerShell do ExecutionPolicy chan npm.ps1.

npm.cmd run build
PASS - Vite build thanh cong.

php artisan test --compact tests/Feature/DashboardTest.php
PASS - 4 tests / 10 assertions.

php artisan test
FAIL - 10 failed, 25 passed, 79 assertions.
```

## Full suite chua pass

- Full suite chua pass.
- 10 loi con lai khong thuoc Phase 10 theo hau kiem.
- Nhom loi:
  - auth redirect expected `/dashboard`, actual `/admin/products`.
  - `/register` 404 do route register dang bi comment.
  - `/settings/password` va `/settings/profile` 404 do route that la `/password` va `/profile`.

## Trang thai

```text
PASS CO GHI CHU.
Phase 10 co the chot, nhung full test suite van con loi cu ngoai Phase 10.
```
