# Project State

Current phase: Phase 25.1 completed.

## Phase 25.1: Nâng Cấp Toàn Diện Laravel AI SDK (Streaming SSE + Vision + Structured Output + Admin AI) & Laravel MCP Server

- Completion time: 2026-11-16 +07:00.
- Scope:
  - Extracted unified `App\Services\Ai\GeminiAgentRunner` (`GeminiKeyPool` round-robin rotation, cooldown tracking & transient failover) for reuse across all AI agents; added test isolation flags (`AI_LEAD_CLASSIFICATION_ENABLED`, `AI_STRUCTURED_RECOMMENDATIONS_ENABLED`, `AI_ARTICLE_EDITORIAL_ENABLED`) in `config/showroom_ai.php` and `phpunit.xml`.
  - Upgraded Showroom AI Assistant (`app/Ai/Agents/ShowroomAssistant.php`, `app/Services/Ai/ShowroomAssistantService.php`, `app/Http/Controllers/Ai/ShowroomAssistantController.php`): added real-time SSE streaming (`/ai/showroom-assistant/stream` & `/api/ai/assistant/stream` with unbuffered flush headers `Content-Type: text/event-stream; charset=UTF-8`, `Cache-Control: no-cache, no-transform`, `X-Accel-Buffering: no`, lazy stream key rotation before first chunk & deterministic fallback), preserved `/ai/showroom-assistant` + added `/api/ai/assistant`, added multimodal image attachment via `Laravel\Ai\Files\Image::fromUpload()` (`jpeg|png|webp|max:5120`), and added quota-saving companion `App\Ai\Agents\ShowroomRecommendationExtractor` (`HasStructuredOutput`).
  - Upgraded AI widget UI (`resources/views/components/public/ai-assistant-widget.blade.php` keeping zero `rounded-` classes per Phase 22 test contract & safe `x-text` rendering + `resources/js/app.js`): live `ReadableStream` SSE parsing (`chunk` & `done` events), `AbortController` stop button (`Dừng`), transparent JSON fallback, and 0px image upload picker + preview strip.
  - Built Admin AI capabilities: `App\Ai\Agents\ArticleEditorialAgent` + `App\Services\Ai\ArticleEditorialAiService` + `POST /admin/articles/ai-assist` (`Admin\ArticleController::aiAssist`) + button `"AI Tóm Tắt & Tạo Mô Tả SEO"` in `resources/views/admin/articles/_form.blade.php`; migration `2026_11_16_000001_add_ai_lead_columns_to_appointments_table` (`lead_priority`, `lead_intent`, `ai_lead_note`), `App\Ai\Agents\AppointmentLeadClassifierAgent`, `App\Services\Ai\AppointmentLeadClassifier` (with rule-based fallback), `App\Observers\AppointmentObserver` registered in `AppServiceProvider`, and `HOT LEAD` / `TIỀM NĂNG` / `THAM KHẢO` badges + filter in `App\Livewire\Admin\AppointmentTable`.
  - Integrated Laravel MCP Server (`laravel/mcp` `v0.6.7` in `composer.json`, `routes/ai.php` with `Mcp::web('/mcp/showroom', ...)` & `Mcp::local('bmw-showroom', ...)`): `App\Mcp\Servers\BmwShowroomServer` and 3 read-only tools (`SearchVehiclesMcpTool`, `GetVehicleDetailsMcpTool`, `SearchShowroomKnowledgeMcpTool`) reusing `VehicleSearchService` and `ShowroomKnowledgeBaseService`.
  - Created `tests/Feature/Phase25_1AiSdkCapabilitiesTest.php` (5 tests / 49 assertions using `::fake()` and `BmwShowroomServer::tool()`).
- Verification:
  - Migration (`php artisan migrate --force`): `2026_11_16_000001_add_ai_lead_columns_to_appointments_table` pass (`535.42ms`).
  - Pint style check (`vendor\bin\pint --dirty --format agent`): pass (`{"tool":"pint","result":"passed"}`).
  - Vite production asset build (`npm run build`): pass (`55 modules transformed`, `public/build/assets/app-BHARmere.css` 98.03 kB, `public/build/assets/app-C9jVlp3q.js` 368.03 kB in 4.58s).
  - Cache clearing: `php artisan optimize:clear` pass.
  - Phase 25.1 test suite: `5 passed (49 assertions)` in `5.18s`.
  - Full test suite: `php artisan test --compact` pass (**146 passed (1686 assertions)** in **99.24s**, 100% PASS).
- Report: `.planning/baocao/phase-reports/phase-25-1-report.md`.
- Result:
  - PASS 100%.

## Phase 25: Nâng Cấp Trải Nghiệm Tương Tác Thời Gian Thực Với Laravel Livewire

- Completion time: 2026-11-16 +07:00.
- Scope:
  - Installed `livewire/livewire` (`v4.4.8`) and implemented Single-Alpine integration across `resources/js/app.js` (`import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm'`, `Alpine.plugin(collapse)`, `window.syncComparisonButtons()`, `livewire:navigated` & `Livewire.hook('morph.updated', ...)` syncing scroll reveal and `#comparison-bar` / `vehicle-card` comparison state, `Livewire.start()`), `resources/views/layouts/app.blade.php`, `resources/views/components/admin-layout.blade.php`, and `resources/views/layouts/guest.blade.php` (`@livewireStyles` + `@livewireScriptConfig`).
  - Created shared traits & helpers: `App\Livewire\Concerns\WithBmwPagination` + `resources/views/livewire/pagination/bmw.blade.php` (`0px border-radius`, `rounded-none`, `#121215`, `border-white/10`, active `#1c69d4`), `App\Livewire\Concerns\AuthorizesAdminLivewire` + `App\Models\User::isAdmin()` shared with `AdminMiddleware`, `App\Models\AccessoryOrder::transitionToStatus()`, and `StoreAppointmentRequest::sharedRules()` / `StoreAccessoryOrderRequest::sharedRules()`.
  - Built 6 Livewire components with Progressive Enhancement preserving all HTTP routes, input `name` contracts, and `ai_visitor_id` hooks: `App\Livewire\CatalogBrowser` (`/catalog` & locked `/accessories`), `App\Livewire\ArticleHub` (`/tim-hieu-them`), `App\Livewire\BookingFormWizard` (`/booking` with dynamic category-product cascade, URL `product_id` prefill, Flatpickr `wire:ignore` sync, and realtime validation), `App\Livewire\AccessoryOrderCalculator` (`/accessories/{product:slug}/order` with `-/+` quantity stepper, live subtotal, and realtime validation), `App\Livewire\Admin\AppointmentTable` (`/admin/appointments` with instant search/filter, inline status update, and `403` admin guard), and `App\Livewire\Admin\AccessoryOrderTable` (`/admin/accessory-orders` with `with(['product.category'])` single-query pagination keeping `<= 8` SQL queries, inline status update + `confirmed_at` sync, and `403` admin guard).
  - Created `tests/Feature/Phase25LivewireInteractiveUxTest.php` (6 tests / 91 assertions).
