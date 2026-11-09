# Phase 19: Vietnamese Typography Overhaul & Be Vietnam Pro Font Standardization

## 1. Objectives & Architectural Context

During the visual review of the Vietnamese frontend interface (`bmw_laravel`), a critical typography regression was identified:
1. **Unnatural Letter Spacing & Diacritic Detachment**: Excessive use of `uppercase` combined with wide tracking utilities (`tracking-widest`, `tracking-wider`, `tracking-[0.2em]`) on multi-word Vietnamese headings (`h1`, `h2`, `h3`, `h4`) and body paragraphs (`<p>`) caused Vietnamese diacritics (`ư`, `ơ`, `â`, `ê`, `huyền`, `sắc`, `hỏi`, `ngã`, `nặng`) to visually separate and collide vertically across lines.
2. **Font Pipeline Standardization in Tailwind CSS v4**: To ensure deterministic loading across all viewports and Vite CSS compilation, `Be Vietnam Pro` must be imported directly at the top of `resources/css/app.css` (line 1, prior to `@import 'tailwindcss';`) with complete weights (`300`, `400`, `500`, `600`, `700`, `800`, and `400 italic`) and bound to `--font-sans` inside the `@theme` block, `:root`, and `body`.

### Core Typography Principles (Phase 19 Standard)
- **Font Family**: `--font-sans: 'Be Vietnam Pro', ui-sans-serif, system-ui, sans-serif;`
- **Case Discipline**:
  - **NEVER** apply `uppercase` to `<p>` tags, body descriptions, article excerpts, or multi-line Vietnamese headings (`h1`–`h4`).
  - **ONLY** retain `uppercase` for ultra-short micro-labels (1–2 word category badges, short status tags, navbar items, and compact CTA buttons).
- **Letter Spacing (Tracking)**:
  - Headings (`h1`, `h2`, `h3`, `h4`): Use `tracking-tight` (`-0.02em`) or `tracking-normal` (`0em`) so multi-diacritic Vietnamese words remain cohesive.
- **Line Height (Leading)**:
  - Body paragraphs (`<p>`): Use `leading-relaxed` (`1.625`) with natural sentence case (`font-normal` or `font-medium`, `text-zinc-300` / `text-zinc-400`) to prevent vertical diacritic collisions.

---

## 2. Detailed Implementation Plan

### Step 1: Configure `Be Vietnam Pro` in `resources/css/app.css`
- Place Google Fonts `@import` on **line 1** (before `@import 'tailwindcss';`):
  ```css
  @import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap');
  @import 'tailwindcss';
  ```
- Define `--font-sans` inside `@theme`:
  ```css
  @theme {
      --font-sans: 'Be Vietnam Pro', ui-sans-serif, system-ui, sans-serif;
      --font-display: 'Be Vietnam Pro', ui-sans-serif, system-ui, sans-serif;
  }
  ```
- Apply `font-family: var(--font-sans)` to `:root` and `body`, and establish base rules in `@layer base` (`h1`–`h6` with `letter-spacing: -0.02em; line-height: 1.25;`, `p` with `line-height: 1.625; letter-spacing: normal;`).

### Step 2: Eliminate `uppercase` & Wide Tracking on Paragraphs and Long Headings
Audit and refactor the 6 primary public Blade views:
1. `resources/views/articles/show.blade.php`:
   - Remove `uppercase` from `$article->title` (`h1`), markdown section `h2` headings, sidebar category label, and related article `h2` titles.
   - Apply `leading-relaxed` to article excerpt and body paragraph blocks.
2. `resources/views/articles/index.blade.php`:
   - Remove `uppercase` from page `h1` (`Tìm hiểu thêm`), empty-state `h2`, and article card `h2` (`$article->title`).
   - Apply `leading-relaxed text-zinc-400` to header descriptions and card excerpts.
3. `resources/views/welcome.blade.php`:
   - Remove `uppercase` from Hero `h1`, section `h2` headings (`Dòng sản phẩm`, `Mẫu nổi bật`, `Tìm hiểu thêm`, `Từ khám phá đến tư vấn trong cùng một trải nghiệm.`), product line card `h3`, article card `h3`, and showroom flow card `h3`.
   - Apply `leading-relaxed` to all `<p>` descriptions.
4. `resources/views/services.blade.php`:
   - Remove `uppercase` and `tracking-widest` / `tracking-[0.2em]` from header `h2`, Hero `h1`, Hero `<p>`, service item `h2` headings, service `<p>` descriptions, and footer `h3`.
   - Apply `tracking-tight` to headings and `leading-relaxed` to paragraphs.
5. `resources/views/experiences.blade.php`:
   - Remove `uppercase` and `tracking-widest` / `tracking-[0.2em]` from header `h2`, Hero `h1`, Hero `<p>`, experience section `h2` headings, experience `<p>` descriptions, and footer `h3`.
   - Apply `tracking-tight` to headings and `leading-relaxed` to paragraphs.
6. `resources/views/products/show.blade.php`:
   - Remove `uppercase` from Hero `h1` (`$product->name`), section `h2` & `h3` headings (Overview, Highlights, Design, Technology, Gallery, Technical Data, Services, Booking, Related Models), modal `h4`, deposit `<p>`, and modal footnote `<p>`.
   - Apply `tracking-tight` to headings and `leading-relaxed` to all body paragraphs.

### Step 3: Build & Regression Verification Strategy
1. Code style formatting: `php vendor/bin/pint --format agent`
2. Cache invalidation: `php artisan optimize:clear`
3. Frontend bundle compilation: `npm run build` (verifying Vite CSS bundle compiles cleanly with `@import url(...)` at line 1)
4. Full automated test suite: `php artisan test` — all **114 tests (1147 assertions)** must pass 100% without regression.
