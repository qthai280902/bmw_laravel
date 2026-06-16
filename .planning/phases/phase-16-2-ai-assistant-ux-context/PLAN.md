# Phase 16.2 Plan - AI Assistant UX + Product Context Intelligence

## Goal

Rebuild the AI assistant widget into a cleaner ecommerce/support chat experience and fix product context intelligence so BMW Motorrad products such as BMW S1000RR are always available to the assistant.

## Scope

- Audit current AI widget state, rendering, localStorage and fixed/drag behavior.
- Replace drag-positioned widget behavior with a stable fixed bottom-right launcher/panel/side-tab.
- Move greeting into a separate intro card instead of a stored assistant message.
- Use a new localStorage state version: `bmw_ai_assistant_state_v4`.
- Ignore and clear old v2/v3/position state.
- Render assistant messages as simple safe blocks plus action chips.
- Keep internal link sanitization and no `x-html`.
- Expand AI context to include active cars, BMW Motorrad, accessories and published articles.
- Add normalized aliases for queries such as `BMW S1000RR`, `S1000RR`, `BMW S 1000 RR`.
- Keep private data out of prompts and keep tests fake-only.

## Non-Goals

- No Phase 16.3.
- No real Gemini calls from automated tests.
- No API key or secret exposure.
- No endpoint or route change for `POST /ai/showroom-assistant`.
- No change to compare logic fixed in Phase 16.1.
- No booking/order/admin/CRM business logic changes.

## Verification Plan

- Focused AI widget/context tests.
- Required command suite:
  - `php artisan config:clear`.
  - `php artisan cache:clear`.
  - `php artisan view:clear`.
  - `php artisan view:cache`.
  - `vendor\bin\pint --dirty --format agent`.
  - `npm.cmd run build`.
  - `php artisan test`.
- Browser QA on desktop, mobile and tablet.
- Live AI QA through browser only when local provider config is available.
