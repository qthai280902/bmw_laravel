# Phase 19 Checklist — Vietnamese Typography Overhaul & Be Vietnam Pro

- [x] **Step 1: Configure `Be Vietnam Pro` Font in Tailwind CSS v4**
  - [x] Add `@import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap');` at line 1 of `resources/css/app.css` (before `@import 'tailwindcss';`).
  - [x] Set `--font-sans: 'Be Vietnam Pro', ui-sans-serif, system-ui, sans-serif;` inside `@theme` in `resources/css/app.css`.
  - [x] Bind `:root` and `body` to `var(--font-sans)` with antialiased font smoothing and base `line-height: 1.625` (`leading-relaxed`) on `<p>`.
  - [x] Keep Google Fonts `<link>` preconnect and stylesheet in `resources/views/layouts/app.blade.php`, `resources/views/layouts/guest.blade.php`, `resources/views/services.blade.php`, and `resources/views/experiences.blade.php`.

- [x] **Step 2: Remove `uppercase` & Wide Tracking from Paragraphs and Long Headings**
  - [x] `resources/views/articles/show.blade.php`: Removed `uppercase` from `h1`, section `h2`, sidebar category value, and related `h2`; added `leading-relaxed` to excerpt and content paragraphs.
  - [x] `resources/views/articles/index.blade.php`: Removed `uppercase` from page `h1`, empty-state `h2`, and article card `h2`; applied `leading-relaxed` to `<p>` excerpts.
  - [x] `resources/views/welcome.blade.php`: Removed `uppercase` from Hero `h1`, section `h2` headings, product line `h3`, article card `h3`, and showroom flow `h3`; applied `leading-relaxed` to `<p>` descriptions.
  - [x] `resources/views/services.blade.php`: Removed `uppercase` and `tracking-widest` / `tracking-[0.2em]` from Hero `h1`, Hero `<p>`, service `h2` titles, `<p>` descriptions, and footer `h3`; applied `tracking-tight` and `leading-relaxed`.
  - [x] `resources/views/experiences.blade.php`: Removed `uppercase` and `tracking-widest` / `tracking-[0.2em]` from Hero `h1`, Hero `<p>`, experience `h2` titles, `<p>` descriptions, and footer `h3`; applied `tracking-tight` and `leading-relaxed`.
  - [x] `resources/views/products/show.blade.php`: Removed `uppercase` from Hero `h1`, section `h2` & `h3` headings, modal `h4`, and `<p>` notes; applied `tracking-tight` and `leading-relaxed`.

- [x] **Step 3: Build & Regression Verification**
  - [x] `php vendor/bin/pint --format agent` -> Passed (`{"tool":"pint","result":"passed"}`).
  - [x] `php artisan optimize:clear` -> Passed.
  - [x] `npm run build` -> Vite v6 production build succeeded (`public/build/assets/app-izHYdINk.css`, `app-BM1UHsC0.js`).
  - [x] `php artisan test` -> 114 passed (1147 assertions), 100% PASS.

- [x] **Step 4: Documentation & `.planning` Synchronization**
  - [x] Created `.planning/phases/phase-19-vietnamese-typography-fix/PLAN.md`.
  - [x] Created `.planning/phases/phase-19-vietnamese-typography-fix/CHECKLIST.md`.
  - [x] Created `.planning/phases/phase-19-vietnamese-typography-fix/SUMMARY.md`.
  - [x] Created `.planning/baocao/phase-reports/phase-19-report.md`.
  - [x] Updated `.planning/STATE.md` and `.planning/ROADMAP.md`.
