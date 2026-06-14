# Phase 14 Checklist

## Audit

- [x] Read admin layout and sidebar structure.
- [x] Read product/category/appointment/accessory-order admin views.
- [x] Confirm existing delete modal contract.
- [x] Confirm no pre-existing Article/Post/News module.
- [x] Confirm public routes needing new content flow.

## Backend

- [x] Create `articles` migration.
- [x] Create `App\Models\Article`.
- [x] Add article factory.
- [x] Add idempotent article seeder.
- [x] Add admin article controller.
- [x] Add public article controller.
- [x] Add store/update form requests.
- [x] Register public article routes.
- [x] Register admin article resource routes.

## UI

- [x] Modernize admin layout.
- [x] Add article link to admin sidebar.
- [x] Modernize dashboard.
- [x] Modernize product/category/appointment/accessory-order list pages.
- [x] Add admin article index/create/edit/form views.
- [x] Add public article index/detail views.
- [x] Add homepage latest article section.
- [x] Add public navigation/footer article links.

## Tests and Verification

- [x] Run `php artisan migrate`.
- [x] Run `php artisan config:clear`.
- [x] Run `php artisan view:clear`.
- [x] Run `php artisan view:cache`.
- [x] Run `vendor\bin\pint --dirty --format agent`.
- [x] Run focused admin article tests.
- [x] Run focused public article tests.
- [x] Run focused admin UI tests.
- [x] Run `npm.cmd run build`.
- [x] Run full `php artisan test`.
- [x] Verify admin article routes.
- [x] Verify public article routes.
- [x] Browser QA desktop.
- [x] Browser QA mobile 390x900.

## Notes

- [x] Dev DB keeps Browser QA articles created during manual QA.
- [x] No full seed run was required.
- [x] No destructive data operation was run.
