# BUG-TEST-001 - Full suite php artisan test cu fail auth/register/settings

## Ngay phat hien

2026-06-06.

## Ngay fix

2026-06-10.

## Lenh

```text
php artisan test
```

## Ket qua that

```text
10 failed, 25 passed, 79 assertions.
```

## Nhom fail da ghi nhan

- Login test expected `/dashboard`, actual `/admin/products`.
- `/register` tra 404 vi route register dang bi comment.
- `/settings/password` va `/settings/profile` tra 404 vi route that hien la `/password` va `/profile`.

## Ket luan hau kiem sau fix

- Root cause la test Breeze/settings cu khong khop route va redirect hien tai cua app.
- Khong doi logic nghiep vu de chieu test cu.
- Login test da expect route `admin.products.index` dung voi `AuthenticatedSessionController`.
- Register tests da expect public `/register` 404 vi route dang tat.
- Settings tests da doi sang route that `/profile` va `/password`.
- Full suite hien da pass: 44 tests / 628 assertions.

## Trang thai

Fixed / Verified.
