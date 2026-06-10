# Phase 12 - Public UI Overhaul

## Goal

- Overhaul the public-facing website UI while preserving existing business logic.
- Fix compare page product images.
- Normalize CTA wording by real product type.
- Extend product detail and accessory detail into deeper BMW-inspired landing pages.

## Scope

- Homepage.
- Public navigation and footer.
- Catalog/listing.
- Compare page.
- Product detail.
- Accessory detail.
- Public product card CTA blocks.

## Guardrails

- Do not change route names or public URLs.
- Do not change schema.
- Do not change CRM booking/quote storage flow.
- Do not change compare localStorage key `bmw_comparison_ids`.
- Do not change admin dashboard Phase 10 behavior.
- Do not fix old auth/register/settings tests in this phase.

## Implementation Plan

1. Audit routes, controllers, models, views, assets and planning docs.
2. Add safe public image URL resolution.
3. Eager load `category`, `primaryImage` and `images` in public product flows.
4. Update product cards and detail CTAs by product type.
5. Rework homepage, catalog, compare and detail views with sharper public UI.
6. Add focused PHPUnit coverage for Phase 12.
7. Run view cache, route list, Pint, Vite build, focused tests and full suite.
8. Update planning docs with real results.
