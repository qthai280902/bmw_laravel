# Security Notes

## Environment Secrets

- `.env` must stay local and must not be committed.
- `.env.example` must contain placeholders only.
- API keys, `APP_KEY`, database passwords and provider tokens must not be printed in reports, logs or terminal output.

## AI Provider Keys

- Gemini primary key is configured through `GEMINI_API_KEY`.
- Additional Gemini keys are configured through `GEMINI_API_KEYS`.
- Source code never hard-codes Gemini keys.
- Runtime logs must not include raw keys.
- Phase 16.3 logs only safe metadata:
  - provider.
  - model.
  - key index.
  - exception class.
  - reason.
  - attempts/configured key count.
- Cooldown cache keys use a hash/fingerprint, not raw key text.

## AI Prompt Boundary

- Assistant prompt context may include active public products and published public articles.
- Assistant prompt context must not include:
  - appointments.
  - accessory orders.
  - users.
  - customer names, phone numbers, emails or addresses.
  - admin/internal notes.
  - draft articles.

## Automated Tests

- Automated AI tests use Laravel AI fakes.
- Tests must not call Gemini live.
- Tests may use fake placeholder key strings only.

## Phase 17 AI Conversation CRM

- AI conversation storage must not store provider keys.
- AI message content is sanitized for obvious API key and secret assignment patterns before storage.
- Message previews redact email and phone-like values.
- Admin UI masks visitor ids and IP labels.
- Original IP metadata is preserved for internal CRM audit, but IP is not treated as a unique customer identity.
- Visitor-id linking is preferred for customer conversion.
- IP fallback links only one recent unlinked session and marks confidence as `ip_recent`.
- Admin AI conversation routes are protected by `auth`, `admin` and `verified` middleware.
