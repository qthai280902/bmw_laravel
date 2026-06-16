# Phase 16.2 Report - AI Assistant UX Overhaul + Product Context Intelligence Fix

## 1. Status

PASS CO GHI CHU.

## 2. Audit Summary

- Widget root:
  - `resources/views/components/public/ai-assistant-widget.blade.php`.
  - `resources/js/app.js`.
  - `resources/css/app.css`.
- Root cause UX:
  - The previous widget depended on draggable fixed positioning and persisted old coordinates in localStorage.
  - The greeting was inserted as an assistant message, so a fresh chat could look like history already existed.
  - Assistant answer rendering was functional but too nested and action links were not promoted as clear CTA chips.
- Root cause context:
  - Product context was limited too narrowly and sorted by latest products.
  - BMW S1000RR existed as an active `motorbike`, but could be excluded from the AI public context.
  - Matching used full normalized aliases such as `bmw330isedan`, so short model-code questions like `330i` were not reliably matched.
- Chosen direction:
  - Fixed bottom-right support/ecommerce assistant pattern.
  - Intro card for greeting and quick replies.
  - Safe text rendering plus internal action chips.
  - Expanded public product context and normalized product-code aliases.

## 3. UX Changes

- New widget flow:
  - Compact launcher by default.
  - Fixed panel, no drag handle.
  - Side-tab hidden mode remains available.
  - Fresh chat shows intro card, not a stored assistant greeting.
  - `localStorage` key upgraded to `bmw_ai_assistant_state_v4`.
  - Legacy state keys are cleared.
- Message rendering:
  - Keeps line breaks and list-style blocks readable.
  - Extracts internal links as action chips.
  - Link labels are mapped to actions such as detail, quote, test drive, compare, accessory order and Motorrad catalog.
  - No `x-html`.
- Mobile:
  - Panel width adjusted to stay fully inside 390px viewport.
  - Chat body scroll remains internal when content is long.

## 4. Product Context Fix

- Context now includes active public:
  - cars.
  - BMW Motorrad products.
  - accessories.
  - published articles.
- Product context includes:
  - name.
  - slug.
  - type.
  - category.
  - product line.
  - public URL.
  - normalized `search_aliases`.
- Alias matching now covers model-code queries:
  - `330i`.
  - `530i`.
  - `x5`.
  - `s1000rr`.
  - `BMW S 1000 RR`.
- Tests confirm S1000RR variants return the BMW Motorrad product suggestion without real AI calls.

## 5. Security / Safety

- No `.env` or secret values were read or printed.
- No API key is hard-coded.
- Automated tests use Laravel AI fakes.
- Prompt context still excludes:
  - appointments.
  - accessory orders.
  - users.
  - customer PII.
  - admin/internal notes.
  - draft articles.
- Provider exceptions log only safe error class/type information.

## 6. Files Changed

- `.env.example`.
- `config/showroom_ai.php`.
- `app/Ai/Agents/ShowroomAssistant.php`.
- `app/Services/Ai/ShowroomAssistantService.php`.
- `resources/js/app.js`.
- `resources/css/app.css`.
- `resources/views/components/public/ai-assistant-widget.blade.php`.
- `resources/views/components/public/ai-assistant-avatar.blade.php`.
- `tests/Feature/AiAssistantWidgetTest.php`.
- `tests/Feature/Phase16_2AiAssistantContextTest.php`.
- Planning files under `.planning/`.

## 7. Commands Run

- `php artisan config:clear`: pass.
- `php artisan cache:clear`: pass.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `vendor\bin\pint --dirty --format agent`: pass.
- `npm.cmd run build`: pass.
- `php artisan test`: pass, 89 tests / 1048 assertions.
- `php artisan route:list --path=ai -v`: pass, `POST /ai/showroom-assistant` keeps `web` and `throttle:12,1`.

## 8. Browser QA

- Pages checked:
  - `/`.
  - `/catalog`.
  - `/catalog?type=motorbike`.
  - `/catalog/bmw-330i-sedan`.
  - `/catalog/bmw-s1000rr`.
  - `/accessories`.
  - `/tim-hieu-them`.
  - `/booking?type=consult`.
  - `/compare?ids=1,2`.
- Viewports checked:
  - 1366x768.
  - 768x1024.
  - 390x900.
- Results:
  - launcher/panel render: pass.
  - intro card fresh state: pass.
  - `messageCount = 0` on storage-cleared fresh state: pass.
  - S1000RR live answer: pass.
  - sedan live answer: pass.
  - internal action chips clickable: pass.
  - chip navigation to `/catalog/bmw-s1000rr`: pass.
  - mobile panel within viewport: pass.
  - broken images: 0.
  - console errors: 0.
  - horizontal overflow: 0.
  - `x-html`: 0.
  - draggable nodes: 0.

## 9. Playwright CLI QA

- Opened `http://127.0.0.1:8000/`.
- Cleared localStorage.
- Desktop 1366x768:
  - launcher click opened panel.
  - intro card visible.
  - `messageCount = 0`.
  - no draggable nodes.
  - no `x-html`.
  - broken images: 0.
  - horizontal overflow: false.
- Mobile 390x900:
  - panel rect stayed inside viewport.
  - broken images: 0.
  - horizontal overflow: false.
- Console error count: 0.
- Temporary `.playwright-cli` artifact was removed.

## 10. Known Issues

- During live QA, the SUV prompt returned the safe fallback because Gemini responded with `RateLimitedException`.
- This was an external provider/rate limit condition after successful S1000RR and sedan live answers.
- Automated fake-AI tests cover the expected context and suggestion behavior without calling Gemini.

## 11. Conclusion

- Phase 16.2 can be closed as PASS CO GHI CHU.
- No route, endpoint or business logic was changed.
- No additional patch is required for the local code based on current automated and browser QA.
