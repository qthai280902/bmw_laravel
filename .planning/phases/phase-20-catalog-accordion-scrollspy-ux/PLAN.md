# Phase 20: Catalog Accordion Filters, Landing Page Scroll Reveal, Product Detail Scrollspy & Article Typography Hierarchy

## 1. Objectives & Architectural Context

Phase 20 delivers four frontend/UX refinements across the public BMW Showroom interface (`bmw_laravel`) following the **BMW Modern Dark Luxury** design system (Zinc-950 `#09090b`, card surface `#121215`, border `#27272a`, BMW Signature Blue `#1C69D4` & `#70A7FF`, strict `0px` border-radius, and `Be Vietnam Pro` typography):
1. **Catalog Sidebar Accordion & Independent Scroll (`resources/views/products/index.blade.php`)**:
   - Each filter section (Product Type, Segment/Accessory Group, Price Range) is wrapped in an Alpine.js accordion (`x-data="{ open: true }"`) powered by `@alpinejs/collapse` (`x-show="open" x-collapse`) with a smoothly rotating chevron SVG (`:class="open ? 'rotate-180' : ''"`).
   - Desktop sticky sidebar (`lg:sticky lg:top-24 lg:self-start lg:max-h-[calc(100vh-7rem)] lg:overflow-y-auto lg:pr-2 scrollbar-none`) scrolls independently without moving the main vehicle grid.
2. **Scroll Reveal & Landing Page Motion (`resources/js/app.js`, `resources/css/app.css`, `resources/views/welcome.blade.php`)**:
   - Global `window.initScrollReveal(root = document)` uses `IntersectionObserver` (`threshold: 0.08`, `rootMargin: '0px 0px -30px 0px'`) to trigger `.is-revealed` on `.reveal-on-scroll` sections (static and dynamically appended nodes), with full `@media (prefers-reduced-motion: reduce)` support.
   - Vehicle and article cards feature refined image zoom (`group-hover:scale-105 transition-transform duration-700 ease-out`) and subtle BMW Blue border highlight (`hover:border-[#1C69D4]/50`).
3. **Product Detail Sticky Scrollspy Navigation (`resources/views/products/show.blade.php`)**:
   - Sticky navigation (`sticky top-20 z-40`) tracks all 7 product sections (`overview`, `highlights`, `design`, `technology`, `gallery`, `technical-data`, `booking`) via `IntersectionObserver` (`rootMargin: '-20% 0px -60% 0px'`) and bottom-scroll detection.
   - All target sections include `scroll-mt-32` so smooth scrolling (`scrollIntoView({ behavior: 'smooth' })`) never obscures section titles beneath the sticky header.
   - Active tab displays `text-white font-bold border-b-2 border-[#1C69D4] bg-blue-950/20`.
4. **Article Editorial Visual Hierarchy (`resources/views/articles/show.blade.php`, `resources/views/articles/index.blade.php`, `resources/css/app.css`)**:
   - `articles/show.blade.php`: Prominent `h1` (`text-3xl sm:text-5xl lg:text-6xl font-black`), editorial lead excerpt (`border-l-2 border-[#1C69D4] bg-zinc-900/40 py-3 pl-5`), category badge (`text-[#70A7FF] bg-blue-950/50 border border-blue-900/40`), and structured `.article-content` rules for `h2`, `h3`, `p`, and `strong`.
   - `articles/index.blade.php`: Enhanced visual contrast between card `h2` (`text-xl font-bold text-white group-hover:text-[#70A7FF]`) and 3-line clamped excerpt (`mt-2 line-clamp-3 text-sm leading-relaxed text-zinc-400`).

---

## 2. Invariant Constraints
- **Zero Backend Mutation**: No modifications to routes, controllers, form requests, models, or database migrations.
- **100% Test Suite Preservation**: All **114 tests (1147 assertions)** pass 100%, preserving every asserted HTML token (`Tìm hiểu thêm`, `Đăng ký lái thử`, `Đặt hàng ngay`, `Liên hệ tư vấn`, `Thêm so sánh`, `Motorrad`, `technical-data`, `showDetailedSpecs`).
