# Phase 16.3 Report - Gemini Multi-Key Rotation + Rate Limit Failover

## 1. Status

PASS CO GHI CHU.

## 2. Root Cause

- Previous AI assistant flow used a single Gemini key from `config('ai.providers.gemini.key')`.
- When Gemini returned HTTP 429, Laravel AI SDK mapped it to `RateLimitedException`.
- `ShowroomAssistantService` caught provider errors globally and returned fallback immediately.
- A single key was not stable enough because any temporary key-level rate limit ended the request even if the user had other valid keys available.

## 3. Multi-Key Config

- Existing legacy config remains supported:
  - `GEMINI_API_KEY`.
- New config:
  - `GEMINI_API_KEYS`.
  - `GEMINI_KEY_COOLDOWN_SECONDS`, default `120`.
  - `GEMINI_KEY_ROTATION`, default `round_robin`.
- `GEMINI_API_KEYS` supports comma-separated values and also newline/pipe separators.
- Parser behavior:
  - combines `GEMINI_API_KEY`, `GEMINI_API_KEYS` and the existing `ai.providers.gemini.key` config fallback.
  - trims whitespace.
  - ignores empty values.
  - deduplicates values.
  - has no hard-coded key count limit.
- `.env.example` contains placeholders only.

## 4. Rotation / Failover

- Strategy: round-robin by default.
- Round-robin cursor is stored in cache.
- Every request attempts at most the number of currently available key candidates.
- No infinite retry loop.
- Each Gemini attempt creates a temporary Laravel AI provider alias with that candidate key.
- Temporary provider alias is forgotten after the attempt to avoid stale cached provider config.
- Rate-limit handling:
  - catches `RateLimitedException`.
  - marks that candidate in cooldown.
  - logs only safe metadata such as `key_index`, exception class, reason and attempts.
  - tries the next available key.
- If all available keys are rate limited, endpoint returns:
  - `status: fallback`.
  - `reason: rate_limited`.
  - friendly rate-limit fallback message.
- Transient provider errors such as provider overloaded, connection exception or 5xx request exception can try the next available key.
- Non-transient provider errors still return the existing provider fallback.

## 5. Files Changed

- `.env.example`.
- `config/showroom_ai.php`.
- `app/Services/Ai/GeminiKeyPool.php`.
- `app/Services/Ai/ShowroomAssistantService.php`.
- `tests/Feature/AiAssistantFallbackTest.php`.
- `tests/Feature/Phase16_3GeminiKeyRotationTest.php`.
- `.planning/phases/phase-16-3-gemini-multi-key-rotation/*`.
- `.planning/baocao/phase-reports/phase-16-3-report.md`.
- `.planning/kientruc/AI-ASSISTANT.md`.
- `.planning/kientruc/SECURITY.md`.
- `.planning/kientruc/TESTING.md`.
- `.planning/STATE.md`.
- `.planning/bug/BUG-INDEX.md`.
- `.planning/bug/fixed/phase-16-3-gemini-rate-limit-failover.md`.

## 6. Tests

- Config parsing:
  - no keys returns empty list.
  - single legacy key works.
  - comma-separated multi-key list works.
  - duplicate/blank/whitespace values are normalized.
  - 20-key list parses without a hard limit.
- Rotation:
  - round-robin does not always start with the first key.
  - cooldown skips rate-limited key.
  - all keys in cooldown exhaust candidates.
- Failover:
  - first key rate-limited, second key succeeds.
  - all keys rate-limited returns `rate_limited` fallback.
  - all keys already cooling down returns `rate_limited` fallback without prompting AI.
- Security:
  - response does not contain fake key values.
  - log assertion verifies raw fake key values are not logged in warning context.
  - `.env.example` documents placeholders and does not contain `APP_KEY=base64:` or `AIza`.

## 7. Commands

- `php artisan config:clear`: pass.
- `php artisan cache:clear`: pass after local MySQL was started.
- `php artisan cache:clear array`: pass during environment isolation check.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `vendor\bin\pint --dirty --format agent`: pass.
- `npm.cmd run build`: pass.
- `php artisan test`: pass, 100 tests / 1083 assertions.
- `php artisan route:list --path=ai -v`: pass, `POST /ai/showroom-assistant` keeps `web` and `throttle:12,1`.

## 8. Manual QA

- Local app checked through Browser and Playwright CLI.
- Live prompt checked:
  - `tìm giúp tôi chiếc bmw s1000rr`.
- Result:
  - widget returned a normal BMW S1000RR / BMW Motorrad answer.
  - no rate-limit fallback on the final live prompt.
  - action chips included product detail, quote, test drive and BMW Motorrad catalog.
  - broken images: 0.
  - console errors: 0.
  - horizontal overflow: false.
  - visible secret pattern: false.
- Playwright CLI mobile smoke:
  - launcher visible.
  - panel exists and stays within viewport.
  - broken images: 0.
  - console errors: 0.
  - no `x-html`.
  - no draggable nodes.
  - visible secret pattern: false.

## 9. Security

- No `.env` content was read or printed.
- No real API key was logged or reported.
- No key was hard-coded.
- No SSL verification was changed.
- Automated tests used Laravel AI fakes and did not call Gemini.
- Prompt context boundary remains public-only.
- Temporary provider aliases are cleaned up after each attempt.

## 10. Planning Updated

- `.planning/phases/phase-16-3-gemini-multi-key-rotation/PLAN.md`.
- `.planning/phases/phase-16-3-gemini-multi-key-rotation/CHECKLIST.md`.
- `.planning/phases/phase-16-3-gemini-multi-key-rotation/SUMMARY.md`.
- `.planning/baocao/phase-reports/phase-16-3-report.md`.
- `.planning/kientruc/AI-ASSISTANT.md`.
- `.planning/kientruc/SECURITY.md`.
- `.planning/kientruc/TESTING.md`.
- `.planning/STATE.md`.
- `.planning/bug/BUG-INDEX.md`.
- `.planning/bug/fixed/phase-16-3-gemini-rate-limit-failover.md`.

## 11. Conclusion

- Phase 16.3 can be closed as PASS CO GHI CHU.
- The note is only that local MySQL had to be started before default `cache:clear` and Browser QA could run.
- No commit or push was performed.
