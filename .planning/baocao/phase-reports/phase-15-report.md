# Phase 15 Report

## 1. Status

PASS.

## 2. Audit Summary

- Homepage structure: `routes/web.php` closure loads featured products and latest published articles into `resources/views/welcome.blade.php`.
- Article CMS state before implementation: 4 total articles, 2 published, 2 draft; `Browser QA Published 1781444047469` was public.
- Form routes/views: `/booking` uses `Client\AppointmentController` + `resources/views/appointments/create.blade.php`; `/accessories/{product:slug}/order` uses `AccessoryOrderController` + `resources/views/accessory-orders/create.blade.php`.
- Settings system: no existing `site_settings`, `visual_settings` or media/page-setting system existed.
- Product preview state: admin product edit/index did not have public landing preview links.

## 3. Public UI Fixes

- Header/nav: rebuilt public nav with smaller logo/title, tighter desktop menu and dark custom mobile menu.
- Homepage sections: polished hero, product lines, featured products, editorial article section and showroom CTA flow.
- "Tim hieu them": homepage cards now use real editorial imagery and better title/excerpt/date/category hierarchy.
- Footer: existing public footer was preserved because it already matched the dark showroom shell.
- Responsive: Browser QA passed at desktop, 390x900 and 768x1024.

## 4. Article SEO Improvements

- Article categories covered:
  - `uu-dai-khach-hang`
  - `chuong-trinh-ban-hang`
  - `su-kien-showroom`
  - `trai-nghiem-bmw`
  - `dich-vu-hau-mai`
  - `tin-tuc-showroom`
- Published articles created/updated: 12 seeded SEO sample articles, 2 per category.
- QA/dev articles handled: Browser QA title/slug patterns are set to `draft` with `published_at = null`.
- Homepage article logic: still shows the 3 latest published articles, now backed by quality seeded content.
- Public article list/detail: both use `Article::editorialImageUrl()` fallback when no cover is uploaded.

## 5. Premium Form Experience

- Forms updated:
  - `resources/views/appointments/create.blade.php`
  - `resources/views/accessory-orders/create.blade.php`
- Background image behavior: both use `<x-public.form-shell>` and `SiteSetting::publicFormBackgroundImageUrl()`.
- Fields/validation preserved: field names, routes, request classes and controller logic are unchanged.
- Mobile behavior: Browser QA 390x900 passed with no horizontal overflow.

## 6. Admin Configurable Background

- Route:
  - `GET /admin/site-settings`
  - `PUT /admin/site-settings`
- Model/table:
  - `App\Models\SiteSetting`
  - `site_settings` table with `key`, `value`, `type`.
- Controller/view:
  - `App\Http\Controllers\Admin\SiteSettingController`
  - `resources/views/admin/site-settings/edit.blade.php`
- Upload/storage:
  - validates image max 5120 KB.
  - stores in public disk under `site-settings`.
  - deletes old uploaded background when replaced/reset.
- Fallback:
  - `images/cars/330i/lifestyle-showroom.png`.

## 7. Product Landing Preview

- Admin product edit: added "Xem trang public".
- Admin product index: added "Public" action.
- Route target: `products.show` / `/catalog/{product:slug}`.
- Behavior: opens in new tab with `target="_blank" rel="noopener"`.

## 8. Logic Preservation

- Product detail route and controller unchanged.
- Compare route and selection logic unchanged.
- Accessory order submit logic unchanged.
- Appointment/quote/consult submit logic unchanged.
- Article draft protection unchanged and additionally covered by seeder/test.
- Admin auth/admin middleware unchanged.

## 9. Files Changed

- `app/Http/Controllers/Admin/SiteSettingController.php`
- `app/Models/Article.php`
- `app/Models/SiteSetting.php`
- `database/migrations/2026_06_14_144558_create_site_settings_table.php`
- `database/seeders/ArticleSeeder.php`
- `resources/views/welcome.blade.php`
- `resources/views/layouts/navigation.blade.php`
- `resources/views/articles/index.blade.php`
- `resources/views/articles/show.blade.php`
- `resources/views/appointments/create.blade.php`
- `resources/views/accessory-orders/create.blade.php`
- `resources/views/admin/site-settings/edit.blade.php`
- `resources/views/components/public/form-shell.blade.php`
- `resources/views/components/admin-layout.blade.php`
- `resources/views/admin/products/edit.blade.php`
- `resources/views/admin/products/index.blade.php`
- `routes/web.php`
- `tests/Feature/PublicHomepagePhase15Test.php`
- `tests/Feature/PublicFormVisualSettingsTest.php`
- `tests/Feature/AdminProductPreviewLinkTest.php`
- `tests/Feature/ArticleSeoContentTest.php`

## 10. Commands Run

- `php artisan test --compact tests/Feature/PublicHomepagePhase15Test.php tests/Feature/ArticleSeoContentTest.php tests/Feature/PublicFormVisualSettingsTest.php tests/Feature/AdminProductPreviewLinkTest.php`: pass, 6 tests / 49 assertions.
- `php artisan migrate`: pass.
- `php artisan db:seed --class=ArticleSeeder`: pass.
- `php artisan config:clear`: pass.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `vendor\bin\pint --dirty --format agent`: pass.
- `npm.cmd run build`: pass.
- `php artisan test`: pass, 71 tests / 825 assertions.
- `php artisan route:list --path=admin -v`: pass, includes `/admin/site-settings`.
- `php artisan route:list --path=tim-hieu-them -v`: pass.
- `php artisan route:list --path=catalog -v`: pass.

## 11. Browser QA

- Pages checked:
  - `/`
  - `/tim-hieu-them`
  - `/tim-hieu-them/uu-dai-mua-he-bmw-2026-cho-khach-hang-dat-lich-showroom`
  - `/catalog/bmw-330i-sedan`
  - `/booking?type=consult`
  - `/accessories`
  - `/accessories/tham-lot-san-m-performance/order`
  - `/admin/site-settings`
  - `/admin/products/1/edit`
- Broken images: 0 after load + lazy image scroll settle.
- Console errors: 0.
- Horizontal overflow: 0.
- Mobile/tablet: 390x900 and 768x1024 passed.
- Admin background setting: upload input visible in browser; dynamic public background switching verified with a temporary DB setting and then reset.

## 12. Planning Updates

- `.planning/phases/phase-15-public-ui-form-article-polish/PLAN.md`
- `.planning/phases/phase-15-public-ui-form-article-polish/SUMMARY.md`
- `.planning/phases/phase-15-public-ui-form-article-polish/CHECKLIST.md`
- `.planning/baocao/phase-reports/phase-15-report.md`
- `.planning/STATE.md`
- `.planning/ROADMAP.md`
- `.planning/kientruc/ROUTING.md`
- `.planning/kientruc/DATA-MODEL.md`
- `.planning/kientruc/ADMIN-UI.md`
- `.planning/kientruc/TESTING.md`

## 13. Known Issues

- No new code bug found.
- Dev DB still contains existing Phase 14 QA/dev article records, but Browser QA public article is no longer published after ArticleSeeder.
- No generated image asset was added because existing local assets were sufficient.

## 14. Conclusion

Phase 15 can be closed as PASS.
