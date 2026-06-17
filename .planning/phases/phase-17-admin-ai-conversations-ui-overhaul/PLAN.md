# Phase 17 Plan - Admin AI Conversation History + Customer Linking + Admin UI Overhaul

## Scope

- Add AI conversation history tracking for the public showroom assistant.
- Persist visitor identity, IP metadata and chat messages without storing provider keys.
- Link AI sessions to appointments and accessory orders when a public form includes the same visitor id.
- Add admin list/detail/status workflow for AI conversations.
- Improve admin shell/sidebar/cards/dashboard with a more premium CRM feel.

## Implementation Direction

- Use new CRM-specific tables `ai_chat_sessions` and `ai_chat_messages`.
- Keep Laravel AI package conversation tables untouched.
- Keep `POST /ai/showroom-assistant` response contract compatible.
- Generate `bmw_ai_visitor_id` in localStorage and submit it with AI requests and public forms.
- Prefer visitor-id linking; use IP recent fallback only for one most recent unlinked session.
- Do not change Gemini provider/key logic.

## Verification

- Focused Phase 17 tests.
- Related AI/admin regression tests.
- Required full commands and browser QA.
