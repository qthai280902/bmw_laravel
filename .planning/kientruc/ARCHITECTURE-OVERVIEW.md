# Architecture Overview

## Tong quan

- Framework: Laravel 12.
- Frontend: Blade, Tailwind CSS v4, Alpine.js.
- Production database theo report deployment: PostgreSQL tren Render.com.
- Mot so tai lieu cu van nhac MySQL; can xem config/env hien tai truoc khi thao tac database.
- Domain hien tai: BMW Digital Showroom & CRM Lead-Gen / Aftersales Platform.

## Thanh phan

- Customer-facing showroom: trang chu, catalog, product detail, booking.
- Admin panel: products, categories, appointments, customers, users, dashboard analytics.
- CRM leads: luu trong `appointments`.
- Service/Action Pattern: da duoc ghi trong PROJECT va report cu; code hien tai co cac action product. Can audit truoc khi mo rong.

## UI standard

- Dark theme chu dao Zinc-950.
- Font Inter trong admin layout hien tai.
- 0px radius trong design tokens.
- BMW Blue `#1C69D4`.

## Domain chinh

- Appointments/CRM leads.
- Products/Cars.
- Categories/Segments.
- Users/Admin.
- Product images.
