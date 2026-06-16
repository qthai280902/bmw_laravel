# BUG-COMPARE-001 - Compare ID Normalization

## Status

Fixed / Verified in Phase 16.1.

## Area

Public product compare flow.

## Root Cause

`VehicleSearchService::getComparisonMatrix()` accepted raw query string IDs from `/compare?ids=...`.

The previous implementation relied on `whereIn()` to handle duplicates, invalid IDs and ordering. That meant:

- duplicate IDs were only implicitly removed by the database result set.
- invalid IDs were not normalized before query.
- requested order was not preserved.
- the UI limited compare to 4 products, but the server did not.

## Fix

- Normalize IDs to positive integers.
- Remove duplicates.
- Query only active car/motorbike products.
- Ignore accessories.
- Preserve requested order after query.
- Limit the final compare set to 4 vehicles.

## Verification

- Added `tests/Feature/Phase16_1AiWidgetCompareTest.php`.
- `php artisan test`: pass, 86 tests / 1006 assertions.
- Browser QA checked `/compare?ids=1,2` on desktop/tablet/mobile.
