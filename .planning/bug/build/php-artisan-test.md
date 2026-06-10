# BUG-TEST-001 - Full suite php artisan test con fail

## Ngay phat hien

2026-06-06.

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

## Ket luan hau kiem

- Khong duoc xem la full suite pass.
- Theo hau kiem, cac loi nay khong thuoc Phase 10.
- Can task rieng de dong bo test cu voi route that hoac khoi phuc route neu nghiep vu can.

## Trang thai

Open / Need Verify.