- Verification:
  - Pint style check (`vendor\bin\pint --dirty --format agent`): pass (`{"tool":"pint","result":"passed"}`).
  - Vite production asset build (`npm run build`): pass (`55 modules transformed`, `public/build/manifest.json` 0.27 kB, `public/build/assets/app-bzJvKkpC.css` 97.21 kB, `public/build/assets/app-CqDqJ2FY.js` 364.19 kB in 1.78s).
  - Cache clearing: `php artisan optimize:clear` pass.
  - Phase 25 Livewire test suite: `6 passed (91 assertions)` in `0.83s`.
  - Full test suite: `php artisan test --compact` pass (**141 passed (1637 assertions)** in **27.05s**, 100% PASS).
- Report: `.planning/baocao/phase-reports/phase-25-report.md`.
- Result:
  - PASS 100%.

## Phase 24.1: Hotfix Context-Aware Action Cards & Tự Động Trích Xuất Thực Thể Từ Câu Trả Lời AI

- Completion time: 2026-11-16 +07:00.
- Scope:
  - Updated `App\Services\Ai\ShowroomAssistantService` to synthesize `suggestions` AFTER AI answer generation & markdown sanitization (`successfulResponse()`), auto-extracting active `Product` (full name, URL, unique short alias without ambiguous collisions) and published `Article` mentions in exact narrative order (`extractMentionedEntitySuggestions()`), dropping unrelated `current_product` on policy-only or other-vehicle queries (`shouldIncludeCurrentProductSuggestion()`), and auto-appending `/compare?ids=...` when >= 2 vehicles are suggested while keeping backward-compatible direct-query `'Xem ...'` labels for legacy tests.
  - Updated `App\Ai\Agents\ShowroomAssistant::instructions()` to encourage standard `[Tên xe](/catalog/{slug})` and `[Tiêu đề bài viết](/tim-hieu-them/{slug})` markdown links.
  - Updated `resources/js/app.js` (`actionLabel`, `actionMeta`, `extractActions`, `canonicalActionUrl`, `actionPriority`, `normalizeSuggestionList`, `normalizeActionList`) to strip redundant `'Xem '` prefixes cleanly, preserve backend `subtitle`/`kind` and concrete vehicle/article names, deduplicate canonical URLs, and limit to max 4 prioritized Action Cards.
  - Added `test_action_cards_follow_ai_mentioned_vehicles_and_drop_unrelated_page_context_product` in `tests/Feature/Phase24SemanticRagEmbeddingTest.php`.
- Verification:
  - Pint style check (`vendor\bin\pint --dirty --format agent`): pass (`{"tool":"pint","result":"passed"}`).
  - Cache clearing: `php artisan optimize:clear` pass.
  - Vite production asset build: `npm run build` pass (`55 modules transformed`, `public/build/assets/app-y6mvR-xi.js`).
  - AI cluster tests: `23 passed (284 assertions)`.
  - Full test suite: `php artisan test --compact` pass (**135 passed (1539 assertions)** in 21.36s, 100% PASS).
- Report: `.planning/baocao/phase-reports/phase-24-1-report.md`.
- Result:
  - PASS 100%.

## Phase 24: Hybrid RAG Semantic Search Với Cloudflare Workers AI (`@cf/google/embeddinggemma-2`)

- Completion time: 2026-11-16 +07:00.
- Scope:
  - Configured multi-account Cloudflare Workers AI rotation & automatic failover (`CLOUDFLARE_AI_ACCOUNTS`, `CLOUDFLARE_ACCOUNT_ID_1/2` + `CLOUDFLARE_API_TOKEN_1/2`, single-account backward compatibility) in `config/showroom_ai.php`, `.env`, `.env.example`, and test isolation `<env name="AI_EMBEDDINGS_ENABLED" value="false"/>` in `phpunit.xml`.
  - Added migration `2026_11_15_000001_add_embedding_columns_to_products_and_articles_tables` (`embedding` JSON nullable, `embedding_hash` string(64) nullable) and updated `Product` & `Article` (`$hidden`, `casts()`, `toEmbeddingParts()` with `loadMissing('category')`).
  - Built `App\Services\Ai\CloudflareEmbeddingService` with `@cf/google/embeddinggemma-2` payload (`input`, `dimensions: 768`), Task Prefixes (`title: ... | text: ...`, `task: search result | query: ...`), OpenAI-style `result.data[].embedding` + flat array response extraction, round-robin cooldown failover (`429`/`401`/`403`/`5xx`/network errors), 15-minute query cache, `cosineSimilarity()`, and `syncModel()`.
  - Built `ProductObserver` & `ArticleObserver` (guarded by `$product->is_active === true` / `$article->isPublished()`, SHA-256 deduplication, `saveQuietly()`, `try/catch`) registered in `AppServiceProvider`, plus `php artisan ai:sync-embeddings {--force}` (`SyncAiEmbeddingsCommand`) writing `storage/app/private/ai_knowledge_embeddings.json`.
  - Upgraded `ShowroomKnowledgeBaseService` to Hybrid Search (`lexicalScore + semanticScore`, excluding `strict_boundaries` from cosine scoring) and added semantic fallback inside `SearchCatalogTool`.
  - Added `tests/Feature/Phase24SemanticRagEmbeddingTest.php` (4 tests / 68 assertions using `Http::fake()`).
- Verification:
  - Pint style check (`vendor\bin\pint --dirty --format agent`): pass (`{"tool":"pint","result":"passed"}`).
  - Cache clearing: `php artisan optimize:clear` pass.
  - Full test suite: `php artisan test --compact` pass, **134 passed (1521 assertions)** (100% PASS).
  - Local migration: `2026_11_15_000001_add_embedding_columns_to_products_and_articles_tables` pass (`38.12ms`).
  - Live vector sync (`php artisan ai:sync-embeddings`): **25/25 active products, 15/15 published articles, 7/7 FAQ chunks** synced (`0 failed`), `ai_knowledge_embeddings.json` (`137,597 bytes`); second run skipped all `25 / 15 / 7` unchanged records via SHA-256 deduplication.
- Report: `.planning/baocao/phase-reports/phase-24-report.md`.
- Result:
  - PASS 100%.

## Phase 23: Kiểm Toán Chức Năng Người Dùng (Guest & Real User) & Kiểm Thử Logic E2E

