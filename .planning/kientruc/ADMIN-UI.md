# Admin UI

## Layout

- Admin layout dung Blade component `<x-admin-layout>`.
- File layout: `resources/views/components/admin-layout.blade.php`.
- Sidebar co link products, categories, appointments, users, customers va dashboard CRM.
- Phase 13 them sidebar link `Don phu kien` cho `admin.accessory-orders.index`.

## Dashboard

- View: `resources/views/admin/dashboard.blade.php`.
- Route: `/dashboard`.
- Controller: `Admin\DashboardController@index`.
- Dung `<x-admin-layout>`.

## Delete modal

- Component: `resources/views/components/admin/delete-confirm-modal.blade.php`.
- Nhung trong admin layout bang `<x-admin.delete-confirm-modal />`.
- Form xoa admin dung:
  - class `admin-delete-form`.
  - attribute `data-confirm-message`.

## UI rules

- Zinc-950 dark theme.
- Inter trong admin layout hien tai.
- 0px border radius theo design token.
- Khong dung confirm mac dinh trong admin delete forms.

## Phase 11 note

- Phase 11 khong sua Admin UI.
- Phase 11 chi redesign public product detail page.

## Phase 12 note

- Phase 12 khong sua Admin UI/controller/dashboard.
- Admin dashboard Phase 10 duoc giu nguyen.
- Admin product image fallback/URL cu van nam ngoai scope Phase 12.
- Public layout/navigation/footer da duoc dai tu, nhung `<x-admin-layout>` khong doi.

## Phase 12.2 note

- Phase 12.2 khong sua Admin UI/controller/dashboard.
- Phase 12.2 chi them public product assets, seeder va detail page image mapping cho BMW 330i Sedan.
- Admin upload/product image UI khong doi.

## Phase 13 note

- Them admin accessory order module:
  - `resources/views/admin/accessory-orders/index.blade.php`.
  - `resources/views/admin/accessory-orders/show.blade.php`.
  - `App\Http\Controllers\Admin\AccessoryOrderController`.
- Admin co the list/filter/detail/update status/internal notes cho accessory orders.
- `confirmed_at` duoc set khi status chuyen sang `confirmed` hoac `completed`.
- Admin product index/edit da doi image rendering sang `Product::displayImageUrl()` de dung fallback public asset va tranh storage URL hong.
