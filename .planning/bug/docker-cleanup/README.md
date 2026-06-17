# Docker Cleanup Review

Date: 2026-06-17 +07:00

## Result

No blocking cleanup bug was fixed in this pass.

## Decisions

- `vendor/` is a local Composer dependency folder and was not treated as junk.
- `node_modules/` is a local npm dependency folder and was not treated as junk.
- No tracked file was moved into `.no/` because no candidate was proven obsolete with enough confidence.

## Kept for manual review

- `files.txt`: tracked legacy path dump.
- `vehicle_ecommerce.sql`: tracked SQL dump.
- `README_DEPLOY.md`: tracked deployment note.

## Follow-up

If the owner confirms any of the kept files are obsolete, move them to `.no/` while preserving their path and update `.no/MANIFEST.md`.
