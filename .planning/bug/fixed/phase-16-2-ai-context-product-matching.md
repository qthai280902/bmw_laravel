# Phase 16.2 Fixed Bug - AI Context Product Matching

## Status

Fixed / Verified.

## Symptom

- AI assistant could answer cars but was not reliably aware of BMW Motorrad products.
- Queries such as `BMW S1000RR`, `S1000RR` or `BMW S 1000 RR` could miss the intended product when the context limit excluded that product.
- Short model-code questions such as `BMW 330i khac gi BMW 530i?` did not always produce the expected product suggestion.

## Root Cause

- Public product context was limited too narrowly and sorted by latest records.
- Matching used full normalized aliases like `bmw330isedan`, so a user message containing only `330i` did not match the product.

## Fix

- Expanded public context to include active cars, BMW Motorrad, accessories and published articles.
- Added product line and `search_aliases` fields to public product context.
- Added normalized model-code aliases such as `330i`, `530i`, `x5` and `s1000rr`.
- Sorted context products by message match score before applying the context limit.

## Verification

- `php artisan test --compact tests\Feature\AiShowroomAssistantTest.php tests\Feature\Phase16_2AiAssistantContextTest.php tests\Feature\AiAssistantWidgetTest.php`: pass, 13 tests / 202 assertions.
- `php artisan test`: pass, 89 tests / 1048 assertions.
- Browser QA:
  - `tìm giúp tôi chiếc bmw s1000rr`: returned BMW S1000RR / BMW Motorrad answer.
  - action chip to `/catalog/bmw-s1000rr`: pass.
