# Phase 11 Checklist

## Audit
- [x] Xac minh route chi tiet xe.
- [x] Xac minh controller va view that.
- [x] Xac minh layout `<x-app-layout>`.
- [x] Xac minh Product fields/casts/relations can dung.
- [x] Xac minh booking, quote, compare, specs modal va gallery hien co.

## UI
- [x] Hero full-bleed dung anh san pham/fallback noi bo.
- [x] Sticky anchor navigation.
- [x] Overview section.
- [x] Highlight specs section.
- [x] Design/gallery section.
- [x] Technology section.
- [x] Technical data section.
- [x] Services va booking CTA section.
- [x] Related vehicles section co fallback rong.
- [x] Dark Zinc-950, Inter, 0px radius.

## Chuc nang giu nguyen
- [x] Test drive link giu `appointments.create` voi `product_id` va `type=test_drive`.
- [x] Quote link giu `appointments.create` voi `product_id` va `type=quote`.
- [x] Compare button goi `toggleComparison(productId)`.
- [x] Specs modal van dung Alpine.
- [x] Gallery van dung `ProductImage`.

## Guards
- [x] Khong co `href="#"` trong view chi tiet.
- [x] Khong co `Product::all()` / `::all()` trong controller product.
- [x] Khong co placeholder image ngoai.
- [x] Khong co class `rounded` trong `resources/views/products/show.blade.php`.
- [x] Khong sua Phase 10/admin/auth/register/settings.

## Verification
- [x] `php artisan view:clear` pass.
- [x] `php artisan view:cache` pass.
- [x] `php artisan route:list` pass.
- [x] `vendor/bin/pint --dirty --format agent` pass.
- [x] `npm.cmd run build` pass.
- [x] HTTP smoke `/catalog/bmw-330i-sedan` status 200.
- [ ] Full `php artisan test` pass toan bo.
