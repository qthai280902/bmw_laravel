# CHECKLIST PHASE 23: KIỂM TOÁN CHỨC NĂNG NGƯỜI DÙNG & KIỂM THỬ LOGIC E2E

## 1. Kiểm kê chức năng & Audit Logic
- [x] Lập ma trận đầy đủ các phân hệ Guest, Real User (`Customer`) và Admin RBAC qua `routes/web.php`, `routes/auth.php`, Controllers, Requests, Services, Events, Listeners, Mailables và Views.
- [x] Kiểm tra tương thích 2 form nhúng (`resources/views/components/quick-booking-form.blade.php`, `resources/views/components/trade-in-form.blade.php`) với rule mới của `StoreAppointmentRequest`.

## 2. Khắc phục lỗi biên & Tích hợp lưu ý kỹ thuật
- [x] Chặn phụ kiện ngừng kinh doanh (`is_active = false`) trên cả GET/POST trong `app/Http/Controllers/AccessoryOrderController.php`.
- [x] Thêm accessor `getTotalAmountAttribute()` trên `App\Models\AccessoryOrder`, giữ eager-load `with(['product.category'])` (`Zero N+1`) trên `Admin\AccessoryOrderController::index()`, hiển thị Tổng tiền trên `admin/accessory-orders/{index,show}.blade.php`.
- [x] Pre-fill và fallback `customer_name` / `customer_email` từ `auth()->user()` trong `resources/views/accessory-orders/create.blade.php` & `StoreAccessoryOrderRequest`; áp dụng `strip_tags()` đúng phạm vi text tự do (không biến đổi email).
- [x] Vá toàn diện `StoreAppointmentRequest`, `Client\AppointmentController` & `resources/views/appointments/create.blade.php`: ánh xạ `service -> maintenance`, hỗ trợ `consult` không cần xe bắt buộc, chặn `is_active = false` & phụ kiện, validate phone regex, lưu `showroom` + `meta_data` (`trade_in`, `service`), fallback thông tin User đăng nhập, `strip_tags()` đúng phạm vi text tự do.
- [x] Bổ sung tìm kiếm từ khóa (`search`/`keyword`/`q`) trong `App\Services\VehicleSearchService::search()` và ô input `<input name="search">` chuẩn `0px border-radius` trong `resources/views/products/index.blade.php`.

## 3. Bộ kiểm thử E2E (`tests/Feature/Phase23UserJourneyE2ETest.php`)
- [x] Nhóm 1: Catalog, Search, Filter, Product Visibility & Comparison Edge Cases.
- [x] Nhóm 2: 5 Luồng Đặt Lịch Hẹn cho Guest & Authenticated User + Validation + Queue Mail Notifications.
- [x] Nhóm 3: Đặt Hàng Phụ Kiện (`total_amount`, product boundaries, queue Mail, pre-fill User).
- [x] Nhóm 4: Bài Viết Editorial, Ưu Đãi & Chặn Draft / Future Scheduled Articles.
- [x] Nhóm 5: Trợ Lý AI Showroom, RAG Grounding, `context_product_id` & Reset Hội Thoại.
- [x] Nhóm 6: Phân Quyền RBAC (Guest / Customer / Admin), Zero N+1 Query Assertion & Đồng Bộ CRM Admin.

## 4. Nghiệm thu & Tài liệu hóa
- [x] Xuất Artifact `phase_23_plan.md` & `phase_23_report.md`.
- [x] Cập nhật `.planning/phases/phase-23-e2e-user-journey-logic-audit/SUMMARY.md`, `.planning/baocao/phase-reports/phase-23-report.md`, `.planning/STATE.md` & `.planning/ROADMAP.md` sau khi full test suite hoàn tất.
- [x] Chạy `vendor\bin\pint --dirty --format agent` (PASSED).
- [x] Chạy `php artisan optimize:clear` và `npm run build` (PASSED).
- [x] Hoàn tất `php artisan test --compact` (**130 passed, 1453 assertions** trong **20.53s**).
