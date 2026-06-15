# Phase 14 Regression QA Report

## 1. Test metadata

- Test time: 2026-06-14 21:05:33 +07:00.
- Workspace: `C:\Users\thaib\du_an_code\bmw_laravel`.
- Branch: `master`.
- Remote: `https://github.com/qthai280902/bmw_laravel.git`.
- Initial working tree: clean.
- No commit or push was performed.
- No destructive migrate was run.
- No full seed was run.
- No dev/QA data was deleted.

## 2. Commands run

```text
php artisan config:clear
PASS - Configuration cache cleared successfully.

php artisan view:clear
PASS - Compiled views cleared successfully.

php artisan view:cache
PASS - Blade templates cached successfully.

vendor\bin\pint --test
PASS - {"tool":"pint","result":"passed"}.

npm.cmd run build
PASS - Vite built manifest, CSS and JS assets successfully.

php artisan test
PASS - 65 tests / 776 assertions.
```

## 3. Route checks

```text
php artisan route:list --path=admin -v
PASS.
```

Confirmed admin article routes:

- `GET /admin/articles`.
- `GET /admin/articles/create`.
- `POST /admin/articles`.
- `GET /admin/articles/{article}/edit`.
- `PUT|PATCH /admin/articles/{article}`.
- `DELETE /admin/articles/{article}`.

Confirmed admin article middleware:

- `web`.
- `auth`.
- `admin`.

```text
php artisan route:list --path=tim-hieu-them -v
PASS.
```

Confirmed public article routes:

- `GET /tim-hieu-them`.
- `GET /tim-hieu-them/{article}` via route source `/tim-hieu-them/{article:slug}`.

Confirmed public middleware:

- `web`.

## 4. Admin UI checklist

Pages checked in Browser:

- `/dashboard`.
- `/admin/products`.
- `/admin/categories`.
- `/admin/appointments`.
- `/admin/accessory-orders`.
- `/admin/articles`.
- `/admin/articles/create`.

Results:

- No 500 page detected.
- Sidebar rendered on admin pages.
- Active sidebar link detected on each admin page.
- Sidebar clipped text count: 0.
- Logout button exists.
- Tables render on dashboard/products/categories/appointments/accessory-orders/articles.
- Action elements render on product/category/article/admin pages.
- Search/filter controls render and work:
  - `/admin/articles?search=BMW&status=published` shows `Uu dai mua he BMW 2026`.
  - `/admin/products?search=330i` shows `BMW 330i Sedan`.
  - `/admin/accessory-orders?status=completed` shows QA order `#3`.
  - `/admin/appointments?status=pending` loads successfully.
- Pagination checked:
  - `/admin/products?page=2` loads with 10 rows.
- `href="#"` grep across `resources`, `routes`, `app`, `tests`: no matches.

## 5. Delete modal checklist

Pages checked in Browser:

- `/admin/products`.
- `/admin/categories`.
- `/admin/articles`.

Results:

- Products:
  - delete forms found: 10.
  - custom modal opened.
  - `aria-hidden=false` while open.
  - Cancel button closed modal.
  - no data deleted.
- Categories:
  - delete forms found: 8.
  - custom modal opened.
  - `aria-hidden=false` while open.
  - Cancel button closed modal.
  - no data deleted.
- Articles:
  - delete forms found: 4.
  - custom modal opened.
  - `aria-hidden=false` while open.
  - Cancel button closed modal.
  - no data deleted.

## 6. Article CMS checklist

Article create/update was executed through a real authenticated admin HTTP session because Browser text input failed in this local automation runtime with:

```text
Browser Use virtual clipboard is not installed
```

The pages and created records were then verified in Browser and DB.

Published article:

- ID: `3`.
- Title: `Uu dai mua he BMW 2026`.
- Slug: `uu-dai-mua-he-bmw-2026`.
- Status: `published`.
- `published_at`: `2026-06-14T13:59:34.000000Z`.
- Create redirect: `/admin/articles/uu-dai-mua-he-bmw-2026/edit`.
- Update redirect: `/admin/articles/uu-dai-mua-he-bmw-2026/edit`.
- Admin index shows the article.
- Public homepage shows the article.
- Public list shows the article.
- Public detail loads 200.

Draft article:

- ID: `4`.
- Title: `Bai nhap noi bo showroom`.
- Slug: `bai-nhap-noi-bo-showroom`.
- Status: `draft`.
- `published_at`: null.
- Create redirect: `/admin/articles/bai-nhap-noi-bo-showroom/edit`.
- Admin index shows the draft.
- Public homepage does not show the draft.
- Public list does not show the draft.
- Public direct URL returns 404.

Validation:

- Empty admin article submit redirects back to `/admin/articles/create`.
- Validation error text was present.

Cover image:

