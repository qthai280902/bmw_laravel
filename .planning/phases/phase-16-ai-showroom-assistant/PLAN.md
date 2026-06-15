# Phase 16 Plan - AI Showroom Assistant

## Goal

Add a public BMW Showroom AI assistant using Laravel AI SDK while keeping product, article, booking, accessory order and admin logic unchanged.

## Scope

- Fix PHP 8.5 `PDO::MYSQL_ATTR_SSL_CA` deprecation before continuing AI work.
- Install Laravel AI SDK.
- Configure Gemini as the default assistant provider through env/config only.
- Add a public POST endpoint for the assistant.
- Build a public floating assistant widget with a custom BMW-style robot/car avatar.
- Keep private data out of AI context.
- Add tests with Laravel AI fakes; never call a real AI provider in tests.
- Run browser QA across public pages and responsive viewports.

## Non-Goals

- Do not hard-code `GEMINI_API_KEY`.
- Do not send appointment, accessory order, user, admin notes or customer PII to the prompt.
- Do not change existing product, article, booking, accessory order or admin routes.
- Do not call the real Gemini API during automated tests.

## Implementation Direction

- Use `laravel/ai`.
- Create `App\Ai\Agents\ShowroomAssistant`.
- Create `App\Services\Ai\ShowroomAssistantService` to assemble public context and handle fallback behavior.
- Create `App\Http\Controllers\Ai\ShowroomAssistantController`.
- Add `POST /ai/showroom-assistant` with `web` and rate limit middleware.
- Add `<x-public.ai-assistant-widget />` to the public app layout only.
- Use Alpine for widget state, drag, local fallback and suggestion handling.

## Verification Plan

- `composer validate`.
- `php artisan config:clear`.
- `php artisan view:clear`.
- `php artisan view:cache`.
- `vendor\bin\pint --dirty --format agent`.
- `npm.cmd run build`.
- `php artisan test`.
- Browser QA at 390x900, 768x1024 and 1366x768.
- Playwright screenshot cross-check for mobile homepage.