- Completion time: 2026-11-15 +07:00.
- Scope:
  - Audited all Guest, Authenticated Customer, and Admin RBAC boundaries across public catalog, vehicle comparison, 5-flow booking, accessory orders, editorial content/offers, AI assistant/RAG, and Admin CRM sync.
  - Patched 5 edge-case logic gaps: blocked inactive accessories (`is_active = false`) in `AccessoryOrderController`; added keyword search (`search`/`keyword`/`q`) in `VehicleSearchService::search()` + 0px search input in `resources/views/products/index.blade.php`; standardized `service -> maintenance` alias, nullable `product_id` for `consult`/`advisor_meeting`, inactive/accessory boundary checks & phone regex in `StoreAppointmentRequest`; selective `strip_tags()` only on free-text fields (leaving `email`/`appointment_date` untouched); preserved `showroom` column, `trade_in`/`service` `meta_data`, and `Auth::user()` fallbacks in `AppointmentController`; added `AccessoryOrder::getTotalAmountAttribute()` with Zero N+1 eager-loading (`with(['product.category'])`) across Admin views and user pre-fill/fallback on `StoreAccessoryOrderRequest`.
  - Created `tests/Feature/Phase23UserJourneyE2ETest.php` (6 E2E tests / 165 assertions covering all 6 user journey groups, mail queues, Zero N+1, RBAC, and Admin CRM sync).
  - Documented Phase 23 across `.planning/phases/phase-23-e2e-user-journey-logic-audit/{PLAN.md,CHECKLIST.md,SUMMARY.md}`, `.planning/baocao/phase-reports/phase-23-report.md`, and conversation artifacts (`phase_23_plan.md`, `phase_23_report.md`).
- Verification:
  - Pint style check (`vendor\bin\pint --dirty --format agent`): pass (`{"tool":"pint","result":"passed"}`).
  - Cache clearing: `php artisan optimize:clear` pass.
  - Vite production asset build: `npm run build` pass (`55 modules transformed`, `public/build/assets/app-B9LNB7Ih.css` 96.54 kB, `public/build/assets/app-B0NegWBV.js` 100.66 kB in 1.19s).
  - Phase 23 E2E suite: `6 passed (165 assertions)` in 1.27s.
  - Full test suite: `php artisan test --compact` pass, **130 passed (1453 assertions)** in 20.53s (100% PASS).
- Report: `.planning/baocao/phase-reports/phase-23-report.md`.
- Result:
  - PASS 100%.

## Phase 22.2: Xuất Bản 3 Bài Viết Editorial Chuyên Sâu & Kiểm Chứng Hiển Thị / RAG AI

- Completion time: 2026-11-14 +07:00.
- Scope:
  - Updated `database/seeders/ArticleSeeder.php` to support explicit `slug`, `seo_title`, and `seo_description` fields in `Article::updateOrCreate()` and added 3 in-depth editorial BMW articles (`bmw-i4-m60-gran-coupe-dna-duong-dua-m-thuan-dien`, `so-sanh-bmw-330i-sedan-va-bmw-530i-sedan-chon-xe-nao`, `bmw-x3-m50-xdrive-the-he-moi-chuan-muc-sav-hieu-nang-cao`) using verified real disk images under `public/images/cars/...` for `cover_image` and Markdown inline images.
  - Kept seeder timestamps at `now()->subDays(3)->subHours(1..3)` to preserve `PublicHomepagePhase15Test` homepage top-3 assertions while setting `status = 'published'` and `published_at = now()` on the 3 new slugs in the runtime DB (`15` published articles total).
  - Verified all cover/inline image files exist on disk (270 KB – 2.1 MB), `/tim-hieu-them/{slug}` and `/admin/articles/{slug}/edit` (`articleCanvasStudio`) return HTTP `200`, and both `SearchArticlesAndOffersTool` and `ShowroomKnowledgeBaseService::retrieveRelevantChunks()` accurately retrieve all 3 articles for `"i4 M60"`, `"so sánh 330i và 530i"`, and `"X3 M50"`.
- Verification:
  - Pint style check (`vendor\bin\pint --dirty --format agent`): pass (`{"tool":"pint","result":"passed"}`).
  - Cache clearing: `php artisan optimize:clear` pass.
  - Full test suite: `php artisan test --compact` pass, **124 passed (1288 assertions)** in 31.56s (100% PASS).
- Report: `.planning/baocao/phase-reports/phase-22-2-report.md`.
- Result:
  - PASS 100%.

## Phase 22.1: Hotfix UX/Markdown, Tái Thiết Kế UI shadcn/ui & Kiến Trúc RAG Showroom AI

- Completion time: 2026-11-14 +07:00.
- Scope:
  - Persist & restore Action Cards seamlessly across page navigations (`resources/js/app.js`): limit `maxStoredMessages` to 16, persist validated `suggestions` & `actions` immediately upon API response arrival, prioritize practical actions (Chi tiết xe → Đặt lái thử → Báo giá → So sánh/Ưu đãi) with a strict max-4 cap.
  - Sanitize & repair malformed/unsafe/broken markdown links (`ShowroomAssistantService::sanitizeAndRepairMarkdownLinks`, `resources/js/app.js`): neutralize empty `[]()`, `#`, XSS parens (`javascript:alert(1)`), hallucinated `/catalog/{slug}`, `/tim-hieu-them/{slug}`, and `/accessories/{slug}/order` slugs against live active/published records, map legacy `/dat-lich` → `/booking` & `/phu-kien` → `/accessories`, and convert unmapped links to bold (`**Nhãn**` / `type: 'strong'`).
  - Upgrade duplicate-vehicle comparison (`CompareVehiclesTool`) to preserve `status: needs_more_vehicles` / `compare_url: null` while attaching nearest same-segment `suggested_comparison_vehicle` and ready `suggested_compare_url`; tighten `ShowroomAssistant::instructions()` advisor cadence.
  - Redesign chat widget (`ai-assistant-widget.blade.php`, `resources/css/app.css`) with shadcn/ui 0px cards (`sm:w-[420px] w-[calc(100vw-1.5rem)] max-w-[420px]`, `.scrollbar-none`), compact `BMW AI` badge header removing per-message avatars, and balanced `grid-cols-2` Action Cards (`col-span-2` first card when count is odd).
  - Build RAG architecture: `resources/knowledge/bmw_showroom_knowledge.json` (7 chunks), `App\Services\Ai\ShowroomKnowledgeBaseService` (resilient `File::exists()` + `is_array()` JSON loading + Hybrid Retrieval reusing shared `ShowroomCatalogMatcher::scoreArticleQuery()`), 4th AI Tool `App\Ai\Tools\SearchKnowledgeBaseTool` (`search_knowledge_base`), and automatic `[TÀI LIỆU TRI THỨC RAG ĐƯỢC XÁC THỰC]` injection inside `buildPrompt()`.
