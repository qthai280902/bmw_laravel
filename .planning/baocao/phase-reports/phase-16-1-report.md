# Phase 16.1 Report

## 1. Status

PASS.

## 2. UI Research Summary

- Chatwoot pattern: compact support launcher and persistent conversation UX. Community edition is documented as MIT.
- assistant-ui pattern: production AI chat structure, threads/actions and readable message surface. Site states MIT license.
- Flowbite pattern: Tailwind chat bubbles, clear message alignment and dark-mode friendly components. Flowbite is documented as MIT/open-source.
- Botpress webchat pattern: embeddable/customizable widget and contextual show/hide behavior.
- No source code or template was copied.

## 3. AI Widget UX

- Files changed:
  - `resources/js/app.js`.
  - `resources/css/app.css`.
  - `resources/views/components/public/ai-assistant-widget.blade.php`.
  - `resources/views/components/public/ai-assistant-avatar.blade.php`.
- Fresh default state:
  - compact launcher + greeting bubble.
  - no large panel open by default.
- Open state:
  - premium dark BMW/Zinc panel.
  - compact header with ready state, minimize and hide actions.
  - scrollable chat body.
- Hidden state:
  - side-tab on the right edge.
  - state persists through reload.
  - clicking side-tab reopens the panel.
- Chat history:
  - stored in `localStorage` key `bmw_ai_assistant_state_v2`.
  - limited to 24 recent messages.
  - common email/phone patterns are redacted before local storage.
  - history is not sent back to the AI endpoint.
- Markdown/link rendering:
  - token-based parser in `resources/js/app.js`.
  - supports paragraphs, bullets, basic bold and internal links.
  - rendered with `x-text`; no `x-html`.

## 4. AI Live QA

- Live widget calls were made through the browser.
- Questions sent:
  - `Tư vấn giúp tôi một mẫu BMW sedan đi làm hằng ngày.`
  - `So sánh BMW 330i và BMW 530i giúp tôi.`
  - `Có ưu đãi nào mới không?`
- Result:
  - response fallback visible: false.
  - readable lists: yes.
  - internal links detected: yes.
  - chat body internal scroll: yes.
  - clicked `Xem BMW 330i Sedan`, navigated to `/catalog/bmw-330i-sedan`.
  - chat history restored after navigation: yes.

## 5. Compare Bug

- Root cause:
  - compare IDs were passed raw from query string into `whereIn`.
  - duplicate/invalid ID handling was implicit.
  - product order followed database return order, not requested order.
  - max 4 compare limit existed in UI only, not server-side.
- Fix:
  - normalize IDs to positive integers.
  - remove duplicates.
  - ignore invalid IDs.
  - keep car/motorbike only.
  - ignore accessories.
  - preserve requested order.
  - enforce max 4 vehicles after filtering vehicle products.
- File changed:
  - `app/Services/VehicleSearchService.php`.
- Cases covered:
  - `/compare?ids=1,2`.
  - `/compare?ids=1,16`.
  - duplicate IDs.
  - invalid IDs.
  - empty compare.
  - car + motorbike.
  - more than 4 valid vehicle candidates.

## 6. Commands

- `php artisan config:clear`: pass.
- `php artisan cache:clear`: pass.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `vendor\bin\pint --dirty --format agent`: pass.
- `npm.cmd run build`: pass.
- `php artisan test`: pass, 86 tests / 1006 assertions.

## 7. Browser QA

- Desktop 1366x768:
  - `/`.
  - `/catalog`.
  - `/catalog/bmw-330i-sedan`.
  - `/accessories`.
  - `/tim-hieu-them`.
  - `/booking?type=consult`.
  - `/compare?ids=1,2`.
- Mobile 390x900:
  - `/`.
  - `/compare?ids=1,2`.
  - AI launcher.
  - AI panel.
  - AI side-tab hidden state.
- Tablet 768x1024:
  - `/`.
  - `/compare?ids=1,2`.
- Results:
  - broken images: 0.
  - console errors: 0.
  - page-level horizontal overflow: 0.
  - compare table internal scroll exists on narrow viewports by design.

## 8. Security

- `.env` and secrets were not read or printed.
- No API key was hard-coded.
- Automated tests do not call the real AI provider.
- AI prompt context remains public-only.
- Appointment, accessory order, user, admin note and private customer data remain excluded.

## 9. Planning Files Updated

- `.planning/phases/phase-16-1-ai-widget-ux-compare-fix/PLAN.md`.
- `.planning/phases/phase-16-1-ai-widget-ux-compare-fix/CHECKLIST.md`.
- `.planning/phases/phase-16-1-ai-widget-ux-compare-fix/SUMMARY.md`.
- `.planning/baocao/phase-reports/phase-16-1-report.md`.
- `.planning/STATE.md`.
- `.planning/kientruc/AI-ASSISTANT.md`.
- `.planning/kientruc/TESTING.md`.
- `.planning/kientruc/ROUTING.md`.
- `.planning/bug/fixed/phase-16-1-compare-id-normalization.md`.
- `.planning/bug/BUG-INDEX.md`.

## 10. Conclusion

Phase 16.1 can be closed as PASS.
