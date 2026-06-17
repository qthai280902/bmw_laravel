# Phase 17 Summary

Phase 17 adds a CRM layer for the BMW AI Assistant.

New data model:

- `ai_chat_sessions`
- `ai_chat_messages`
- `appointments.ai_visitor_id`
- `accessory_orders.ai_visitor_id`

New admin module:

- `GET /admin/ai-conversations`
- `GET /admin/ai-conversations/{session}`
- `PATCH /admin/ai-conversations/{session}/status`

Key behavior:

- Public widget creates `bmw_ai_visitor_id` in localStorage.
- AI endpoint receives visitor id, page URL and referrer.
- AI requests log one user message and one assistant message.
- Appointment/accessory order submit links the matching AI session.
- Visitor-id linking is authoritative.
- IP fallback links only one recent unlinked session and marks confidence as `ip_recent`.
- Admin displays customer name when known, while preserving IP metadata.

Security:

- Provider keys are not stored.
- Secret-like values in chat content are redacted before storage.
- Message previews redact email and phone values.
- Admin routes remain protected by auth/admin middleware, with AI routes also using verified middleware.
