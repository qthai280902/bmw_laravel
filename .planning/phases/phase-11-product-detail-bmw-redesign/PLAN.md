# Phase 11 Plan - Product Detail BMW Redesign

## Muc tieu

- Dai tu trang chi tiet xe cong khai theo huong showroom cao cap.
- Giu route, controller va chuc nang hien co.
- Khong dung code, layout hay asset copy tu BMW.vn.
- Khong dung anh placeholder ngoai; chi dung anh san pham co san va fallback noi bo.
- Giu dark theme Zinc-950, Inter, 0px border radius.

## Audit truoc khi sua

- Route chi tiet xe: `GET /catalog/{product}`, name `products.show`.
- Controller: `App\Http\Controllers\ProductController@show`.
- View that: `resources/views/products/show.blade.php`.
- Layout that: `<x-app-layout>` qua `resources/views/layouts/app.blade.php`.
- Product relation can dung: `category`, `images`, `primaryImage`.
- Chuc nang can giu:
  - Dat lich lai thu voi `appointments.create`.
  - Yeu cau bao gia voi `appointments.create`.
  - Compare button qua `toggleComparison(id)`.
  - Technical specs modal qua Alpine.
  - Gallery tu `ProductImage`.

## Huong trien khai

- Refactor rieng view `resources/views/products/show.blade.php`.
- Them query related vehicles co `limit(3)` va eager load trong `ProductController@show`.
- Render thong so tu `translated_specs` / `specifications` that.
- Fallback an toan khi xe chua co image/spec.
- Kiem tra khong co `href="#"`, `Product::all()`, placeholder ngoai, class `rounded` trong view chi tiet.

## Verification

- Blade compile: `php artisan view:cache`.
- Route smoke: `php artisan route:list --path=catalog -v`.
- HTTP smoke: `GET /catalog/bmw-330i-sedan` tra 200.
- Build: `npm.cmd run build`.
- Full suite: ghi nhan con 10 fail cu ngoai Phase 11.
