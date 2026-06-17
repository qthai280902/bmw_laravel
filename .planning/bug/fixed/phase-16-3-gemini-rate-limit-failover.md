# Phase 16.3 Fixed Bug - Gemini Rate Limit Failover

## Status

Fixed / Verified by automated tests.

## Symptom

- Live AI assistant could return fallback when Gemini returned `RateLimitedException`.
- A single configured Gemini key meant one key-level 429 ended the request immediately.

## Root Cause

- The AI service previously used one Gemini key from `config('ai.providers.gemini.key')`.
- `ShowroomAssistantService` caught provider errors globally and returned fallback immediately.
- No key pool, cooldown, rotation or failover existed.

## Fix

- Added `App\Services\Ai\GeminiKeyPool`.
- Added `GEMINI_API_KEYS`, `GEMINI_KEY_COOLDOWN_SECONDS` and `GEMINI_KEY_ROTATION`.
- Added round-robin candidate selection.
- Added cooldown for rate-limited candidates.
- Added temporary Laravel AI provider aliases per key attempt.
- Added separate rate-limit fallback when every key is rate limited or cooling down.

## Verification

- Focused Phase 16.3 tests cover:
  - empty key config.
  - legacy single-key config.
  - comma-separated multi-key config.
  - dedupe/trim/empty filtering.
  - no parser hard limit with 20 keys.
  - round-robin rotation.
  - cooldown skip/exhaustion.
  - first-key rate limit then second-key success.
  - all-key rate limit fallback.
  - all-key cooldown fallback without prompting AI.
  - `.env.example` placeholder safety.
