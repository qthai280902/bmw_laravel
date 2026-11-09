# Phase 20 Checklist — Catalog Accordion, Scroll Reveal, Scrollspy & Article Typography Hierarchy

- [x] **Step 1: Dependencies, Global Scroll Reveal & CSS Utilities (`package.json`, `app.js`, `app.css`)**
  - [x] Install `@alpinejs/collapse` (`npm install -D @alpinejs/collapse`).
  - [x] Import and register `Alpine.plugin(collapse)` in `resources/js/app.js`.
  - [x] Implement `window.initScrollReveal(root = document)` with `IntersectionObserver` in `resources/js/app.js` and invoke on `DOMContentLoaded`.
  - [x] Define `.reveal-on-scroll`, `.reveal-on-scroll.is-revealed`, `.scrollbar-none`, `@media (prefers-reduced-motion: reduce)`, and `.article-content` (`h2`, `h3`, `p`, `strong`/`b`) in `resources/css/app.css`.

- [x] **Step 2: Catalog Accordion Sidebar & Independent Scroll (`resources/views/products/index.blade.php`)**
  - [x] Configure desktop sidebar with `lg:sticky lg:top-24 lg:self-start lg:max-h-[calc(100vh-7rem)] lg:overflow-y-auto lg:pr-2 scrollbar-none`.
  - [x] Wrap each filter group (Loại sản phẩm, Phân khúc / Nhóm phụ kiện, Khoảng giá) in `x-data="{ open: true }"` with rotating chevron SVG (`:class="open ? 'rotate-180' : ''"`) and `x-show="open" x-collapse`.
  - [x] Apply `.reveal-on-scroll` to `#catalogGrid` and empty state block.

- [x] **Step 3: Landing Page Scroll Reveal & Hover Polish (`resources/views/welcome.blade.php`)**
  - [x] Add `.reveal-on-scroll` to Product Lines, Featured Selection, Editorial Showroom (`Tìm hiểu thêm`), and Showroom Flow sections.
  - [x] Apply `transition-transform duration-700 ease-out group-hover:scale-105` on images and `hover:border-[#1C69D4]/50` on cards.

- [x] **Step 4: Product Detail Scrollspy & Sticky Offset (`resources/views/products/show.blade.php`)**
  - [x] Add `scroll-mt-32` to `#overview`, `#highlights`, `#design`, `#technology`, `#gallery`, `#technical-data`, `#services`, and `#booking`.
  - [x] Implement Alpine.js Scrollspy on sticky `<nav>` using `IntersectionObserver` (`rootMargin: '-20% 0px -60% 0px'`), bottom-of-page fallback for `booking`, smooth scroll `scrollTo(id)`, and active tab indicator (`text-white font-bold border-b-2 border-[#1C69D4] bg-blue-950/20`).

- [x] **Step 5: Article Typography & Visual Hierarchy (`resources/views/articles/show.blade.php`, `resources/views/articles/index.blade.php`)**
  - [x] Upgrade `articles/show.blade.php` with `text-3xl sm:text-5xl lg:text-6xl font-black` on `h1`, editorial lead box (`border-l-2 border-[#1C69D4] bg-zinc-900/40 py-3 pl-5`), category badge (`bg-blue-950/50 text-[#70A7FF]`), and `.article-content` formatting for `h2`, `h3`, and `p`.
  - [x] Upgrade `articles/index.blade.php` with category badge, `group-hover:text-[#70A7FF]` title contrast, and `mt-2 line-clamp-3 text-sm leading-relaxed text-zinc-400` excerpt.

- [x] **Step 6: Verification & `.planning` Synchronization**
  - [x] `php vendor/bin/pint --format agent` -> Passed (`{"tool":"pint","result":"passed"}`).
  - [x] `php artisan optimize:clear` -> Passed.
  - [x] `npm run build` -> Vite v6 production build succeeded (`55 modules transformed`).
  - [x] `php artisan test` -> **114 passed (1147 assertions)**, 100% PASS.
  - [x] Synchronized `.planning/phases/phase-20-catalog-accordion-scrollspy-ux/*`, `.planning/baocao/phase-reports/phase-20-report.md`, `.planning/STATE.md`, and `.planning/ROADMAP.md`.
