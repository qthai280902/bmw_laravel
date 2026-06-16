# Phase 16.2 Summary - AI Assistant UX + Product Context Intelligence

## Status

PASS CO GHI CHU.

## What Changed

- Rebuilt the public AI assistant widget as a fixed launcher, panel and side-tab experience.
- Removed drag-position dependency and legacy position state.
- Moved greeting text into an intro card instead of storing it as an assistant message.
- Added state key `bmw_ai_assistant_state_v4` and clears legacy v2/v3/position keys.
- Rendered assistant answers with safe text blocks, readable lists and internal action chips.
- Kept escaped rendering with `x-text`; no `x-html`.
- Expanded public AI context to active cars, BMW Motorrad, accessories and published articles.
- Added product aliases for model-code queries such as `330i`, `530i`, `S1000RR` and `BMW S 1000 RR`.
- Updated assistant wording so the showroom is not described as cars-only.
- Tightened mobile panel width so it stays fully inside the 390px viewport.

## Verification

- `php artisan config:clear`: pass.
- `php artisan cache:clear`: pass.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `vendor\bin\pint --dirty --format agent`: pass.
- `npm.cmd run build`: pass.
- `php artisan test`: pass, 89 tests / 1048 assertions.
- `php artisan route:list --path=ai -v`: pass.
- Browser QA:
  - pages: `/`, `/catalog`, `/catalog?type=motorbike`, `/catalog/bmw-330i-sedan`, `/catalog/bmw-s1000rr`, `/accessories`, `/tim-hieu-them`, `/booking?type=consult`, `/compare?ids=1,2`.
  - viewports: 1366x768, 768x1024, 390x900.
  - broken images: 0.
  - console errors: 0.
  - horizontal overflow: 0.
- Playwright CLI:
  - storage-cleared desktop confirmed fresh intro card and `messageCount = 0`.
  - mobile 390x900 confirmed panel within viewport.

## Notes

- Live widget QA returned valid Gemini answers for S1000RR and sedan prompts.
- The SUV prompt hit a provider `RateLimitedException`, so the widget returned the safe fallback for that request. This is an external provider/rate condition, not a config, render or context regression.
- Automated tests use Laravel AI fakes and do not call the real provider.