- Verification:
  - Pint style check: pass (`{"tool":"pint","result":"passed"}`).
  - Cache clearing: `php artisan optimize:clear` pass.
  - Vite production asset build: `npm run build` pass (`55 modules transformed`, `public/build/assets/app-BXsuYffs.css` 96.50 kB, `public/build/assets/app-B0NegWBV.js` 100.66 kB in 946ms).
  - AI test cluster: `20 passed (320 assertions)` in 6.39s.
  - Full test suite: `php artisan test --compact` pass, **124 passed (1288 assertions)** in 20.48s (100% PASS).
- Report: `.planning/baocao/phase-reports/phase-22-1-report.md`.
- Result:
  - PASS 100%.

## Phase 22: AI Agent Tool Calling, Conversational Memory, Rich Action Cards & Contextual UX

- Completion time: 2026-11-13 +07:00.
- Scope:
  - Build `App\Services\Ai\ShowroomCatalogMatcher` + 3 read-only AI Tools (`SearchCatalogTool`, `CompareVehiclesTool` with ID deduplication `unique('id')` and single-match guidance, `SearchArticlesAndOffersTool`) supporting Vietnamese synonyms (`suv` <-> `sav`, `xe điện` <-> `i4`/`iX`, `xe máy`/`mô tô`/`pkl` <-> `motorrad`).
  - Upgrade `App\Ai\Agents\ShowroomAssistant` (`#[MaxSteps(6)]`, `HasTools`, `Conversational`, normalized real routes) and `App\Services\Ai\ShowroomAssistantService` (multi-turn `AiChatMessage` history after reset marker, config-driven limits in `config/showroom_ai.php`, page context summary & `current_product`).
  - Add safe reset endpoint `POST /ai/showroom-assistant/reset` handling empty/stale `visitor_id` gracefully while preserving CRM database logs.
  - Sync active product context (`context_product_id`, `context_product_name`, `context_product_slug`) from `/catalog/{slug}` into `ai_chat_sessions`, `AiConversationTracker`, and Admin CRM views (`admin/ai-conversations/{index,show}.blade.php`).
  - Upgrade frontend widget (`resources/js/app.js`, `ai-assistant-widget.blade.php`, `resources/css/app.css`) and `resources/views/products/show.blade.php` with 0px Rich Action Cards (`x-text` only, zero `x-html`), safe inline link reveal (`visiblePartText(...).length > 0`), `Esc` minimize, `"Làm mới"` reset fallback, and contextual trigger button `"Hỏi trợ lý AI về ..."`.
  - Add `tests/Feature/AiAssistantPhase22Test.php` covering all backend tools, memory/reset, CRM context sync, and 0px/XSS frontend contracts.
- Verification:
  - Pint style check: pass (`{"tool":"pint","result":"passed"}`).
  - Cache clearing: `php artisan optimize:clear` pass.
  - Vite production asset build: `npm run build` pass (`55 modules transformed`, `public/build/assets/app-CjKkumYC.css` 95.92 kB, `public/build/assets/app-DrWX-9im.js` 98.09 kB in 1.23s).
  - Full test suite: `php artisan test --compact` pass, **121 passed (1239 assertions)** in 31.22s (100% PASS).
- Report: `.planning/baocao/phase-reports/phase-22-report.md`.
- Result:
  - PASS 100%.

## Phase 21 & Bổ Sung: Admin UI Overhaul, Cinematic Login, Public Article Editorial, Canvas Studio, Cố Định Bảng Quản Trị, Nâng Cấp Chatbot AI & Toàn Diện Việt Hóa

- Completion time: 2026-11-10 +07:00.
- Scope:
  - Redesign Admin Login and Guest layout (`resources/views/layouts/guest.blade.php`, `resources/views/auth/login.blade.php`): Cinematic focus layout, `.bg-ambient-showroom` glow, `.bg-card-luxury` surface, Title Case labels, sleek inputs with BMW Blue focus ring, gradient submit button, and Vietnamese copyright footer.
  - Upgrade Public Article View (`resources/views/articles/show.blade.php`, `resources/css/app.css`): Markdown rendering with `{!! \Illuminate\Support\Str::markdown($article->body) !!}`, 2-column luxury editorial layout, H2 with BMW Blue accent bar (`#1C69D4`), blockquote, list, inline images with captions, Alpine.js dynamic TOC sidebar, consultation/test-drive booking card, and related articles.
  - Overhaul Admin CRM Shell & Dashboard (`resources/views/components/admin-layout.blade.php`, `resources/views/components/admin/sidebar-link.blade.php`, `resources/views/admin/dashboard.blade.php`, `resources/views/admin/articles/index.blade.php`): Title Case navigation with `Be Vietnam Pro`, 4 luxury stat cards with dedicated SVG icons, elegant SVG empty states ("Chưa ghi nhận hội thoại mới từ Trợ lý ảo"), emerald status badge, quick action links.
  - Build Canvas Studio Visual Block Editor (`resources/views/admin/articles/_form.blade.php`): Alpine.js `articleCanvasStudio()` with 4 block types (Heading H2/H3, Rich Paragraph, Single Image + Caption, Quote / Spec Highlight), block reordering (up/down/delete), Toolbar drawer, Live Public Preview tab, Markdown raw tab, automatic two-way serialization into hidden/synced `name="body"` textarea for 100% backward compatibility.
  - Cố Định Khung Bảng Quản Trị (`resources/views/admin/articles/index.blade.php`): `table-fixed w-full`, 5 cột tỷ lệ chuẩn (`42%`, `18%`, `12%`, `14%`, `14%`), xử lý ellipsis chữ dài `truncate block` kèm tooltip `title="..."` trên tiêu đề, mô tả và chuyên mục.
  - Nâng Cấp Chatbot AI Showroom (`resources/views/components/public/ai-assistant-widget.blade.php`, `resources/js/app.js`, `app/Services/Ai/ShowroomAssistantService.php`, `app/Ai/Agents/ShowroomAssistant.php`): Nút nổi LED pulse online, viền xanh `#1C69D4`, typing indicator 3 chấm nảy sóng âm có delay bậc thang `animate-bounce`, 6 prompt chips (bảo tồn 3 test chips), hiển thị markdown link an toàn bằng template Alpine (không dùng `x-html`), System Prompt chỉ thị tư vấn chuẩn xác không bịa giá và xưng hô lịch thiệp.
  - Toàn Diện Việt Hóa Website: Admin Shell (`Cổng Quản Trị Showroom`, `Hệ Thống Trực Tuyến`, `Xem Trang Chủ`), Dashboard CRM (`Khách Hàng Tiềm Năng Từ AI Mới Nhất`, `Toàn thời gian`, `Hôm nay`, `Chờ xử lý`, `Trợ lý AI`), Public Landing & Cards (`Dòng sản phẩm`, `Ô tô BMW`, `Phụ kiện chính hãng`, `Hậu mãi & Dịch vụ`, `Tuyển chọn đặc quyền`, `Xem Toàn Bộ Sản Phẩm`, `Chuyên mục Showroom`, `Hành trình Showroom`, `Cố vấn tận tâm`, `Báo giá chính xác`, `So sánh trực quan`, `Đặc quyền ưu đãi`, `Chính Hãng`, `Bảng so sánh`), bảo tồn nguyên vẹn các chuỗi assert test.
