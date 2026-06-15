# Admin UI

## Layout

- Admin layout dung Blade component `<x-admin-layout>`.
- File layout: `resources/views/components/admin-layout.blade.php`.
- Sidebar co link products, categories, appointments, users, customers va dashboard CRM.
- Phase 13 them sidebar link `Don phu kien` cho `admin.accessory-orders.index`.
- Phase 14 them sidebar link `Bai viet` cho `admin.articles.index`.
- Phase 15 them sidebar link `Thiet lap giao dien` cho `admin.site-settings.edit`.
- Phase 14 rebuild admin shell voi dark sidebar/topbar, grouped navigation va quick link ra website public.

## Shared admin components

- `resources/views/components/admin/page-header.blade.php`.
- `resources/views/components/admin/badge.blade.php`.
- `resources/views/components/admin/empty-state.blade.php`.
- `resources/views/components/admin/form-field.blade.php`.

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
- Khong dung `href="#"`.
- Admin article delete van dung custom delete modal.

## Phase 14 note

- Them admin Article CMS:
  - `resources/views/admin/articles/index.blade.php`.
  - `resources/views/admin/articles/create.blade.php`.
  - `resources/views/admin/articles/edit.blade.php`.
  - `resources/views/admin/articles/_form.blade.php`.
  - `App\Http\Controllers\Admin\ArticleController`.
- Modernized dashboard/products/categories/appointments/accessory-orders list surfaces.
- Product/category/appointment/accessory-order logic duoc giu; Phase 14 tap trung UI va article CMS.
- Customer admin query duoc fix PostgreSQL string literal de page render on dinh.

## Phase 15 note

- Them admin visual settings page:
  - Route: `/admin/site-settings`.
  - Controller: `App\Http\Controllers\Admin\SiteSettingController`.
  - View: `resources/views/admin/site-settings/edit.blade.php`.
  - Cho phep upload/reset shared public form background.
- Admin product edit them link `Xem trang public`.
- Admin product index them action `Public`.
- Product save/update/delete logic khong doi.

## Phase 15.1 note

- Admin sidebar scrollbar polish:
  - file: `resources/views/components/admin-layout.blade.php`.
  - nav area still uses internal `overflow-y-auto` so long menus remain reachable.
  - native scrollbar is hidden with `.scrollbar-none` from `resources/css/app.css`.
  - header/logo and user/logout footer remain outside the scrollable nav area.
  - sidebar groups were tightened slightly to reduce scroll pressure.
- No admin route, controller or navigation target changed.

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
