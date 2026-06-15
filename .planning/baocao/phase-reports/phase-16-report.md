# Phase 16 Report

## 1. Status

PASS CO GHI CHU.

## 2. PHP 8.5 Deprecation Fix

- Root cause: `config/database.php` referenced `PDO::MYSQL_ATTR_SSL_CA` directly for MySQL and MariaDB options.
- Fix: use `Pdo\Mysql::ATTR_SSL_CA` when available, with fallback to `PDO::MYSQL_ATTR_SSL_CA` for older PHP versions.
- DB connection logic: unchanged.
- Result: `php artisan test` no longer reports the PHP 8.5 deprecation.

## 3. Laravel AI SDK Integration

- Package installed: `laravel/ai` v0.7.2.
- Published config/stubs through `Laravel\Ai\AiServiceProvider`.
- Migrated package conversation tables.
- Added Gemini as env-driven assistant provider.
- No `GEMINI_API_KEY` value was committed or hard-coded.

## 4. AI Assistant Backend

- Agent: `App\Ai\Agents\ShowroomAssistant`.
- Service: `App\Services\Ai\ShowroomAssistantService`.
- Controller: `App\Http\Controllers\Ai\ShowroomAssistantController`.
- Route: `POST /ai/showroom-assistant`, name `ai.showroom-assistant.ask`.
- Middleware: `web`, `throttle:12,1`.
- Prompt context only includes public active products and published articles.
- Private appointments, accessory orders, users, admin notes and customer PII are excluded.

## 5. Widget UI/UX

- Added `<x-public.ai-assistant-widget />` to the public layout.
- Added custom BMW-style robot/car SVG avatar.
- Desktop opens with the assistant panel ready.
- Mobile starts with a compact launcher/greeting to avoid covering hero content.
- Widget supports minimize/open, prompts, input, fallback response and drag movement.
- Animation respects reduced-motion CSS.

## 6. Logic Preservation

- Product detail routes preserved.
- Article routes and filtering preserved.
- Booking and accessory order flows preserved.
- Admin layout does not render the public widget.
- No business logic was changed outside the AI assistant integration.

## 7. Files Changed

- `composer.json`
- `composer.lock`
- `.env.example`
- `config/database.php`
- `config/ai.php`
- `config/showroom_ai.php`
- `database/migrations/2026_06_15_152329_create_agent_conversations_table.php`
- `app/Ai/Agents/ShowroomAssistant.php`
- `app/Http/Controllers/Ai/ShowroomAssistantController.php`
- `app/Services/Ai/ShowroomAssistantService.php`
- `resources/views/components/public/ai-assistant-avatar.blade.php`
- `resources/views/components/public/ai-assistant-widget.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/js/app.js`
- `resources/css/app.css`
- `routes/web.php`
- `tests/Feature/AiShowroomAssistantTest.php`
- `tests/Feature/AiAssistantFallbackTest.php`
- `tests/Feature/AiAssistantWidgetTest.php`
- `.planning/phases/phase-16-ai-showroom-assistant/*`
- `.planning/baocao/phase-reports/phase-16-report.md`

## 8. Commands Run

- `composer validate`: pass.
- `php artisan config:clear`: pass.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `vendor\bin\pint --dirty --format agent`: pass.
- `npm.cmd run build`: pass.
- `php artisan test`: pass, 83 tests / 975 assertions.
- `php artisan route:list --path=ai -v`: pass.

## 9. Browser QA

- Pages checked:
  - `/`
  - `/catalog`
  - `/catalog/bmw-330i-sedan`
  - `/accessories`
  - `/tim-hieu-them`
  - `/booking?type=consult`
- Viewports checked:
  - 390x900
  - 768x1024
  - 1366x768
- Page/viewport sweep: 18 combinations passed.
- Widget present: pass.
- Ready/greeting text present: pass.
- Desktop drag: pass.
- Missing-key fallback response: pass.
- Broken images: 0.
- Console errors: 0.
- Horizontal overflow: 0.
- Playwright screenshot cross-check: pass; temporary screenshot artifact was cleaned after visual QA.

## 10. Security and Data Notes

- Tests use Laravel AI fakes and do not call real providers.
- Missing `GEMINI_API_KEY` returns a friendly fallback.
- Provider exception returns a friendly fallback without exposing stack traces.
- Endpoint validates message length and is rate limited.

## 11. Known Issues

- Live Gemini response was not tested because `.env` currently has no `GEMINI_API_KEY`.
- This is expected and safer for local/CI. Add the key in environment configuration when ready to test live AI.

## 12. Conclusion

- Phase 16 can be closed as PASS CO GHI CHU.
- No patch is currently needed.
