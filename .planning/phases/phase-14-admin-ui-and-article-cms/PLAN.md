# Phase 14 Plan - Admin UI Modernization + Article CMS

## Scope

- Modernize admin UI without breaking existing admin/product/CRM/accessory order logic.
- Add article/news CMS for admin.
- Add public "Tim hieu them" content flow.
- Preserve Phase 10 dashboard middleware and Phase 12/13 product/accessory behavior.

## Guardrails

- Keep `/dashboard` on `Admin\DashboardController@index`.
- Keep dashboard middleware: `web`, `auth`, `verified`, `admin`.
- Keep admin routes protected by `web`, `auth`, `admin`.
- Do not change product detail route `/catalog/{product}`.
- Do not remove delete modal contract:
  - `.admin-delete-form`.
  - `data-confirm-message`.
- Do not reintroduce native `confirm()`.
- Do not use `href="#"`.
- Do not use `Model::all()` in runtime paths.
- Do not run destructive seed/migrate operations.

## Implementation Plan

1. Audit current admin layout, sidebar, dashboards, product/category/appointment/accessory order screens.
2. Review version-specific Laravel docs for routing, validation, Eloquent scopes and file storage.
3. Add article data model, migration, factory, seeder and validation requests.
4. Add admin article CRUD under `/admin/articles`.
5. Add public article index/detail under `/tim-hieu-them`.
6. Refresh admin shell and main admin pages with BMW/Zinc-950 dark theme and 0px radius.
7. Add focused PHPUnit coverage for admin article, public article and admin UI contracts.
8. Run migration, caches, Pint, Vite build, full test suite and route verification.
9. Run Browser QA on desktop and mobile for public/admin article flows and core admin pages.

## Acceptance Criteria

- Admin UI renders with modern sidebar/topbar and keeps existing modules usable.
- Article CMS supports create/edit/delete/list/filter and draft/published states.
- Public article pages only show published content.
- Draft article direct URL returns 404.
- Homepage includes latest article section.
- Admin article delete uses custom modal.
- View cache, Vite build and full test suite pass.
- Browser QA reports no visible broken images, console errors or horizontal overflow.
