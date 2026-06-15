# Phase 15.1 Plan

Phase name: OVERFLOW UX CLEANUP + SCROLLBAR REMOVAL POLISH.

## Goals

- Remove the exposed mini horizontal scrollbar from the public `/tim-hieu-them` article category filter.
- Replace the article filter overflow behavior with a cleaner responsive pill layout.
- Remove the exposed mini vertical scrollbar from the admin sidebar without blocking sidebar scroll.
- Preserve article filtering, admin navigation, routes and business logic.
- Verify desktop, tablet and mobile breakpoints with browser QA.

## Constraints

- Do not change routes, controllers or business logic.
- Do not break existing Phase 14-15 UI.
- Do not hide overflow in a way that makes navigation unreachable.
- Keep BMW/Zinc-950 dark visual language.
- Keep the admin sidebar scrollable if menu height exceeds available space.

## Audit Findings

- Article filter source: `resources/views/articles/index.blade.php`.
- Article filter root cause: the category row used `overflow-x-auto` plus shrink-fixed pills, exposing a native horizontal scrollbar.
- Admin sidebar source: `resources/views/components/admin-layout.blade.php`.
- Admin sidebar root cause: the sidebar nav used `overflow-y-auto` inside a fixed-height flex sidebar, exposing a native vertical scrollbar.
- Shared CSS source: `resources/css/app.css`.

## Implementation Approach

- Convert article category filter to a responsive grid/flex-wrap pill group.
- Add active `aria-current="page"` on the selected article category.
- Add a reusable `.scrollbar-none` utility for cross-browser hidden native scrollbars.
- Keep admin sidebar nav as `overflow-y-auto` with `overscroll-contain` and hidden native scrollbar.
- Tighten sidebar group spacing and add a subtle mask fade to keep the scroll area polished.

## Verification

- `php artisan config:clear`
- `php artisan view:clear`
- `php artisan view:cache`
- `vendor\bin\pint --dirty --format agent`
- `npm.cmd run build`
- `php artisan test`
- Browser QA at 390x900, 768x1024 and 1366x768:
  - `/tim-hieu-them`
  - `/tim-hieu-them?category=uu-dai-khach-hang`
  - `/dashboard`
  - `/admin/products`
  - `/admin/articles`
