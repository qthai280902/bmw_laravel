# Phase 12.2 - Product Image Expansion

## Goal

- Improve product detail visual depth by expanding the BMW 330i Sedan image set.
- Stop the detail page from repeating one image across hero, design, technology and gallery sections.
- Preserve existing route, controller, booking, quote, compare and admin logic.

## Scope

- Priority product: `BMW 330i Sedan`.
- Add organized project-local image assets.
- Add idempotent seeder records for `ProductImage`.
- Improve detail page image selection and gallery rendering.

## Guardrails

- No schema changes.
- No route changes.
- No controller flow changes.
- No compare logic changes.
- No booking/quote changes.
- No admin changes.
- Do not depend on `public/storage` because it still points to the old `tmdt_laravel` location.

## Implementation Plan

1. Audit current product image model, BMW 330i records, detail view image usage and asset storage.
2. Generate project-local BMW 330i-inspired bitmap assets.
3. Save final assets under `public/images/cars/330i/`.
4. Create `Bmw330iImageSeeder` to attach the image set to `bmw-330i-sedan`.
5. Add a model helper for context-aware detail image selection.
6. Update detail gallery to render multiple distinct images.
7. Add focused Phase 12.2 feature tests.
8. Run view cache, Pint, build, route list, focused tests, full suite and browser smoke QA.