- Verification:
  - Pint style check: pass (`{"tool":"pint","result":"passed"}`).
  - Cache clearing: `php artisan optimize:clear` pass.
  - Vite production asset build: `npm run build` pass (`55 modules transformed`, `public/build/assets/app-CMpl15ba.css` 95.14 kB, `public/build/assets/app-DC-JuK-4.js` 93.76 kB in 981ms).
  - Full test suite: `php artisan test` pass, **114 tests / 1147 assertions (100% PASS)** in 19.03s.
- Report: `.planning/baocao/phase-reports/phase-21-report.md`.
- Result:
  - PASS 100%.

## Phase 20: Catalog Accordion Filters, Scroll Reveal Landing Page, Product Detail Scrollspy & Article Typography Hierarchy

- Completion time: 2026-11-09 +07:00.
- Scope:
  - Install `@alpinejs/collapse` and configure `Alpine.plugin(collapse)` + global `window.initScrollReveal` (`IntersectionObserver`) in `resources/js/app.js`.
  - Add `.reveal-on-scroll`, `.is-revealed`, `.scrollbar-none`, reduced-motion overrides, and `.article-content` (`h2`, `h3`, `p`, `strong`/`b`) rules in `resources/css/app.css`.
  - Catalog Sidebar Accordion (`x-data="{ open: true }"`, `x-collapse`, rotating chevron SVG) and independent desktop scrolling (`lg:sticky lg:top-24 lg:max-h-[calc(100vh-7rem)] lg:overflow-y-auto scrollbar-none`) in `resources/views/products/index.blade.php`.
  - Landing Page Scroll Reveal and image/card hover polish (`group-hover:scale-105`, `hover:border-[#1C69D4]/50`) in `resources/views/welcome.blade.php`.
  - Product Detail Scrollspy sticky navigation (`IntersectionObserver` with `rootMargin: '-20% 0px -60% 0px'`, active tab highlight `border-b-2 border-[#1C69D4] bg-blue-950/20`, smooth scroll, and `scroll-mt-32` section offsets) in `resources/views/products/show.blade.php`.
  - Article Typography & Visual Hierarchy overhaul in `resources/views/articles/show.blade.php` and `resources/views/articles/index.blade.php`.
- Verification:
  - Pint style check: pass (`{"tool":"pint","result":"passed"}`).
  - Cache clearing: `php artisan optimize:clear` pass.
  - Vite production asset build: `npm run build` pass (`55 modules transformed`, `public/build/assets/app-B4Eogft3.css` 84.25 kB, `public/build/assets/app-Dma1Dh-p.js` 93.62 kB in 1.09s).
  - Full test suite: `php artisan test` pass, **114 tests / 1147 assertions (100% PASS)**.
- Report: `.planning/baocao/phase-reports/phase-20-report.md`.
- Result:
  - PASS 100%.

## Phase 19: Vietnamese Typography Overhaul & Be Vietnam Pro Font Standardization

- Completion time: 2026-11-08 +07:00.
- Scope:
  - Configure `Be Vietnam Pro` (weights 300–800 and 400 italic) as primary sans-serif typeface in Tailwind CSS v4 (`resources/css/app.css`).
  - Eliminate `uppercase` and wide letter-spacing (`tracking-widest`, `tracking-wider`, `tracking-[0.2em]`) on all `<p>` paragraphs, excerpts, and multi-word Vietnamese headings (`h1`–`h4`) across public Blade views.
  - Standardize `tracking-tight` (`-0.02em`) on headings and `leading-relaxed` (`1.625`) on body paragraphs for optimal Vietnamese diacritic rendering.
- Implemented:
  - `resources/css/app.css`: Added Google Fonts `@import` for `Be Vietnam Pro` on line 1 (before `@import 'tailwindcss';`), defined `--font-sans` inside `@theme`, and configured `:root`, `body`, `h1–h6`, and `p` base rules.
  - Refactored 6 public Blade views: `resources/views/articles/show.blade.php`, `resources/views/articles/index.blade.php`, `resources/views/welcome.blade.php`, `resources/views/services.blade.php`, `resources/views/experiences.blade.php`, `resources/views/products/show.blade.php`.
- Verification:
  - Pint style check: pass (`{"tool":"pint","result":"passed"}`).
  - Cache clearing: `php artisan optimize:clear` pass.
  - Vite production asset build: `npm run build` pass (`public/build/assets/app-izHYdINk.css` 82.20 kB in 1.24s).
  - Full test suite: `php artisan test` pass, **114 tests / 1147 assertions (100% PASS)** in 33.64s.
- Report: `.planning/baocao/phase-reports/phase-19-report.md`.
- Result:
  - PASS 100%.

## Phase 18: Automated Email Notifications & Queue Module

