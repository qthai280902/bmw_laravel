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
