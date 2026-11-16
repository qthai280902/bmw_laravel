# Project Roadmap - Vehicle E-commerce v1.0

## Current documented status

- [x] Phase 25.1 completed with notes: Full Laravel AI SDK & MCP Server Upgrade — unified `GeminiAgentRunner`, Streaming SSE (`POST /ai/showroom-assistant/stream` & `/api/ai/assistant/stream` with unbuffered flush + `ReadableStream` & `AbortController` UI + automatic JSON fallback), Multimodal Vision (`Laravel\Ai\Files\Image::fromUpload()`), quota-saving `ShowroomRecommendationExtractor` (`HasStructuredOutput`), Admin Article AI (`ArticleEditorialAgent` + `ArticleEditorialAiService` + `POST /admin/articles/ai-assist`), CRM Lead Classification (`2026_11_16_000001_add_ai_lead_columns_to_appointments_table`, `AppointmentLeadClassifierAgent`, `AppointmentLeadClassifier`, `AppointmentObserver`, `Admin\AppointmentTable` priority badges & filter), Laravel MCP Server (`routes/ai.php`, `BmwShowroomServer` at `/mcp/showroom` & `bmw-showroom` with `SearchVehiclesMcpTool`, `GetVehicleDetailsMcpTool`, `SearchShowroomKnowledgeMcpTool`), and `tests/Feature/Phase25_1AiSdkCapabilitiesTest.php` (`5 passed, 49 assertions`). Full `php artisan test --compact` pass: **146 passed (1686 assertions)** in 99.24s.
- [x] Phase 25 completed with notes: Real-Time Interactive UX with Laravel Livewire (`livewire/livewire` v4.4.8) using Single-Alpine ESM integration (`vendor/livewire/livewire/dist/livewire.esm` + `@livewireStyles` + `@livewireScriptConfig` + `Livewire.hook('morph.updated', ...)` syncing `initScrollReveal()` and `syncComparisonButtons()` on `vehicle-card`), custom 0px `rounded-none` Livewire pagination (`livewire.pagination.bmw` + `WithBmwPagination`), shared `User::isAdmin()` / `AuthorizesAdminLivewire` + `AccessoryOrder::transitionToStatus()` + `StoreAppointmentRequest::sharedRules()` / `StoreAccessoryOrderRequest::sharedRules()`, and 6 Livewire components (`CatalogBrowser`, `ArticleHub`, `BookingFormWizard`, `AccessoryOrderCalculator`, `Admin\AppointmentTable`, `Admin\AccessoryOrderTable`) preserving Progressive Enhancement `POST`/`PATCH` flows & `<= 8` SQL queries on `/admin/accessory-orders`, verified by `tests/Feature/Phase25LivewireInteractiveUxTest.php` (`6 passed, 91 assertions`) and full `php artisan test --compact` (**141 passed (1637 assertions)** in 27.05s).
- [x] Phase 24.1 completed with notes: Context-Aware Action Cards Hotfix — post-answer entity extraction (`extractMentionedEntitySuggestions` in `ShowroomAssistantService` with Unicode boundaries, length-desc matching, unique short model aliases & narrative order), `shouldIncludeCurrentProductSuggestion()` dropping unrelated page `current_product` on off-product / policy queries, auto `/compare?ids=...` when >= 2 vehicles are introduced, `ShowroomAssistant` markdown link instruction, `resources/js/app.js` concrete vehicle/article label preservation + `canonicalActionUrl()` dedupe + `npm run build` (`app-y6mvR-xi.js`), and `test_action_cards_follow_ai_mentioned_vehicles_and_drop_unrelated_page_context_product`. Full `php artisan test --compact` pass: **135 passed (1539 assertions)**.
- [x] Phase 24 completed with notes: Hybrid RAG Semantic Search using Cloudflare Workers AI (`@cf/google/embeddinggemma-2`, 768 dims) with 2-account round-robin/cooldown failover, `2026_11_15_000001_add_embedding_columns_to_products_and_articles_tables`, `$hidden` + `toEmbeddingParts()` on `Product` & `Article`, `CloudflareEmbeddingService`, `ProductObserver` & `ArticleObserver` (active/published guards + SHA-256 deduplication), `ShowroomKnowledgeBaseService` Hybrid Score (`strict_boundaries` excluded from cosine), `SearchCatalogTool` semantic fallback, `ai:sync-embeddings {--force}` (`storage/app/private/ai_knowledge_embeddings.json`), and `tests/Feature/Phase24SemanticRagEmbeddingTest.php` (4 tests / 68 assertions). Full `php artisan test --compact` pass: **134 passed (1521 assertions)**; live sync: **25 products, 15 articles, 7 FAQ chunks synced (0 failed)**.
- [x] Phase 23 completed with notes: End-to-End Guest & Real User functional audit + edge-case logic fixes (inactive accessory 404 guard in `AccessoryOrderController`; keyword `search` in `VehicleSearchService` & 0px catalog search box; `service -> maintenance` alias, optional `product_id` for `consult`/`advisor_meeting`, inactive/accessory product & phone regex validation, selective free-text `strip_tags()`, `showroom` persistence & user fallbacks in `StoreAppointmentRequest` / `AppointmentController`; `AccessoryOrder::getTotalAmountAttribute()` with Zero N+1 Admin table & user pre-fill) + `tests/Feature/Phase23UserJourneyE2ETest.php` (6 E2E tests / 165 assertions). Full `php artisan test --compact` pass: **130 passed (1453 assertions)**.
- [x] Phase 22.2 completed with notes: Published 3 in-depth editorial BMW articles (`bmw-i4-m60-gran-coupe-dna-duong-dua-m-thuan-dien`, `so-sanh-bmw-330i-sedan-va-bmw-530i-sedan-chon-xe-nao`, `bmw-x3-m50-xdrive-the-he-moi-chuan-muc-sav-hieu-nang-cao`) in `database/seeders/ArticleSeeder.php` and runtime DB using 100% verified `public/images/cars/...` assets, Markdown compatible with `.article-content` & `articleCanvasStudio()`, verified HTTP `200` on public/admin routes and AI retrieval via `SearchArticlesAndOffersTool` & `ShowroomKnowledgeBaseService`. Full `php artisan test --compact` pass: **124 passed (1288 assertions)**.
- [x] Phase 22.1 completed with notes: UX/Markdown Hotfixes (`localStorage` 16-message `suggestions`/`actions` persistence on arrival, `max 4` prioritized Action Cards, backend `sanitizeAndRepairMarkdownLinks` fixing `[]()`, `#`, XSS parens, hallucinated DB slugs, and `/dat-lich`/`/phu-kien` legacy routes), `CompareVehiclesTool` companion auto-suggestion, shadcn/ui 0px `grid-cols-2` chat panel (`sm:w-[420px]`), and RAG Knowledge Base (`resources/knowledge/bmw_showroom_knowledge.json`, `ShowroomKnowledgeBaseService`, `SearchKnowledgeBaseTool`, prompt grounding). Full `php artisan test --compact` pass: **124 passed (1288 assertions)**.
- [x] Phase 22 completed with notes: AI Agent Tool Calling (`SearchCatalogTool` with Vietnamese synonyms, `CompareVehiclesTool` with `unique('id')` deduplication, `SearchArticlesAndOffersTool`), multi-turn `Conversational` memory with safe `POST /ai/showroom-assistant/reset`, `/catalog/{slug}` page context + Admin CRM `context_product_id` tracking, 0px Rich Action Cards, safe progressive reveal, and contextual trigger on `products/show.blade.php` (`tests/Feature/AiAssistantPhase22Test.php`). Full `php artisan test --compact` pass: **121 passed (1239 assertions)**.
- [x] Phase 21 & Bổ Sung completed with notes: Admin UI Overhaul (Sidebar, CRM Dashboard, Cinematic Login), Editorial Markdown Showcase & dynamic TOC, Canvas Studio visual block editor, fixed 5-column article table with ellipsis, AI assistant widget upgrade, and full Vietnamese localization (`tests` preserved). Full `php artisan test` pass: 114 tests / 1147 assertions.
- [x] Phase 20 completed with notes: Catalog Accordion Filters & Independent Scroll, Scroll Reveal Landing Page, Product Detail Scrollspy Sticky Nav & `scroll-mt-32`, and Article Typography Hierarchy (`@alpinejs/collapse`, `window.initScrollReveal`, `.article-content`). Full `php artisan test` pass: 114 tests / 1147 assertions.
- [x] Phase 19 completed with notes: Vietnamese Typography Overhaul & `Be Vietnam Pro` Font Standardization (`resources/css/app.css` Tailwind v4 `@theme`, removed `uppercase` & wide tracking from `<p>` and multi-word Vietnamese headings across 6 core public views). Full `php artisan test` pass: 114 tests / 1147 assertions.
- [x] Phase 18 completed with notes: Automated Email Notifications & Queue Module (`AppointmentCreated` & `AccessoryOrderCreated`, Queued Listeners on `notifications` queue, BMW Dark Luxury Transactional Mailables, Null-safe fallback). Full `php artisan test` pass: 114 tests / 1147 assertions.
- [x] Phase 17 completed with notes: Admin AI conversation history + visitor identity linking + admin CRM UI overhaul. Full `php artisan test` pass: 108 tests / 1128 assertions.
- [x] Phase 16.3 completed with notes: Gemini Multi-Key Rotation + Rate Limit Failover & Cooldown.
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