- No new cover image was uploaded in this regression run.
- Existing automated test `AdminArticleTest` still covers published article creation with cover image.
- Browser checks confirmed no broken images and no layout break when QA articles have no cover image.

## 7. Public "Tim hieu them" checklist

Pages checked in Browser:

- `/`.
- `/tim-hieu-them`.
- `/tim-hieu-them/uu-dai-mua-he-bmw-2026`.
- `/tim-hieu-them/bai-nhap-noi-bo-showroom`.

Results:

- Homepage has "Tim hieu them" section.
- Homepage shows published QA article.
- Homepage hides draft QA article.
- "Xem tat ca" link goes to `/tim-hieu-them`.
- `/tim-hieu-them` loads.
- Public list shows published QA article.
- Public list hides draft QA article.
- Public detail shows title, category/content and back link.
- Draft detail returns 404.
- `href="#"`: 0.

## 8. Old logic regression

Product detail:

- `/catalog/bmw-330i-sedan` loads.
- Product title visible.
- Test-drive booking links present.
- Quote booking links present.
- Compare link present.
- Specs table/section still renders.
- Broken images: 0.

Accessories:

- `/accessories` loads.
- No "Dang ky lai thu" text on accessory listing.
- Order links are present.
- `/catalog/tham-lot-san-m-performance` loads.
- Accessory detail has order CTA.
- Accessory detail has no test-drive CTA.
- `/accessories/tham-lot-san-m-performance/order` loads and contains accessory order form.

Accessory orders admin:

- `/admin/accessory-orders` loads.
- `/admin/accessory-orders/3` loads.
- QA order `#3` status/internal notes update succeeded.
- Final order `#3` status: `completed`.
- Final order `#3` admin notes: `Phase 14 regression QA checked accessory order admin flow 1781445915951`.

Compare:

- `/compare?ids=1,2` loads with specs table.
- `/compare?ids=1,16` loads safely with accessory ID ignored/handled by Phase 13 logic.
- Broken images: 0.
- Console errors: 0.

## 9. Responsive/browser QA

Viewports checked:

- `390x900`.
- `768x900`.
- `1366x768`.

Pages checked at each viewport:

- `/`.
- `/tim-hieu-them`.
- `/tim-hieu-them/uu-dai-mua-he-bmw-2026`.
- `/dashboard`.
- `/admin/articles`.
- `/admin/articles/create`.

Results:

- Horizontal overflow: 0 on all checked pages and viewports.
- Visible broken images: 0.
- `href="#"`: 0.
- Article form exists and is usable at all checked admin viewports.
- Admin sidebar exists on admin pages and did not produce horizontal overflow.
- Console errors since Browser QA start: 0.

## 10. Security/visibility

- Guest `/admin/articles`: 302 redirect to `/login`.
- Non-admin `qa-nonadmin@example.com`:
  - login redirect target is blocked by admin middleware with 403.
  - `/admin/articles` returns 403.
- Admin `admin@bmw.com`:
  - `/admin/articles` returns 200.
- Public article visibility:
  - published article visible.
  - draft article hidden.
  - draft direct URL returns 404.
- Article validation:
  - required fields rejected.
- Delete article route is inside admin route group with `web`, `auth`, `admin`.

## 11. QA data created or changed

New article records created in this regression run:

- `#3` - `uu-dai-mua-he-bmw-2026` - `published`.
- `#4` - `bai-nhap-noi-bo-showroom` - `draft`.

Pre-existing QA article records still present and not deleted:

- `#1` - `browser-qa-draft-1781444047469` - `draft`.
- `#2` - `browser-qa-published-1781444047469` - `published`.

Updated QA/dev accessory order:

- `#3` - status `completed`.
- `admin_notes` updated to `Phase 14 regression QA checked accessory order admin flow 1781445915951`.

No data was deleted.

## 12. Known issues / notes

- Browser text entry APIs could not type/fill due local plugin clipboard runtime issue:
  - `Browser Use virtual clipboard is not installed`.
- This is an automation-environment limitation, not an application bug.
- Article create/update was therefore tested through authenticated admin HTTP form posts, then verified in Browser and DB.
- No new application bug was found.
- No bug file was created.

## 13. Agent self-evaluation

PASS CO GHI CHU.

Reason:

- All required command gates passed.
- Route checks passed.
- Admin UI, Article CMS, public article flow, old product/accessory/compare flows, responsive checks and security checks passed.
- Browser QA found 0 console errors, 0 visible broken images and 0 horizontal overflow.
- Note remains because regression created QA article records and updated QA order `#3`, and Browser text input needed HTTP fallback due plugin clipboard limitation.

## 14. Conclusion

- Phase 14 can be considered closed after this regression QA.
- No code patch is needed.
- Working tree should be committed/pushed only if there are planning report changes to publish.
