# Phase 16.1 Plan - AI Widget UX + Compare Fix

## Goal

Polish the public AI assistant into a compact premium BMW-style widget, preserve chat history across navigation, add a side-tab hidden mode, and harden product compare logic.

## Scope

- Audit AI widget frontend rendering and state.
- Research legal/open-source chatbot UX patterns without copying source code.
- Rework the launcher, panel, side-tab hidden mode, quick actions and typing state.
- Persist recent chat history and widget mode in browser storage.
- Keep AI endpoint and service behavior unchanged unless required.
- Audit and fix compare ID normalization, duplicate handling, order preservation, accessory filtering and max vehicle count.
- Add regression tests for widget assets and compare behavior.
- Run required Laravel, Pint, Vite and browser QA checks.

## Non-Goals

- Do not hard-code or expose `GEMINI_API_KEY`.
- Do not call the live AI provider in automated tests.
- Do not store conversation history on the server.
- Do not change booking, accessory order, CRM or admin business logic.
- Do not commit or push without explicit approval.

## Implementation Direction

- Keep Alpine + Blade as the widget framework.
- Use `localStorage` for bounded client-side widget state and recent non-server chat history.
- Redact common contact data before storing restored history.
- Keep Markdown rendering token-based with escaped `x-text`; do not use `x-html`.
- Normalize compare IDs in `VehicleSearchService` before querying products.
- Keep compare vehicle-only: car and motorbike are allowed; accessory is ignored.

## Verification Plan

- `php artisan config:clear`.
- `php artisan cache:clear`.
- `php artisan view:clear`.
- `php artisan view:cache`.
- `vendor\bin\pint --dirty --format agent`.
- `npm.cmd run build`.
- `php artisan test`.
- Browser QA at 390x900, 768x1024 and 1366x768.
- Live AI widget QA only through the local browser, not automated tests.