- Completion time: 2026-11-08 +07:00.
- Scope:
  - Event-Driven Architecture for customer touchpoints (appointments & accessory orders).
  - Asynchronous background queue processing on dedicated `notifications` queue.
  - Transactional HTML email templates following BMW Dark Luxury design language (#09090b, 0px border-radius).
  - Comprehensive null-safety for customer email and product relations.
- Implemented:
  - Events: `App\Events\AppointmentCreated`, `App\Events\AccessoryOrderCreated`.
  - Queued Listeners: `App\Listeners\SendAppointmentNotifications`, `App\Listeners\SendAccessoryOrderNotifications` (`ShouldQueue`, queue: `notifications`, tries: 3).
  - Mailables: `AppointmentConfirmationMail`, `AppointmentAlertMail`, `AccessoryOrderConfirmationMail`, `AccessoryOrderAlertMail` (`ShouldQueue`, queue: `notifications`).
  - Blade templates: `resources/views/emails/layouts/bmw-transactional.blade.php`, `emails/customer/*`, `emails/admin/*`.
  - Controllers: Dispatched events in `Client\AppointmentController@store` and `AccessoryOrderController@store`.
  - Config: Added `mail.showroom_admin` (default `advisor@bmw.com`) and updated `.env.example`.
- Verification:
  - Pint style check: pass (`{"tool":"pint","result":"passed"}`).
  - Cache clearing: `php artisan optimize:clear` pass.
  - Focused feature tests: `tests/Feature/Notification/NotificationFlowTest.php` pass, 6 tests / 14 assertions.
  - Full test suite: `php artisan test` pass, **114 tests / 1147 assertions (100% PASS)** in ~20.45s.
- Result:
  - PASS 100%.

## Docker Completion (Post Phase 17)

- Test time: 2026-06-17 +07:00.
- Scope:
  - Complete local Docker development setup.
  - Keep local secrets and dependencies out of source cleanup.
  - Document Docker usage and cleanup decisions.
- Implemented:
  - `Dockerfile` now uses PHP 8.4 FPM with Composer 2 and Node.js 24.
  - `docker-compose.yml` defines `app`, `nginx`, and `mysql` services.
  - Added `docker/php/php.ini`.
  - Added `docker/nginx/default.conf`.
  - Added `.env.docker.example`.
  - Updated `.dockerignore`.
  - Updated README Docker instructions.
  - Added `.planning/kientruc/DOCKER.md`.
- Cleanup:
  - `vendor/` was not cleaned, moved, deleted, or modified.
  - `node_modules/` was not cleaned, moved, deleted, or modified.
  - No tracked file was moved into `.no/`.
- Verification:
  - `composer validate --no-check-publish`: pass.
  - `vendor\bin\pint --test`: pass.
  - `php artisan view:cache`: pass.
  - `npm.cmd run build`: pass.
  - `php artisan test --compact`: pass, 108 tests / 1128 assertions.
  - Docker CLI/Compose unavailable locally, so Docker runtime verification is pending.
- Result:
  - App regression PASS.
  - Docker runtime CHUA PASS until `docker compose config/build/up` and container tests pass on a Docker-capable host.

## Phase 17

- Scope:
  - Admin AI conversation history.
  - Visitor/session/IP tracking.
  - Customer linking from appointment/accessory order submits.
  - Admin AI conversation UI.
  - Admin CRM UI polish.
- Implemented:
  - `ai_chat_sessions` and `ai_chat_messages`.
  - `appointments.ai_visitor_id`.
  - `accessory_orders.ai_visitor_id`.
  - `App\Services\Ai\AiConversationTracker`.
  - `App\Http\Controllers\Admin\AiConversationController`.
  - admin views under `resources/views/admin/ai-conversations`.
  - visitor id generation in `resources/js/app.js`.
  - admin dashboard AI stats widget.
- Verification:
  - Phase 17 focused tests pass, 8 tests / 43 assertions.
  - related AI/admin tests pass, 34 tests / 310 assertions.
  - `php artisan test` pass, 108 tests / 1128 assertions.
  - `npm.cmd run build` pass.
  - `php artisan route:list --path=admin/ai -v` pass.
  - Browser QA pass for admin AI list/detail and public-to-admin linking.
- Result:
  - PASS CO GHI CHU.

## CI Workflow Fix (Post Phase 17)

- Test time: 2026-06-17 +07:00.
- Scope:
  - Fix GitHub Actions `Verify & Test` workflow failing on `composer install`.
  - Align CI PHP version and Node.js version with local development.
- Implemented:
  - Upgraded `setup-php` version to 8.4 to safely support composer requirements.
  - Added Node.js version 24 setup.
  - Replaced `npm install` with `npm ci`.
  - Added SQLite test DB creation step.
  - Added test environment overrides in `verify.yml` environment block.
- Verification:
  - `composer validate`: pass.
  - `npm.cmd ci`: pass.
  - `php artisan test`: pass.
  - `vendor/bin/pint --test`: pass.
  - `npm.cmd run build`: pass.
- Result:
  - CI Workflow updated and ready for GitHub push.

## Phase 16.3

- Test time: 2026-06-17 +07:00.
- Scope:
  - Gemini multi-key rotation.
  - Rate limit failover and cooldown.
  - Safe logging and secret handling for AI provider keys.
- Config:
  - existing `GEMINI_API_KEY` remains supported.
  - added `GEMINI_API_KEYS`.
  - added `GEMINI_KEY_COOLDOWN_SECONDS`, default 120.
  - added `GEMINI_KEY_ROTATION`, default `round_robin`.
  - `.env.example` uses placeholders only and no longer contains a sample encoded app key value.
- Key pool:
  - class: `App\Services\Ai\GeminiKeyPool`.
  - combines primary and additional Gemini keys.
  - supports comma, newline and pipe separators.
  - trims, deduplicates and ignores empty values.
  - has no hard-coded key count limit.
  - round-robin cursor stored in cache.
  - cooldown uses cache keys based on key index and fingerprint, not raw key text.
- AI service:
  - `ShowroomAssistantService` uses temporary Laravel AI provider aliases per Gemini attempt.
  - temporary aliases are forgotten after each attempt to avoid stale provider cache.
  - `RateLimitedException` marks a candidate cooldown and tries the next key.
  - all-key rate limit returns `reason: rate_limited` and a friendly fallback.
  - endpoint remains `POST /ai/showroom-assistant`.
- Commands:
  - focused AI tests: pass, 22 tests / 104 assertions.
  - `php artisan config:clear`: pass.
  - `php artisan cache:clear`: pass after local XAMPP MySQL was started.
  - `php artisan view:clear`: pass.
  - `php artisan view:cache`: pass.
  - `vendor\bin\pint --dirty --format agent`: pass.
  - `npm.cmd run build`: pass.
  - `php artisan test`: pass, 100 tests / 1083 assertions.
  - `php artisan route:list --path=ai -v`: pass, `POST /ai/showroom-assistant` keeps `web` and `throttle:12,1`.
- Browser QA:
  - live widget prompt: `tim giup toi chiec bmw s1000rr`.
  - result: normal BMW S1000RR / BMW Motorrad answer.
  - action chips: product detail, quote, test drive and BMW Motorrad catalog.
  - broken images: 0.
  - console errors: 0.
  - horizontal overflow: false.
  - visible secret pattern: false.
- Playwright CLI:
  - mobile 390x900 smoke pass.
  - launcher visible.
  - panel within viewport.
  - broken images: 0.
  - console errors: 0.
  - no `x-html`.
  - no draggable nodes.
  - visible secret pattern: false.
- Notes:
  - local MySQL was initially off, causing default database-backed `cache:clear` and Browser QA to fail until XAMPP MySQL was started.
  - temporary PHP server, XAMPP MySQL process and Playwright CLI artifacts were stopped/removed after QA.
  - automated tests use Laravel AI fakes and do not call Gemini.
- Report: `.planning/baocao/phase-reports/phase-16-3-report.md`.
- Result: PASS CO GHI CHU.

## Phase 16.2

- Test time: 2026-06-16 +07:00.
- Scope:
  - AI Assistant UX overhaul.
  - Product context intelligence fix for BMW Motorrad and model-code queries.
  - Safe assistant answer rendering with action chips.
  - Fresh widget state behavior and mobile viewport polish.
- Widget:
  - fixed launcher/panel/side-tab pattern.
  - no drag handle or persisted coordinates.
  - greeting is an intro card, not a stored assistant message.
  - new localStorage key: `bmw_ai_assistant_state_v4`.
  - legacy v2/v3/position keys are cleared.
  - assistant links are extracted into internal action chips.
  - rendering remains escaped with `x-text`; no `x-html`.
  - mobile panel width now stays inside 390px viewport.
- AI context:
  - includes active public cars, BMW Motorrad, accessories and published articles.
  - excludes appointments, accessory orders, users, admin/internal notes and customer PII.
  - product context includes `product_line`, `slug`, URL and `search_aliases`.
  - aliases cover `330i`, `530i`, `x5`, `s1000rr` and spaced S1000RR variants.
  - assistant prompt now describes the showroom as BMW cars, BMW Motorrad, accessories, offers and services.
- Commands:
  - `php artisan config:clear`: pass.
  - `php artisan cache:clear`: pass.
  - `php artisan view:clear`: pass.
  - `php artisan view:cache`: pass.
  - `vendor\bin\pint --dirty --format agent`: pass.
  - `npm.cmd run build`: pass.
  - `php artisan test`: pass, 89 tests / 1048 assertions.
- Browser QA:
  - pages: `/`, `/catalog`, `/catalog?type=motorbike`, `/catalog/bmw-330i-sedan`, `/catalog/bmw-s1000rr`, `/accessories`, `/tim-hieu-them`, `/booking?type=consult`, `/compare?ids=1,2`.
  - viewports: 1366x768, 768x1024, 390x900.
  - S1000RR live answer: pass.
  - sedan live answer: pass.
  - internal action chip navigation to `/catalog/bmw-s1000rr`: pass.
  - broken images: 0.
  - console errors: 0.
  - horizontal overflow: 0.
  - `x-html`: 0.
  - draggable nodes: 0.
- Playwright CLI:
  - storage-cleared desktop session confirmed intro card and `messageCount = 0`.
  - mobile 390x900 confirmed panel inside viewport.
  - console errors: 0.
  - temporary `.playwright-cli` artifact was removed.
- Known note:
  - One live SUV prompt returned safe fallback due provider `RateLimitedException` after successful live S1000RR and sedan answers.
  - Automated tests use Laravel AI fakes and do not call Gemini.
- Report: `.planning/baocao/phase-reports/phase-16-2-report.md`.
- Result: PASS CO GHI CHU.

## Phase 16.1

- Test time: 2026-06-16 +07:00.
- Scope:
  - AI Assistant widget UX polish.
  - Side-tab hidden mode.
  - Client-side chat history persistence.
  - AI message readability/link rendering verification.
  - Compare logic normalization and regression tests.
- AI widget:
  - Fresh desktop/mobile default now uses compact launcher + greeting instead of a large open panel.
  - Side-tab hidden mode persists through reload.
  - Recent chat history persists through navigation/reload in `localStorage`.
  - Stored history is capped at 24 messages.
  - Email/phone-like contact patterns are redacted before local storage.
  - Clear conversation action added.
  - Markdown/link rendering stays escaped with `x-text`; no `x-html`.
- Compare:
  - Route preserved: `GET /compare`, name `products.compare`.
  - Invalid IDs are ignored.
  - Duplicate IDs are removed.
  - Accessory IDs are ignored.
  - Car and motorbike can be compared because both are vehicles.
  - Requested order is preserved.
  - Server-side max compare count is 4 vehicles.
- Commands:
  - `php artisan config:clear`: pass.
  - `php artisan cache:clear`: pass.
  - `php artisan view:clear`: pass.
  - `php artisan view:cache`: pass.
  - `vendor\bin\pint --dirty --format agent`: pass.
  - `npm.cmd run build`: pass.
  - `php artisan test`: pass, 86 tests / 1006 assertions.
- Browser QA:
  - pages: `/`, `/catalog`, `/catalog/bmw-330i-sedan`, `/accessories`, `/tim-hieu-them`, `/booking?type=consult`, `/compare?ids=1,2`.
  - viewports: 390x900, 768x1024, 1366x768.
  - live AI widget questions sent successfully.
  - fallback visible: false.
  - side-tab hide/reload/reopen: pass.
  - link navigation to `/catalog/bmw-330i-sedan` preserved chat history.
  - broken images: 0.
  - console errors: 0.
  - page-level horizontal overflow: 0.
- Playwright CLI:
  - fresh mobile and desktop sessions confirmed compact launcher default after storage clear.
- Report: `.planning/baocao/phase-reports/phase-16-1-report.md`.
- Result: PASS.

## Phase 16

- Test time: 2026-06-15 +07:00.
- Scope:
  - PHP 8.5 deprecation cleanup for MySQL SSL CA PDO constant.
  - Laravel AI SDK integration.
  - Public BMW AI Showroom Assistant widget.
  - Gemini provider configuration through env/config only.
- PHP 8.5 fix:
  - `config/database.php` now uses `Pdo\Mysql::ATTR_SSL_CA` when available, with fallback to `PDO::MYSQL_ATTR_SSL_CA`.
  - DB connection logic was preserved.
  - `php artisan test` no longer reports the `PDO::MYSQL_ATTR_SSL_CA` deprecation.
- AI backend:
  - package: `laravel/ai` v0.7.2.
  - agent: `App\Ai\Agents\ShowroomAssistant`.
  - service: `App\Services\Ai\ShowroomAssistantService`.
  - controller: `App\Http\Controllers\Ai\ShowroomAssistantController`.
  - route: `POST /ai/showroom-assistant`, name `ai.showroom-assistant.ask`.
  - middleware: `web`, `throttle:12,1`.
- AI context:
  - includes only active public products and published public articles.
  - excludes appointments, accessory orders, users, admin notes and customer PII.
- Widget:
  - public layout only; admin layout does not render it.
  - desktop opens panel by default.
  - mobile starts as compact launcher/greeting.
  - supports prompts, input, fallback response, drag movement and reduced-motion CSS.
- Commands:
  - `composer validate`: pass.
  - `php artisan config:clear`: pass.
  - `php artisan view:clear`: pass.
  - `php artisan view:cache`: pass.
  - `vendor\bin\pint --dirty --format agent`: pass.
  - `npm.cmd run build`: pass.
  - `php artisan test`: pass, 83 tests / 975 assertions.
- Browser QA:
  - pages: `/`, `/catalog`, `/catalog/bmw-330i-sedan`, `/accessories`, `/tim-hieu-them`, `/booking?type=consult`.
  - viewports: 390x900, 768x1024, 1366x768.
  - page/viewport sweep: 18 combinations.
  - widget present and ready/greeting text visible.
  - desktop drag behavior: pass.
  - missing-key fallback response: pass.
  - broken images: 0.
  - console errors: 0.
  - horizontal overflow: 0.
- Notes:
  - `.env` currently has no `GEMINI_API_KEY`, so live Gemini API response was not exercised.
  - automated tests use Laravel AI fakes and do not call a real AI provider.
- Report: `.planning/baocao/phase-reports/phase-16-report.md`.
- Result: PASS CO GHI CHU.

## Phase 15.1

- Test time: 2026-06-14 22:35:51 +07:00.
- Scope:
  - Public `/tim-hieu-them` article category filter overflow cleanup.
  - Admin sidebar native scrollbar removal polish.
  - Scroll behavior preserved without changing routes or business logic.
- Article filter:
  - source: `resources/views/articles/index.blade.php`.
  - root cause: horizontal `overflow-x-auto` category row exposed a native mini scrollbar.
  - fix: responsive mobile grid and tablet/desktop wrapped pills.
  - active category now has `aria-current="page"`.
- Admin sidebar:
  - source: `resources/views/components/admin-layout.blade.php`.
  - root cause: fixed sidebar internal `overflow-y-auto` nav exposed a native vertical scrollbar.
  - fix: keep `overflow-y-auto`, add `.scrollbar-none`, `overscroll-contain`, tighter grouping and subtle mask fade.
- CSS:
  - added `.scrollbar-none` utility in `resources/css/app.css`.
- Commands:
  - `php artisan config:clear`: pass.
  - `php artisan view:clear`: pass.
  - `php artisan view:cache`: pass.
  - `vendor\bin\pint --dirty --format agent`: pass.
  - `npm.cmd run build`: pass.
  - `php artisan test`: pass, 71 tests / 825 assertions.
- Browser QA:
  - pages: `/tim-hieu-them`, `/tim-hieu-them?category=uu-dai-khach-hang`, `/dashboard`, `/admin/products`, `/admin/articles`.
  - viewports: 1366x768, 768x1024, 390x900.
  - article filter horizontal overflow: 0.
  - admin sidebar native scrollbar hidden and scroll behavior preserved.
  - broken images: 0.
  - console errors: 0.
  - page horizontal overflow: 0.
- Report: `.planning/baocao/phase-reports/phase-15-1-report.md`.
- Result: PASS.

## Phase 15

- Test time: 2026-06-14 22:07:49 +07:00.
- Scope:
  - Public homepage/navigation polish.
  - Article SEO content and public editorial cards.
  - Premium image-backed public forms.
  - Admin-configurable public form background.
  - Admin product public landing preview links.
- Added small visual settings system:
  - `site_settings` table.
  - `App\Models\SiteSetting`.
  - `App\Http\Controllers\Admin\SiteSettingController`.
  - admin route `GET|PUT /admin/site-settings`.
- Added shared public form shell:
  - `resources/views/components/public/form-shell.blade.php`.
- Updated forms:
  - `/booking` form.
  - `/accessories/{product:slug}/order` form.
- Updated article content:
  - `ArticleSeeder` now seeds 12 SEO-quality published articles across all 6 article categories.
  - Browser QA title/slug patterns are drafted without deleting records.
  - Article cards/detail use editorial image fallback.
- Product preview:
  - admin product edit has `Xem trang public`.
  - admin product index has `Public` action.
- Commands:
  - focused Phase 15 tests: pass, 6 tests / 49 assertions.
  - `php artisan migrate`: pass.
  - `php artisan db:seed --class=ArticleSeeder`: pass.
  - `php artisan config:clear`: pass.
  - `php artisan view:clear`: pass.
  - `php artisan view:cache`: pass.
  - `vendor\bin\pint --dirty --format agent`: pass.
  - `npm.cmd run build`: pass.
  - `php artisan test`: pass, 71 tests / 825 assertions.
- Route checks:
  - `php artisan route:list --path=admin -v`: pass, includes `/admin/site-settings`.
  - `php artisan route:list --path=tim-hieu-them -v`: pass.
  - `php artisan route:list --path=catalog -v`: pass.
- Browser QA:
  - desktop public/admin pages checked.
  - mobile 390x900 checked.
  - tablet 768x1024 checked.
  - broken images: 0 after lazy-load settle.
  - console errors: 0.
  - horizontal overflow: 0.
  - dynamic form background switching verified with temporary DB setting then reset.
- Report: `.planning/baocao/phase-reports/phase-15-report.md`.
- Result: PASS.

## Phase 14 post-phase regression QA

- Test time: 2026-06-14 21:05:33 +07:00.
- Scope:
  - Admin UI modernization.
  - Article CMS in admin.
  - Public "Tim hieu them".
  - Old product/accessory/compare/order regression.
- Initial state:
  - workspace: `C:\Users\thaib\du_an_code\bmw_laravel`.
  - branch: `master`.
  - remote: `https://github.com/qthai280902/bmw_laravel.git`.
  - working tree: clean before planning report update.
- Commands:
  - `php artisan config:clear`: pass.
  - `php artisan view:clear`: pass.
  - `php artisan view:cache`: pass.
  - `vendor\bin\pint --test`: pass.
  - `npm.cmd run build`: pass.
  - `php artisan test`: pass, 65 tests / 776 assertions.
- Route checks:
  - `php artisan route:list --path=admin -v`: pass.
  - `php artisan route:list --path=tim-hieu-them -v`: pass.
- Browser QA:
  - admin pages checked: dashboard/products/categories/appointments/accessory-orders/articles.
  - custom delete modal checked on products/categories/articles; cancel did not delete.
  - public article pages checked.
  - old product detail/accessory/compare flows checked.
  - responsive viewports checked: 390x900, 768x900, 1366x768.
  - visible broken images: 0.
  - console errors: 0.
  - horizontal overflow: 0.
- Security QA:
  - guest `/admin/articles`: 302 to login.
  - non-admin `/admin/articles`: 403.
  - admin `/admin/articles`: 200.
  - draft direct public article URL: 404.
- QA data:
  - created article `#3`, slug `uu-dai-mua-he-bmw-2026`, status `published`.
  - created article `#4`, slug `bai-nhap-noi-bo-showroom`, status `draft`.
  - updated QA accessory order `#3` admin notes, status remains `completed`.
  - pre-existing QA articles `#1` and `#2` remain.
- Note:
  - Browser input API could not type/fill due plugin clipboard runtime issue, so article create/update used authenticated admin HTTP form posts and was verified in Browser/DB.
- Report: `.planning/baocao/phase-reports/phase-14-regression-test-report.md`.
- Result: PASS CO GHI CHU.

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
