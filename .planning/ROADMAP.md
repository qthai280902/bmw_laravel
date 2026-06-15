# Project Roadmap - Vehicle E-commerce v1.0

## Current documented status

- [x] Phase 16 completed with notes: AI Showroom Assistant + Laravel AI SDK + PHP 8.5 deprecation cleanup.
- [x] PHP 8.5 `PDO::MYSQL_ATTR_SSL_CA` deprecation fixed without changing DB connection logic.
- [x] `laravel/ai` v0.7.2 installed and configured for Gemini through env/config.
- [x] Public assistant route added: `POST /ai/showroom-assistant`.
- [x] Public BMW AI widget added with draggable desktop panel and compact mobile launcher.
- [x] AI prompt context limited to public products and published articles; private customer/admin data excluded.
- [x] Full `php artisan test` pass after Phase 16: 83 tests / 975 assertions.
- [x] Phase 15 completed: Public UI polish + article SEO content + premium form experience + product landing preview.
- [x] Public `/booking` and accessory order forms now use an image-backed premium form shell.
- [x] Admin visual setting route added: `/admin/site-settings`.
- [x] `site_settings` key/value table added for `public_form_background_image`.
- [x] ArticleSeeder now seeds 12 quality public articles and drafts Browser QA content.
- [x] Admin product edit/index now link to public product landing pages.
- [x] Full `php artisan test` pass after Phase 15: 71 tests / 825 assertions.
- [x] Phase 14 completed with notes: Admin UI modernization + Article CMS.
- [x] Admin article CMS added under `/admin/articles`.
- [x] Public "Tim hieu them" routes added under `/tim-hieu-them`.
- [x] Homepage, public navigation and footer link to latest published articles.
- [x] Public article flow hides drafts and returns 404 for draft direct URLs.
- [x] Admin delete modal contract preserved for article delete.
- [x] Full `php artisan test` pass after Phase 14: 65 tests / 776 assertions.
- [x] Phase 12.2 completed with notes: BMW 330i product image expansion.
- [x] Phase 12.3 completed with notes: all product image expansion.
- [x] Phase 13 completed with notes: product flow normalization + accessory order module.
- [x] Accessory order public/admin flow added with dedicated `accessory_orders` table.
- [x] Car/motorbike keep test-drive, quote, compare and specs; accessories use order/contact only.
- [x] Catalog/accessories cards now align height and CTA footer.
- [x] Admin product images use `Product::displayImageUrl()` instead of raw storage URLs.
- [x] Local `public/storage` junction recreated for `bmw_laravel`.
- [x] Full `php artisan test` pass: 54 tests / 669 assertions.
- [x] All 25 seeded products now have at least 6 usable local ProductImage records.
- [x] `ProductImageExpansionSeeder` is idempotent: 162 records after rerun.
- [x] `PublicUiPhase12_3Test` pass: 3 tests / 484 assertions.
- [x] BMW 330i Sedan now has 9 ProductImage records after seeding.
- [x] Product detail renders up to 8 distinct BMW 330i images.
- [x] `PublicUiPhase12_2Test` pass: 3 tests / 26 assertions.
- [x] Phase 12 completed with notes: Public UI overhaul + product flow normalization.
- [x] Compare page image bug fixed with safe product image resolver.
- [x] Accessories no longer show test-drive CTA on detail pages.
- [x] Public UI updated for homepage, nav, footer, catalog, compare and detail pages.
- [x] `PublicUiPhase12Test` pass: 3 tests / 19 assertions.
- [x] Phase 11 completed with notes: Product detail BMW-inspired redesign.
- [x] `/catalog/{product}` keeps route name `products.show`.
- [x] Product detail preserves booking, quote, compare, specs modal and gallery.
- [x] Phase 10 completed with notes: Admin Dashboard Analytics + Custom Delete Modal.
- [x] `/dashboard` now uses `Admin\DashboardController@index`.
- [x] `/dashboard` has middleware `web`, `auth`, `verified`, `admin`.
- [x] `DashboardTest` pass: 4 tests / 10 assertions.
- [x] Full `php artisan test` pass toan bo.

## Next maintenance tasks

- Optional cleanup: remove local Browser QA article records from dev DB only if explicitly requested.
- Auth/register/settings tests da duoc dong bo voi route that.
- Kiem tra runtime dashboard tren trinh duyet.
- Kiem tra delete modal bang thao tac thuc te.
- Public storage link local da duoc dong bo lai trong Phase 13.
- Admin product images da dung helper `displayImageUrl()` trong Phase 13.
- Bao tri/lam sach cac asset generated neu can toi uu dung luong repository.

Lộ trình phát triển hệ thống chuyên biệt cho xe hơi và xe máy, tập trung vào trải nghiệm đặt cọc và quản lý cấu hình xe linh hoạt với ngôn ngữ thiết kế BMW Modern.

## Phase 1: Foundation, Auth & BMW Showroom UI
- [x] Khởi tạo project Laravel 12 & Cấu hình MySQL.
- [x] Gỡ bỏ hoàn toàn Inertia/React/TS để tối ưu cho Blade Stack.
- [x] Cài đặt Laravel Breeze (Blade stack) & Alpine.js.
- [x] **BMW Showroom UI Overhaul**:
    - Thiết lập Design System: 0px radius, Outfit font, BMW Blue accent.
    - Xây dựng Layout Master & Navigation bold typography.
    - Xây dựng Welcome Page cinematic với Hero & Featured Cards.
- [x] Ổn định môi trường Build (Vite v6 & Tailwind v4).
- [x] Khởi tạo Git & Cleanup middleware tàn dư.

## Phase 2: Core Data Structure & Admin CRUD
- [x] Migration: Brands, Products (với JSON specs & SoftDeletes).
- [x] Xây dựng Admin CRUD cho Brands.
- [x] Xây dựng Admin CRUD cho Products:
    - Giao diện nhập liệu theo Brand Design (sharp rectangles).
    - Logic lưu trữ Specifications (JSON).
- [x] Seeders cho dữ liệu xe BMW mẫu (M5, S1000RR, i7).

## Phase 3: Public Catalog & Comparison Matrix
- [x] Trang chủ: Hiển thị xe mới nhất, xe nổi bật (BMW Style).
- [x] Trang danh sách (Catalog): Tích hợp Bộ lọc & Tìm kiếm (Service layer).
- [x] Hệ thống So sánh xe (Comparison Matrix):
    - Normalized JSON specs matrix.
    - Alpine.js persistent selection (LocalStorage).
- [x] Trang chi tiết xe: Hiển thị media gallery & specs từ JSON.

## Phase 4: Ordering & Stock Locking System
- [x] Hệ thống Giỏ hàng (Cart Service): Dual-mode (Session/Database) với Auto-merge.
- [x] Quy trình Đặt cọc (Checkout Flow): View Cart, Checkout, Success.
- [x] Bảo mật & Integrity (Stock Locking):
    - `DB::transaction` & `lockForUpdate`.
    - Deadlock prevention (Sorting by ID).
    - Price & Name Snapshots in `order_items`.
- [x] Tự động hóa: Task Scheduling cho đơn hàng hết hạn (restore stock).
- [x] Verification: Toàn bộ luồng nghiệp vụ đã được Feature Tested.

## Phase 5: Management & Final Polish (Current)
- [ ] Giao diện Admin: Quản lý đơn đặt cọc & Xác nhận thanh toán.
- [ ] Client Profile: Xem lịch sử đơn hàng.
- [ ] Tối ưu UI/UX, hướng dẫn bàn giao hệ thống.
