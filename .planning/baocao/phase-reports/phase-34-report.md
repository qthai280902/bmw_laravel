# Phase 34 Report — Nâng Cấp Kiến Trúc Eloquent ORM, Truy Vết Laravel Context, Gemini 3.1 Flash-Lite + Fallback & Laravel Prompts CLI

- **Phase:** 34
- **Completed at:** 2026-11-22 (`Asia/Ho_Chi_Minh`)
- **Status:** Completed (100% tests pass — `212 passed, 3451 assertions` in `49.18s`)

## 1. Scope Delivered
1. **Gemini 3.1 Flash-Lite Default + Automatic Model Fallback (`config/showroom_ai.php`, `config/ai.php`, `config/services.php`, `GeminiKeyPool`, `GeminiAgentRunner`, `ShowroomAssistantService`):**
   - Set primary model to `gemini-3.1-flash-lite`, secondary fallback model to `gemini-3.5-flash-lite`, default HTTP timeout to `20s`, and key cooldown to `60s`.
   - Enabled automatic fallback model pass in `GeminiAgentRunner` and `ShowroomAssistantService` when primary model hits quota exhaustion / `429` / `503` / timeouts, with `AQ....` API key delivery via `x-goog-api-key` to `models/{model}:generateContent`.
2. **End-to-End Request & Job Tracing via `Laravel Context` (`AttachRequestContext`, `InteractionTimelineRecorder`):**
   - Added `AttachRequestContext` middleware tracking `trace_id`, `url`, `actor_id`, `actor_role`, `crm_reference`, `ai_visitor_hash`, plus `Context::addHidden()` for internal codes (`crm_internal_customer_code`, `crm_voucher_code`, `ai_visitor_raw_id`) and propagating `X-Trace-Id` response headers and queued job payloads.
   - Propagated `trace_id` into `InteractionTimelineRecorder::makeEvent()` and traced AI calls with `Context::push('ai_steps', ...)`.
3. **Eloquent ORM Hardening & Strictness (`AppServiceProvider`, Models, Collections, Factories, Migration):**
   - Enabled `Model::preventLazyLoading(! $this->app->isProduction())`, `Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction())`, and `Relation::enforceMorphMap([...])` alongside `2026_11_22_000001_backfill_notifications_morph_types.php`.
   - Fixed a live lazy-loading issue caught in `UserController::performance()` (added missing `'user:id,name,email'` eager load) and cleaned legacy test fixtures failing strictness/morph-map checks (`AdminEmailsConfigurationTest`, `Phase25_2`, `Phase26`, `Phase27`, `Phase28_5`, `Phase29`, `Phase30`, `Phase31`, `Phase32`).
   - Added `$hidden` on `Appointment` & `AccessoryOrder`, `AsArrayObject::class` cast on `Appointment` & `AiChatMessage` (+ `InteractionTimelineRecorder::normalizeMetaArray()`), `->ofMany(['is_primary' => 'max', 'id' => 'min'])` on `Product::primaryImage()`, `->withDefault(['name' => 'Chưa phân công'])` on `assignedSales()`, and `protected $touches = ['session'];` on `AiChatMessage`.
   - Created `AppointmentCollection` and `AccessoryOrderCollection` (`#[CollectedBy(...)]`) reused across `AccountingController` and `UserController::performance()`, plus `AccessoryOrderFactory` and expanded factory states (`topSuperAdmin`, `hotLead`, `assignedTo`, `rescheduledByGuest`, `outOfStock`, `completed`, `cancelled`).
4. **Operations CLI With `Laravel Prompts` & `AboutCommand`:**
   - Added `showroom:create-staff` (`CreateStaffCommand` using `form()` / `text()` / `select()` / `password()` / `table()` / `callout()`) and `showroom:cache-clear` (`ShowroomCacheClearCommand` hooked into `$this->optimizes(clear: 'showroom:cache-clear')`).
   - Upgraded `ResetCrmDataCommand` (`confirm()`, `multiselect()`, `table()`, `callout()` while keeping `--force` non-interactive) and `CleanAndSyncShowroomDataCommand`, and registered `AboutCommand::add('BMW Showroom', ...)` in `AppServiceProvider`.

## 2. Verification Results
- `php artisan migrate --force`: `2026_11_22_000001_backfill_notifications_morph_types` — `DONE` (`85.16ms`)
- `vendor\bin\pint --dirty --format agent`: `DONE`
- `php artisan optimize:clear`: `DONE` (including custom `app` clear via `showroom:cache-clear`)
- `php artisan about`: Verified `BMW Showroom` section (`gemini-3.1-flash-lite`, `gemini-3.5-flash-lite`, `20s`)
- `php artisan test --compact tests/Feature/Phase34EloquentContextAiFallbackAndPromptsTest.php`: **`4 passed (95 assertions)`** in **`1.68s`**
- `php artisan test --compact`: **`212 passed (3451 assertions)`** in **`49.18s`** (0 failures, 0 skipped; cold-cache `<= 8 SQL queries` preserved on CRM tables)
