# Phase 14 - Admin UI Modernization + Article CMS

## 1. Audit Summary

- Admin layout existed as `<x-admin-layout>` with sidebar links for dashboard, products, categories, appointments, users, customers and accessory orders.
- Delete modal already existed and was preserved through `.admin-delete-form` plus `data-confirm-message`.
- Product/category/appointment/accessory-order admin logic was kept intact.
- No pre-existing `Article`, `Post`, `News` or blog module existed before this phase.
- Public content flow did not yet have "Tim hieu them" routes.
- UI references were checked for dark admin dashboard, dealership dashboard, CMS dashboard and CRM dashboard patterns.

## 2. Backend

- Added `articles` table:
  - `user_id`.
  - `title`.
  - unique `slug`.
  - `category`.
  - `excerpt`.
  - `body`.
  - `cover_image`.
  - `status`.
  - `published_at`.
  - `seo_title`.
  - `seo_description`.
  - timestamps.
- Added `App\Models\Article` with category/status helpers, route binding by slug, `published()` scope and cover image URL helper.
- Added `StoreArticleRequest` and `UpdateArticleRequest`.
- Added idempotent `ArticleSeeder` and registered it in `DatabaseSeeder`.

## 3. Admin Article CMS

- Added admin routes:
  - `GET /admin/articles`.
  - `GET /admin/articles/create`.
  - `POST /admin/articles`.
  - `GET /admin/articles/{article}/edit`.
  - `PUT/PATCH /admin/articles/{article}`.
  - `DELETE /admin/articles/{article}`.
- Added admin article list, create, edit and shared form views.
- Admin article index supports search/status/category filters.
- Create/update supports draft/published state and cover image upload.
- Delete uses the custom admin delete modal.

## 4. Public Article Flow

- Added public routes:
  - `GET /tim-hieu-them`.
  - `GET /tim-hieu-them/{article:slug}`.
- Public index lists only published articles.
- Public detail returns 404 for draft content.
- Homepage shows latest published articles.
- Navigation and footer link to `articles.index`.

## 5. Admin UI Modernization

- Rebuilt admin layout with BMW/Zinc-950 dark shell, sidebar, topbar and grouped navigation.
- Added admin page header, badge, empty-state and form-field components.
- Modernized:
  - dashboard.
  - products index.
  - categories index/create/edit.
  - appointments index.
  - accessory orders index/detail header.
  - products create/edit header.
  - customers/users header polish.
- Preserved 0px border-radius design rule.
- Preserved dashboard route/controller/middleware.

## 6. Routes

```text
php artisan route:list --path=admin -v
PASS - article admin routes present with web, auth, admin middleware.

php artisan route:list --path=tim-hieu-them -v
PASS - public article index/detail routes present with web middleware.
```

## 7. Tests

Focused tests:

```text
php artisan test --compact tests\Feature\AdminArticleTest.php
PASS - 5 tests / 29 assertions.

php artisan test --compact tests\Feature\PublicArticleTest.php
PASS - 4 tests / 13 assertions.

php artisan test --compact tests\Feature\AdminUiPhase14Test.php
PASS - 2 tests / 65 assertions.

php artisan test --compact tests\Feature\DashboardTest.php
PASS - 4 tests / 10 assertions.
```

Full suite:

```text
php artisan test
PASS - 65 tests / 776 assertions.
```

## 8. Build and Cache

```text
php artisan migrate
PASS.

php artisan config:clear
PASS.

php artisan view:clear
PASS.

php artisan view:cache
PASS.

vendor\bin\pint --dirty --format agent
PASS.

npm.cmd run build
PASS.
```

## 9. Browser QA

- Logged in as admin and created 2 article records through the browser:
  - one draft.
  - one published.
- Verified:
  - homepage latest articles show published article and hide draft.
  - `/tim-hieu-them` lists published article and hides draft.
  - published detail page renders.
  - draft detail page returns 404.
  - `/dashboard`, `/admin/products`, `/admin/categories`, `/admin/appointments`, `/admin/accessory-orders`, `/admin/articles` render.
  - article create/edit screens render.
  - admin delete modal opens and cancels correctly.
  - mobile viewport 390x900 passes homepage, article index/detail, dashboard and admin article index.
- Browser QA results:
  - visible broken images: 0.
  - console errors: 0.
  - horizontal overflow: 0.

## 10. Notes

- Dev DB currently contains 2 Browser QA article records and they were not deleted.
- No full seed was run.
- No destructive data operation was run.
- Fixed one admin PostgreSQL literal issue in `Admin\CustomerController` so the customers admin page can render reliably.
- Documentation references used:
  - Laravel 12 validation: https://laravel.com/docs/12.x/validation
  - Laravel 12 routing: https://laravel.com/docs/12.x/routing
  - Laravel 12 Eloquent: https://laravel.com/docs/12.x/eloquent
  - Laravel 12 filesystem: https://laravel.com/docs/12.x/filesystem
  - Dark admin dashboard references: https://colorlib.com/wp/dark-admin-dashboard-templates/
  - Dealership dashboard references: https://dribbble.com/search/car-dealership-dashboard
  - CMS dashboard references: https://dribbble.com/search/cms-dashboard-ui
  - CRM dashboard references: https://adminlte.io/blog/crm-dashboard-templates/

## 11. Status

PASS CO GHI CHU.
