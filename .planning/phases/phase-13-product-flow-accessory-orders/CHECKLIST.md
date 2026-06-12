# Phase 13 Checklist

- [x] Audit product type / appointment / accessory flow truoc khi code.
- [x] Tao migration `accessory_orders`.
- [x] Tao `AccessoryOrder` model va relation voi `Product`.
- [x] Tao public order controller + form request.
- [x] Tao admin order controller + status request.
- [x] Tao public views cho accessory order form.
- [x] Tao admin list/detail/update views cho accessory orders.
- [x] Them public routes `/accessories/{product:slug}/order`.
- [x] Them admin routes `/admin/accessory-orders`.
- [x] Them link sidebar admin `Don phu kien`.
- [x] Chuan hoa product helpers trong model.
- [x] Car/motorbike giu test drive, quote, compare, specs.
- [x] Accessory khong hien test drive, quote appointment, compare.
- [x] Accessory dung order CTA va contact CTA.
- [x] Compare bo qua accessory IDs.
- [x] Appointment product/category flow khong nhan accessory cho test-drive/viewing.
- [x] Catalog/accessory cards co equal height va action footer aligned.
- [x] Admin product images dung `displayImageUrl()` de tranh storage URL hong.
- [x] Recreate local `public/storage` junction ve workspace `bmw_laravel`.
- [x] Them feature tests public accessory order.
- [x] Them feature tests admin accessory order.
- [x] Cap nhat Phase 12 public UI tests theo route accessory order moi.
- [x] Browser smoke public/admin.
- [x] Pint pass.
- [x] View cache pass.
- [x] Vite build pass.
- [x] Full suite pass.

## Notes

- Browser smoke tao local QA order `#2` trong database dev de kiem tra admin show/update.
- Khong chay seed toan bo trong Phase 13.
- Da chay migration moi cua Phase 13.
