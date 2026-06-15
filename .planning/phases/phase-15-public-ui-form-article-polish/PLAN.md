# Phase 15 Plan

Phase name: PUBLIC UI POLISH + ARTICLE SEO CONTENT + PREMIUM FORM EXPERIENCE + PRODUCT LANDING PREVIEW.

## Goals

- Polish public homepage, header/navigation, editorial article section and showroom CTA flow.
- Improve public article SEO/content quality for `/tim-hieu-them`.
- Keep QA/dev articles out of public surfaces without deleting records.
- Redesign public booking/quote/consult and accessory order forms with a premium showroom image background.
- Add an admin-configurable public form background image.
- Add product public landing preview links in admin product edit and index.

## Constraints

- Preserve Phase 10-14 business logic and routes.
- Do not change appointment/accessory order field names, controllers or validation behavior.
- Do not run full seed or destructive data actions.
- Do not delete real data.
- Keep `href="#"` out of Blade views.
- Keep browser QA at desktop, mobile and tablet breakpoints.

## Implementation Approach

- Reuse existing local BMW/showroom assets instead of generating new bitmap images.
- Add a small `site_settings` key/value table for `public_form_background_image`.
- Add `SiteSetting` URL helpers and `Admin\SiteSettingController`.
- Add `/admin/site-settings` with preview, upload and reset behavior.
- Add `<x-public.form-shell>` as a shared premium form shell.
- Update ArticleSeeder with idempotent SEO sample content and Browser QA draft handling.
- Add article fallback editorial images by category.
- Add focused PHPUnit coverage for homepage articles, seeder idempotency, form visual settings and admin product preview.

## Verification

- `php artisan migrate`
- `php artisan db:seed --class=ArticleSeeder`
- `php artisan config:clear`
- `php artisan view:clear`
- `php artisan view:cache`
- `vendor\bin\pint --dirty --format agent`
- `npm.cmd run build`
- `php artisan test`
- `php artisan route:list --path=admin -v`
- `php artisan route:list --path=tim-hieu-them -v`
- `php artisan route:list --path=catalog -v`
- Browser QA: public pages, forms, admin settings, admin product edit, mobile 390x900 and tablet 768x1024.
