# Filesystem

## Da xac minh

- Logo static: `public/images/bmw-logo.svg`.
- Public storage route/symlink co xuat hien trong route list: `storage/{path}`.
- Phase 12.3 static product assets dung `public/images/...`.
- Them generated assets theo cau truc:
  - `public/images/cars/{product-slug}/`
  - `public/images/motorbikes/{product-slug}/`
  - `public/images/accessories/{product-slug}/`
- `public/storage` la junction toi `C:\Users\thaib\Downloads\tmdt_laravel\storage\app\public`.
- Phase 12.3 tiep tuc tranh phu thuoc `public/storage` cho generated/static product assets.

## Can xac minh truoc khi sua

- Thu muc anh xe dong `storage/app/public/cars/`: Chua xac minh trong task nay.
- Public symlink/junction `public/storage`: da xac minh la con tro workspace cu.

## Quy tac

- Khong dung sai duong dan storage.
- Khong dua file dong vao `public/images` neu app dang chuan hoa qua storage.
- Khong pha asset path hien co.
- Generated/static public product image packs co the dung `public/images` cho den khi co phase bao tri storage link rieng.
