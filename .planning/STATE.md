# Project State

Current phase: Phase 14 completed with notes.

## Phase 14

- Admin UI modernization + Article CMS da hoan tat.
- Admin shell da duoc rebuild trong `resources/views/components/admin-layout.blade.php`.
- Them admin article CMS:
  - `articles` table.
  - `App\Models\Article`.
  - `App\Http\Controllers\Admin\ArticleController`.
  - admin routes `/admin/articles`.
  - views `resources/views/admin/articles/*`.
- Them public "Tim hieu them":
  - `GET /tim-hieu-them`, name `articles.index`.
  - `GET /tim-hieu-them/{article:slug}`, name `articles.show`.
  - controller `App\Http\Controllers\ArticleController`.
  - views `resources/views/articles/*`.
- Homepage, navigation va footer da them link/section bai viet.
- Draft articles khong hien public va direct URL tra 404.
- Admin delete modal contract duoc giu cho article delete.
- Dashboard Phase 10 van giu route/controller/middleware.
- Product/category/appointment/accessory order logic duoc giu.
- Verification:
  - `php artisan migrate`: pass.
  - `php artisan config:clear`: pass.
  - `php artisan view:clear`: pass.
  - `php artisan view:cache`: pass.
  - `vendor\bin\pint --dirty --format agent`: pass.
  - `npm.cmd run build`: pass.
  - `php artisan test`: pass, 65 tests / 776 assertions.
- Browser QA:
  - homepage/article index/article detail/admin pages checked.
  - mobile 390x900 checked.
  - visible broken images: 0.
  - console errors: 0.
  - horizontal overflow: 0.
- Known note: Browser QA tao 2 local dev articles, 1 draft va 1 published, khong xoa.
- Report: `.planning/baocao/phase-reports/phase-14-report.md`.

## Post-phase regression QA - Phase 12 + Phase 13

- Test time: 2026-06-12 23:11:30 +07:00.
- Scope:
  - Phase 12 public UI/product flow.
  - Phase 12.2 BMW 330i images.
  - Phase 12.3 all product images.
  - Phase 13 accessory order/admin/product CTA logic.
- Required commands:
  - `php artisan config:clear`: pass.
  - `php artisan view:clear`: pass.
  - `php artisan view:cache`: pass.
  - `vendor/bin/pint --test`: pass.
  - `npm.cmd run build`: pass.
  - `php artisan test`: pass, 54 tests / 669 assertions.
- Browser QA:
  - homepage/catalog/accessories/compare/detail/admin/order form checked.
  - desktop card/action row diff: 0px for catalog/accessories grids.
  - mobile 390x900 horizontal overflow: 0.
  - visible broken images after lazy-load settle: 0.
  - console errors: 0.
- Data QA:
  - total products: 25.
  - cars: 10.
  - motorbikes: 5.
  - accessories: 10.
  - products with at least 6 images: 25 / 25.
  - BMW 330i images: 9.
  - duplicate paths: 0.
  - duplicate sort_order: 0.
  - remote image URLs: 0.
  - bad primary image products: 0.
- Accessory order QA:
  - created dev QA order `#3`.
  - product: `Tham lot san M Performance`.
  - customer: `QA Regression 1781280571180`.
  - admin status workflow `pending -> confirmed -> completed`: pass.
- Security QA:
  - guest admin access redirects to login.
  - non-admin admin access returns 403.
  - car slug accessory order route returns 404.
  - invalid admin status does not change persisted order status.
- Reports:
  - `.planning/baocao/phase-reports/phase-12-regression-test-report.md`.
  - `.planning/baocao/phase-reports/phase-13-regression-test-report.md`.
- Result: PASS CO GHI CHU.

## Phase 13

- Product flow da duoc chuan hoa:
  - car/motorbike giu test drive, quote, compare va specs modal.
  - accessory dung order CTA rieng va contact CTA.
  - accessory khong con test-drive/vehicle-compare/appointment quote CTA.
- Them module accessory order:
  - `accessory_orders` table.
  - `App\Models\AccessoryOrder`.
  - public route `GET|POST /accessories/{product:slug}/order`.
  - admin routes `GET /admin/accessory-orders`, `GET /admin/accessory-orders/{accessoryOrder}`, `PATCH /admin/accessory-orders/{accessoryOrder}/status`.
- Them admin UI `Don phu kien` de list/detail/update status/internal notes.
- Catalog/accessories cards da can equal-height va CTA aligned.
- Compare flow bo qua accessory IDs.
- Appointment flow chan accessory cho test-drive/viewing.
- Admin product index/edit dung `Product::displayImageUrl()` thay vi `Storage::url()` truc tiep.
- Local `public/storage` junction da duoc recreate ve workspace `bmw_laravel`.
- Browser smoke pass cho catalog/accessories/detail/order form/admin update.
- `php artisan test` pass: 54 tests / 669 assertions.
- `vendor/bin/pint --dirty --format agent` pass.
- `php artisan view:cache` pass.
- `npm.cmd run build` pass.

## GitHub Actions Verify & Test fix

