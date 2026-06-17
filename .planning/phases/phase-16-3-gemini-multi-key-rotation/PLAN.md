# Phase 16.3 Plan - Gemini Multi-Key Rotation + Rate Limit Failover

## Goal

Improve AI Showroom Assistant reliability by supporting multiple Gemini API keys with safe rotation, cooldown and failover when a key is temporarily rate limited.

## Scope

- Keep `POST /ai/showroom-assistant` unchanged.
- Keep Phase 16.2 widget behavior unchanged.
- Preserve compare/product context logic.
- Support existing `GEMINI_API_KEY`.
- Add `GEMINI_API_KEYS` for comma/newline/pipe separated key lists.
- Normalize, trim, dedupe and ignore empty key values.
- Avoid any parser limit on number of configured keys.
- Add round-robin key selection through cache.
- Mark rate-limited keys in cooldown through cache.
- Return a separate friendly rate-limit fallback when all keys are unavailable.
- Keep logs safe: key index, exception class and reason only.

## Non-Goals

- No API key hard-coding.
- No `.env` read or secret printing.
- No automated real Gemini calls.
- No vendor edits.
- No SSL verification changes.
- No commit or push.

## Implementation Direction

- Add `App\Services\Ai\GeminiKeyPool`.
- Add multi-key config in `config/showroom_ai.php`.
- Use temporary Laravel AI provider aliases per key attempt so cached provider instances cannot keep stale credentials.
- Forget temporary provider aliases after each attempt.
- Catch `RateLimitedException` separately from generic provider errors.

## Verification Plan

- Focused AI tests for parsing, rotation, cooldown and failover.
- Required command suite:
  - `php artisan config:clear`.
  - `php artisan cache:clear`.
  - `php artisan view:clear`.
  - `php artisan view:cache`.
  - `vendor\bin\pint --dirty --format agent`.
  - `npm.cmd run build`.
  - `php artisan test`.
  - `php artisan route:list --path=ai -v`.
- Browser/Playwright QA against the existing widget and endpoint.
