# Phase 16.3 Summary - Gemini Multi-Key Rotation + Rate Limit Failover

## Status

PASS CO GHI CHU.

## What Changed

- Added `App\Services\Ai\GeminiKeyPool`.
- Added multi-key config:
  - `GEMINI_API_KEY`.
  - `GEMINI_API_KEYS`.
  - `GEMINI_KEY_COOLDOWN_SECONDS`.
  - `GEMINI_KEY_ROTATION`.
- `GEMINI_API_KEYS` supports comma, newline and pipe separators.
- Key parser trims, deduplicates and ignores empty values.
- Parser has no hard-coded key count limit.
- Added round-robin rotation through cache.
- Added cache cooldown for rate-limited keys.
- Added temporary Laravel AI provider aliases per key attempt to avoid stale provider instance config.
- Added `rate_limited` fallback reason and a separate friendly rate-limit fallback message.
- Kept `POST /ai/showroom-assistant` unchanged.
- Kept widget and compare logic unchanged.

## Verification

- Focused AI tests: pass, 22 tests / 104 assertions.
- `php artisan config:clear`: pass.
- `php artisan cache:clear`: pass after local MySQL was started for QA.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `vendor\bin\pint --dirty --format agent`: pass.
- `npm.cmd run build`: pass.
- `php artisan test`: pass, 100 tests / 1083 assertions.
- `php artisan route:list --path=ai -v`: pass.

## Browser QA

- Local app checked at `http://127.0.0.1:8000`.
- Live widget prompt checked:
  - `tìm giúp tôi chiếc bmw s1000rr`.
- Result:
  - live answer returned BMW S1000RR / BMW Motorrad content.
  - no rate-limit fallback on the final live prompt.
  - action chips included product detail, quote, test drive and BMW Motorrad catalog links.
  - broken images: 0.
  - console errors: 0.
  - horizontal overflow: false.
  - secret pattern visible in page: false.

## Notes

- Initial default `php artisan cache:clear` failed while MySQL local was off because the default cache store is database. After starting local XAMPP MySQL for QA, the same command passed.
- The temporary PHP server, XAMPP MySQL process and Playwright CLI artifacts created for QA were stopped/removed after verification.