- Repo local dang o `C:\Users\thaib\du_an_code\bmw_laravel`.
- Local Git remote da doi sang `https://github.com/qthai280902/bmw_laravel.git`.
- Workflow `.github/workflows/verify.yml` khong hardcode `tmdt_laravel`.
- Root cause local reproduce: full suite fail do test cu auth/register/settings khong khop app hien tai.
- Da cap nhat tests theo route/redirect that:
  - login redirect ve `admin.products.index`.
  - public register route dang tat nen `/register` tra 404.
  - settings/profile route that la `/profile`.
  - password route that la `/password`.
- `php artisan test --compact` pass: 44 tests / 628 assertions.
- `vendor/bin/pint --test` pass.
- `npm.cmd run build` pass.
- `php artisan view:cache` pass.
- Storage junction issue ghi nhan o lan fix CI da duoc xu ly trong Phase 13.
- Known repository artifact: `files.txt` la path dump cu con nhieu path `tmdt_laravel`, khong duoc workflow su dung.

## Phase 12.3

- All seeded public products da duoc audit: 25 total, 10 cars, 5 motorbikes, 10 accessories.
- Products with enough images truoc Phase 12.3: 1 / 25.
- Products with enough images sau Phase 12.3: 25 / 25.
- Them 144 generated/cropped local image assets:
  - 54 car assets.
  - 30 motorbike assets.
  - 60 accessory assets.
- ProductImage count truoc seeder: 33.
- ProductImage count sau seeder: 162.
- ProductImage count sau rerun: 162.
- Remote ProductImage URL count sau seeder: 0.
- Them `ProductImageExpansionSeeder`.
- `DatabaseSeeder` goi `ProductImageExpansionSeeder`.
- `ProductSeeder` dung local image paths va update image theo `path` thay vi `is_primary`.
- `PublicUiPhase12_3Test` pass 3 tests / 484 assertions.
- Browser QA pass cho car detail, accessory detail, catalog filters, compare, mobile/tablet/desktop overflow va console errors.
- `view:cache` pass.
- Pint pass.
- `npm.cmd run build` pass.

## Phase 12.2

- BMW 330i Sedan product detail image set da duoc bo sung.
- BMW 330i image count truoc Phase 12.2: 1.
- BMW 330i image count sau Phase 12.2: 9 ProductImage records.
- Anh primary moi: `images/cars/330i/hero-front-three-quarter.png`.
- Them 8 generated project-local assets vao `public/images/cars/330i/`.
- Them `Bmw330iImageSeeder` va goi trong `DatabaseSeeder`.
- Them `Product::detailImageSet()` de map anh theo ngu canh detail page.
- `resources/views/products/show.blade.php` dung image set de render hero/design/technology/gallery.
- Browser QA xac nhan `/catalog/bmw-330i-sedan` render 8 anh 330i distinct, khong anh vo, khong horizontal overflow.
- `PublicUiPhase12_2Test` pass 3 tests / 26 assertions.
- `PublicUiPhase12Test` van pass 3 tests / 19 assertions.
- `view:cache` pass.
- Pint pass.
- `npm.cmd run build` pass.

## Phase 12

- Public-facing website da duoc dai tu: homepage, navigation, footer, catalog, compare, product detail, accessory detail va CTA blocks.
- Compare image bug da duoc fix bang `Product::displayImageUrl()` va eager load `primaryImage`, `images`.
- Public image fallback khong dung external placeholder; fallback noi bo la `images/cars/hero.png`.
- Product CTA da branch theo `Product::type`.
- Accessories dung `type=accessory`, khong hien test-drive CTA tren detail.
- Accessory detail dung CTA dat hang/bao gia/tu van/lien he mua hang tren flow hien co.
- Car/motorbike detail van giu test drive, quote, compare va specs modal.
- `PublicUiPhase12Test` pass 3 tests / 19 assertions.
- Browser smoke QA pass cho homepage, catalog, compare, car detail, accessory detail va mobile overflow checks.
- `view:cache` pass.
- Pint pass.
- `npm.cmd run build` pass.

## Phase 11

- Public product detail page da duoc redesign theo phong cach showroom BMW-inspired.
- Route chi tiet xe giu nguyen: `/catalog/{product}`, name `products.show`.
- Controller chi tiet xe giu `ProductController@show`.
- View chi tiet xe: `resources/views/products/show.blade.php`.
- Booking, quote, compare, specs modal va gallery da duoc giu.
- HTTP smoke `/catalog/bmw-330i-sedan` pass voi status 200.
- `view:cache` pass.
- Pint pass.
- `npm.cmd run build` pass.

## Phase 10

- Admin Dashboard Analytics da trien khai.
- Custom Delete Modal da thay `confirm()` mac dinh trong Admin.
- `/dashboard` hien dung `Admin\DashboardController@index`.
- `/dashboard` co middleware `web`, `auth`, `verified`, `admin`.
- `DashboardTest` pass 4 tests / 10 assertions.
- `view:cache` pass.
- Pint pass.
- `npm.cmd run build` pass.

## Known issues

- `files.txt` la tracked path dump cu con nhieu path `tmdt_laravel`, khong duoc CI workflow su dung.
- Browser smoke Phase 13 da tao local QA accessory order `#2` trong database dev.

## Notes

- Full `php artisan test` da pass sau Phase 13: 54 tests / 669 assertions.
