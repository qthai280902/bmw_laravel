# Admin UI

## Layout

- Admin layout dung Blade component `<x-admin-layout>`.
- File layout: `resources/views/components/admin-layout.blade.php`.
- Sidebar co link products, categories, appointments, users, customers va dashboard CRM.

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
