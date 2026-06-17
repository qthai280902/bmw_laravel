# Phase 17 Report

## 1. Status

PASS CO GHI CHU.

## 2. Audit Summary

- AI request previously accepted only `message`; it did not persist visitor identity or conversation history.
- Public widget had chat UI state but no persistent CRM visitor id.
- Appointment and accessory order records had customer fields but no AI visitor link.
- Admin UI had a modern dark shell, but no AI conversation area and no AI CRM signal on dashboard.
- Customer linking should prefer visitor id; IP is only a recent fallback and must not mass-link all sessions on the same IP.

## 3. Data Model

- Added `ai_chat_sessions`.
- Added `ai_chat_messages`.
- Added nullable indexed `ai_visitor_id` to `appointments`.
- Added nullable indexed `ai_visitor_id` to `accessory_orders`.
- IP metadata is preserved; admin display masks IP and visitor id.

## 4. AI Conversation Logging

- Added `App\Services\Ai\AiConversationTracker`.
- `POST /ai/showroom-assistant` still returns the existing assistant payload.
- Endpoint now accepts optional `visitor_id`, `page_url` and `referrer`.
- Each request logs one user message and one assistant message.
- Logging failures are caught and do not break the AI response.
- Secret-like values are redacted from stored content; email/phone-like values are redacted in previews.

## 5. Customer Linking

- Public widget creates `bmw_ai_visitor_id`.
- Widget sends visitor id with AI requests.
- Widget injects hidden `ai_visitor_id` into booking and accessory order forms.
- Appointment submit links the matching AI session by visitor id.
- Accessory order submit links the matching AI session by visitor id.
- If no visitor id exists, IP fallback links only one recent unlinked session and stores `ip_recent`.

## 6. Admin AI Conversations

- Routes:
  - `GET /admin/ai-conversations`.
  - `GET /admin/ai-conversations/{session}`.
  - `PATCH /admin/ai-conversations/{session}/status`.
- Middleware confirmed:
  - `web`.
  - `auth`.
  - `admin`.
  - `verified`.
- List page supports search/filter/status/range and shows display label, preview, intent, linked source and status.
- Detail page shows visitor profile, masked visitor id, masked IP, linked source, status form and chat timeline.

## 7. Admin UI Overhaul

- Updated shared admin components:
  - card.
  - page header.
  - sidebar link.
- Admin shell now has stronger CRM dark visual treatment and clearer active states.
- Sidebar includes `Lịch sử trợ lý AI`.
- Dashboard includes AI Assistant CRM metrics and latest AI leads.
- No external admin template/code copied.

## 8. Files Changed

- `routes/web.php`.
- `resources/js/app.js`.
- `app/Http/Controllers/Ai/ShowroomAssistantController.php`.
- `app/Http/Controllers/Admin/AiConversationController.php`.
- `app/Http/Controllers/Admin/DashboardController.php`.
- `app/Http/Controllers/Client/AppointmentController.php`.
- `app/Http/Controllers/AccessoryOrderController.php`.
- `app/Http/Requests/StoreAppointmentRequest.php`.
- `app/Http/Requests/StoreAccessoryOrderRequest.php`.
- `app/Models/AiChatSession.php`.
- `app/Models/AiChatMessage.php`.
- `app/Models/Appointment.php`.
- `app/Models/AccessoryOrder.php`.
- `app/Services/Ai/AiConversationTracker.php`.
- `database/migrations/2026_06_17_080643_create_ai_chat_sessions_table.php`.
- `database/migrations/2026_06_17_080644_create_ai_chat_messages_table.php`.
- `database/migrations/2026_06_17_080651_add_ai_visitor_id_to_customer_touchpoints.php`.
- `resources/views/admin/ai-conversations/index.blade.php`.
- `resources/views/admin/ai-conversations/show.blade.php`.
- `resources/views/admin/dashboard.blade.php`.
- `resources/views/components/admin-layout.blade.php`.
- `resources/views/components/admin/card.blade.php`.
- `resources/views/components/admin/page-header.blade.php`.
- `resources/views/components/admin/sidebar-link.blade.php`.
- `tests/Feature/Phase17AiConversationTest.php`.
- `tests/Feature/AiShowroomAssistantTest.php`.

## 9. Tests

- Focused Phase 17 tests: PASS, 8 tests / 43 assertions.
- Related AI/admin regression tests: PASS, 34 tests / 310 assertions.
- Full suite: PASS, 108 tests / 1128 assertions.

## 10. Commands

- `php artisan migrate --no-interaction`: PASS.
- `php artisan config:clear`: PASS.
- `php artisan cache:clear`: PASS.
- `php artisan view:clear`: PASS.
- `php artisan view:cache`: PASS.
- `vendor\bin\pint --dirty --format agent`: PASS, formatted `AccessoryOrderController.php`.
- `npm.cmd run build`: PASS.
- `php artisan test`: PASS, 108 tests / 1128 assertions.
- `php artisan route:list --path=admin/ai -v`: PASS.

## 11. Browser QA

- In-app Browser admin pages checked:
  - `/dashboard`.
  - `/admin/products`.
  - `/admin/appointments`.
  - `/admin/accessory-orders`.
  - `/admin/articles`.
  - `/admin/ai-conversations`.
  - `/admin/ai-conversations/1`.
- Viewports:
  - 1366x768.
  - 768x1024.
  - 390x900.
- Results:
  - broken images: 0.
  - console errors: 0.
  - horizontal overflow: false.
  - public AI widget absent from admin pages.
  - visible secret pattern: false.
  - AI conversation list shows `Anh Thai Phase 17`.
  - AI conversation detail shows linked `Lịch hẹn #1`, masked visitor and masked IP.
- Public-to-admin linking QA:
  - asked public AI widget about BMW 330i.
  - live AI answer returned normally.
  - submitted booking with same visitor id.
  - DB linked session `#1` to appointment `#1` with `visitor_id` confidence.
- Playwright CLI:
  - mobile 390x900 public homepage smoke.
  - widget launcher visible.
  - broken images: 0.
  - horizontal overflow: false.
  - visible secret pattern: false.

## 12. Security

- `.env` was not opened or printed.
- API keys were not hard-coded.
- AI provider keys are not stored in chat tables.
- Logs only store safe metadata.
- Admin AI routes require auth/admin/verified.
- AI prompt context still excludes private appointment/order/user data.

## 13. Planning Updated

- `.planning/phases/phase-17-admin-ai-conversations-ui-overhaul/PLAN.md`.
- `.planning/phases/phase-17-admin-ai-conversations-ui-overhaul/CHECKLIST.md`.
- `.planning/phases/phase-17-admin-ai-conversations-ui-overhaul/SUMMARY.md`.
- `.planning/baocao/phase-reports/phase-17-report.md`.
- `.planning/kientruc/AI-ASSISTANT.md`.
- `.planning/kientruc/ADMIN-CRM.md`.
- `.planning/kientruc/ADMIN-UI.md`.
- `.planning/kientruc/SECURITY.md`.
- `.planning/kientruc/TESTING.md`.
- `.planning/STATE.md`.

## 14. Known Issues

- Browser QA created local dev AI session `#1` and appointment `#1`.
- Existing older PHP artisan serve process on another port was left untouched.

## 15. Conclusion

Phase 17 can be closed.
