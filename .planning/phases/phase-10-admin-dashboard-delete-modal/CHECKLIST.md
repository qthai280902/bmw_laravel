# Phase 10 Checklist

## Audit
- [x] Kiem tra route dashboard hien tai.
- [x] Kiem tra admin layout that.
- [x] Kiem tra Appointment model/casts/relations.
- [x] Kiem tra Product relations.
- [x] Grep confirm() trong admin/components/layouts.

## Dashboard
- [x] Tao DashboardController.
- [x] Tao dashboard Blade.
- [x] Dung <x-admin-layout>.
- [x] Khong dung @extends('layouts.admin').
- [x] Khong dung Model::all().
- [x] Co eager loading cho relation can thiet.
- [x] Route /dashboard co middleware admin.

## Delete Modal
- [x] Tao component delete-confirm-modal.
- [x] Nhung modal vao admin layout.
- [x] Thay confirm() o products.
- [x] Thay confirm() o categories.
- [x] Grep cuoi khong con confirm().

## Verification
- [x] php artisan view:clear pass.
- [x] php artisan view:cache pass.
- [x] Pint pass.
- [x] npm.cmd run build pass.
- [x] DashboardTest pass.
- [ ] Full php artisan test pass toan bo.
