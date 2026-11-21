# Nhật Ký Phát Triển & Báo Cáo Nghiệm Thu Phase 33 — `bmw_laravel`

- **Thời điểm hoàn tất:** `2026-11-21` (`Asia/Ho_Chi_Minh`)
- **Trạng thái kiểm thử:** **PASS 100%** (`208 passed, 3356 assertions` trong `22.53s`)

---

## 1. Tóm Tắt 7 Hạng Mục Triển Khai

### Phần 1 — Sửa Triệt Để Logic Trang Tự Phục Vụ Khách Hàng (`GuestAppointmentManageController` & `guest-manage.blade.php`)
1. **Tách bạch thời gian lịch hẹn và thời điểm bấm xác nhận:**
   - Hiển thị rõ ràng:
     > *"Đã ghi nhận xác nhận đến Showroom cho lịch hẹn `H:i d/m/Y` (xác nhận lúc `H:i d/m/Y`)."*
2. **Hết hiệu lực xác nhận cũ khi khách đổi lịch (`reschedule`):**
   - Khi khách gửi yêu cầu đổi ngày/giờ hẹn mới, hệ thống chuyển `meta_data['guest_confirmed_at']` cũ sang `meta_data['invalidated_guest_confirmed_at']` (kèm mốc ngày hẹn cũ `invalidated_guest_confirmed_for_date`), đặt `guest_confirmed_at = null` và bật cờ `reschedule_pending_review = true`.
   - Giao diện `guest-manage.blade.php` hiển thị dòng xác nhận cũ với hiệu ứng gạch ngang mờ (`line-through opacity-60`) kèm chú thích *"(Đã hết hiệu lực do thay đổi lịch hẹn)"*.
   - Hiển thị banner màu Amber nổi bật:
     > *"Yêu cầu đổi lịch sang `H:i — d/m/Y` đã được ghi nhận. Chuyên viên BMW Showroom sẽ kiểm tra và xác nhận lại lịch hẹn mới cho Quý khách."*
   - Mở lại nút **"Xác nhận chắc chắn đến Showroom"** để khách xác nhận lại cho khung giờ mới.

---

### Phần 2 — Bảo Mật Hệ Thống Mã Giao Dịch & Làm Sạch Tiêu Đề Email (`DocumentCodeGenerator`)
1. **3 tầng mã giao dịch tách bạch (Công khai vs Nội bộ):**
   - **Mã tham chiếu công khai (`customer_order_code`)** — hiển thị cho khách hàng, không lộ SĐT và không lộ tổng số đơn:
     - `BMW-YYMMDD-XXXX`: Lịch hẹn Lái thử / Tư vấn / Báo giá / Xem xe / Thu cũ đổi mới
     - `SRV-YYMMDD-XXXX`: Lịch hẹn Bảo dưỡng / Chăm sóc xe / Rửa xe
     - `ACC-YYMMDD-XXXX`: Đơn đặt mua phụ kiện
     - `XXXX` sinh ngẫu nhiên chống trùng từ bộ 32 ký tự an toàn `23456789ABCDEFGHJKLMNPQRSTUVWXYZ` (loại bỏ `0/O, 1/I`).
   - **Mã chứng từ kế toán nội bộ (`daily_sequence_code`)** — chỉ hiển thị trong Admin CRM & Kế toán:
     - Định dạng `CT-YYMMDD-001` (tự động reset mỗi ngày theo múi giờ `Asia/Ho_Chi_Minh`, ép kiểu `(int)` hậu tố trong PHP).
   - **Mã nhận diện khách hàng nội bộ đã che (`customer_internal_code`)** — chỉ hiển thị trong Admin CRM & Kế toán:
     - Định dạng `KH-{4_SO_CUOI_SDT}-{SO_LAN_DAT_2_SO}` (ví dụ: `KH-4741-01`, `KH-4741-02`).
2. **Loại bỏ hoàn toàn `#ID` (`#12`, `#ACC-12`, `#BMW-00012`) trên toàn bộ Email & giao diện khách hàng:**
   - Cập nhật 9 lớp Mailable và 13 view Email (`AppointmentConfirmationMail`, `GuestAppointmentChangeMail`, `AccessoryOrderConfirmationMail`, `AccessoryOrderStatusMail`, `AppointmentReminderMail`, `PostTestDriveFollowupMail`, `AppointmentAlertMail`, `HotLeadAlertMail`, `AccessoryOrderAlertMail`).
   - Email gửi khách chỉ hiển thị `publicReferenceCode()` (`BMW-...` / `SRV-...` / `ACC-...`), tuyệt đối không lộ `#ID`, `CT-...` hay `KH-...`.
   - Đồng bộ `CleanAndSyncShowroomDataCommand` (`showroom:clean-and-sync-data`) tự động nâng cấp mọi mã định dạng cũ sang chuẩn Phase 33.

---

### Phần 3 — Bộ Chọn Ngày & Giờ 2 Bước 100% Tiếng Việt (`<x-ui.date-time-picker>`)
- Gỡ bỏ hoàn toàn thư viện Flatpickr tiếng Anh khỏi `layouts/app.blade.php`.
- Xây dựng component Alpine.js thuần `resources/views/components/ui/date-time-picker.blade.php` dùng chung cho `/booking` (`theme="dark"`, vuông `0px`) và `guest-manage.blade.php` (`theme="light"`, bo `rounded-xl`):
  - **Bước 1 · Chọn ngày hẹn:** 3 nút chọn nhanh (*Hôm nay*, *Ngày mai*, *Ngày kia*) + Lịch popup 100% tiếng Việt (*Tháng 1*..*Tháng 12*, *T2*..*CN*), khóa ngày quá khứ, tự động đóng popup ngay khi chọn ngày.
  - **Bước 2 · Chọn khung giờ đón tiếp (08:30 – 17:30):** Lưới 9 khung giờ chuẩn Showroom (`08:30`, `09:30`, `10:30`, `11:30`, `13:30`, `14:30`, `15:30`, `16:30`, `17:30`).
  - **Khóa & tự động reset khung giờ quá khứ:** Nếu người dùng đang chọn khung giờ buổi sáng của *Ngày mai* rồi chuyển ngày về *Hôm nay* (mà khung giờ đó đã trôi qua theo giờ `Asia/Ho_Chi_Minh`), component tự động reset `selectedTime = ''` để chặn gửi giờ quá khứ.

---

### Phần 4 — Đồng Bộ Giao Diện Public Vuông Sắc Nét (`0px` / `rounded-none`) & Sửa Lỗi Bài Viết
1. **Khôi phục ngôn ngữ vuông sắc nét (`0px` / `rounded-none`) trên toàn bộ trang Public:**
   - Đặt `border-radius: 0px` cho `.bg-card-luxury`, `.ai-message-bubble`, `[data-ai-action-chip]`, `.ai-assistant-launcher` trong `resources/css/app.css`.
   - Đồng bộ `rounded-none` trên `services.blade.php`, `products/index.blade.php`, `accessory-orders/create.blade.php`, `catalog-browser.blade.php`, `article-hub.blade.php`, `booking-form-wizard.blade.php`, `accessory-order-calculator.blade.php`, `custom-select.blade.php`, `livewire/pagination/bmw.blade.php`, `vendor/pagination/tailwind.blade.php`, `primary-button`, `secondary-button`, `danger-button`, `text-input`.
   - Giữ nguyên bo góc mềm hiện đại trong `.admin-cockpit` (Admin CRM), hệ thống Email và `.guest-luxury` (`guest-manage.blade.php`).
2. **Bỏ thanh tìm kiếm bài viết ở `/tim-hieu-them` (`article-hub.blade.php`):**
   - Gỡ bỏ ô `<input id="article-hub-search">`, giữ thanh lọc theo Chuyên mục gọn gàng, cân đối.
3. **Sửa lỗi nút `ĐỌC TIẾP` trên Trang chủ (`welcome.blade.php:167`):**
   - Chuyển từ thẻ `<span>` tĩnh sang thẻ `<a href="{{ route('articles.show', $article) }}">` hoạt động 100%.

---

### Phần 5 — Phân Hệ Kế Toán & Sổ Sách (`/admin/accounting*` — Chỉ Super Admin Cao Nhất)
1. **Cấu trúc Database & Phân quyền (`is_super_admin` / `isTopSuperAdmin()`):**
   - Migration `2026_11_21_000002_add_customer_internal_code_and_accounting_closings.php`:
     - Thêm cột `is_super_admin` (`boolean`, mặc định `false`) vào bảng `users`, cấp `is_super_admin = true` duy nhất cho `thai@bmw.com`.
     - Thêm cột `customer_internal_code` (`string(32)`, indexed) vào `appointments` và `accessory_orders`.
     - Tạo bảng `accounting_daily_closings` lưu snapshot chốt sổ kế toán từng ngày.
   - Middleware `EnsureTopSuperAdmin` (`top_super_admin`) chặn `403 Forbidden` tất cả tài khoản không có `isTopSuperAdmin()` (bao gồm cả Quản lý vận hành thường `admin`, `sales`, `marketing`).
   - Menu **"Kế toán & Sổ sách"** trên Sidebar chỉ hiển thị khi `Auth::user()?->isTopSuperAdmin()`.
2. **Chức năng Kế toán & Sổ sách (`AccountingController`):**
   - Bộ lọc Kỳ kế toán (*Hôm nay*, *7 ngày*, *Tháng này*, *Quý này*, *Từ ngày – Đến ngày*), Phân hệ (*Tất cả / Đơn phụ kiện / Dịch vụ / Lịch hẹn xe*) và Trạng thái hạch toán.
   - **5 Thẻ KPI Tài chính:** Doanh thu phụ kiện thực thu (`Completed`), Giá trị phụ kiện đang xử lý (`Pending + Confirmed`), Giá trị đặt cọc xe tiềm năng (kèm tổng giá xe niêm yết tham chiếu), Giá trị giao dịch bị Hủy (Thất thoát), và Tỷ lệ hoàn tất chứng từ (`%`).
   - **Biểu đồ Tài chính & Sản lượng theo ngày:** Biểu đồ cột nhóm SVG thuần + Alpine Tooltip đối chiếu Doanh thu thực thu và Giá trị cam kết.
   - **Gói Dữ Liệu Từng Ngày Bán Hàng (Daily Sales Packs):** Đóng gói chứng từ theo từng ngày (`dd/mm/yyyy`), hiển thị trạng thái **Đang mở** / **Đã chốt sổ ngày**, cho phép mở trang chi tiết sổ ngày (`GET /admin/accounting/days/{date}`), thực hiện **Chốt sổ ngày** (`POST /admin/accounting/days/{date}/close`), và **Xuất sổ kế toán (.CSV)** có sẵn **UTF-8 BOM (`\xEF\xBB\xBF`)** mở trực tiếp trên Excel không lỗi font tiếng Việt.
   - Đăng ký hướng dẫn `(?)` cho `admin.accounting.index` và `admin.accounting.day` trong `AdminPageGuideRegistry`.

---

### Phần 6 — Đại Tu Giao Diện Thêm / Cập Nhật Sản Phẩm (`admin/products/_form.blade.php`)
1. **Định dạng tiền tệ VNĐ tự động:** Tự động thêm dấu chấm phân tách hàng nghìn (`2.499.000.000`) khi gõ Giá bán niêm yết & Tiền đặt cọc, kèm dòng đọc chữ tự động bên dưới (*"≈ 2,5 tỷ VNĐ"*) và chuẩn hóa số nguyên tại `ProductRequest::prepareForValidation()`.
2. **Bảng Thông số kỹ thuật tiếng Việt trực quan:** Ánh xạ nhãn tiếng Việt (*Động cơ*, *Công suất cực đại*, *Mô-men xoắn*, *Tăng tốc 0–100 km/h*...) và bổ sung nút **"Thêm nhanh thông số chuẩn BMW"** theo từng phân loại (*Ô tô*, *Mô tô*, *Phụ kiện*), giữ nguyên cấu trúc JSON `specifications` trong DB.
3. **Thư viện ảnh (Media Library) & Drag-and-Drop Upload:**
   - Cho phép chọn **"Đặt làm ảnh chính"** (`primary_image_id`) và **"Xóa ảnh"** (`delete_image_ids[]`, có nút hoàn tác trước khi lưu) ngay trên lưới ảnh hiện có.
   - Vùng kéo thả ảnh mới (`Drag & Drop Upload Zone`) hiển thị thumbnail xem trước ngay lập tức qua Alpine `URL.createObjectURL`.
4. **Thanh lưu cố định (Sticky Action Bar):** Cố định ở đáy màn hình khi cuộn trang, báo trạng thái *"Có thay đổi chưa lưu"* khi người dùng chỉnh sửa biểu mẫu.

---

### Phần 7 — Dọn Sạch Dữ Liệu Giao Dịch Khách Hàng (`showroom:reset-crm-data --force`)
- Lệnh `php artisan showroom:reset-crm-data --force` (`App\Console\Commands\ResetCrmDataCommand`) xóa sạch toàn bộ dữ liệu giao dịch khách hàng (`interaction_timelines`, `ai_chat_messages`, `ai_chat_sessions`, `notifications`, `accounting_daily_closings`, `appointments`, `accessory_orders` và tài khoản khách hàng thường) trong transaction an toàn.
- Bảo toàn 100%: tài khoản Super Admin `thai@bmw.com` (`is_super_admin = true`), đội ngũ nhân sự `*@bmw.com`, toàn bộ `products`, `product_images`, `categories`, `articles` và `site_settings`.

---

## 2. Danh Sách Tệp Tạo Mới & Chỉnh Sửa

### Tệp tạo mới (9 tệp)
- `database/migrations/2026_11_21_000002_add_customer_internal_code_and_accounting_closings.php`
- `app/Models/AccountingDailyClosing.php`
- `app/Http/Middleware/EnsureTopSuperAdmin.php`
- `app/Http/Controllers/Admin/AccountingController.php`
- `app/Console/Commands/ResetCrmDataCommand.php`
- `resources/views/components/ui/date-time-picker.blade.php`
- `resources/views/admin/accounting/index.blade.php`
- `resources/views/admin/accounting/day.blade.php`
- `resources/views/admin/products/_form.blade.php`
- `tests/Feature/Phase33UxRefinementSecureCodesAccountingAndProductEditorTest.php`

### Tệp chỉnh sửa chính
- `app/Models/User.php`, `app/Models/Appointment.php`, `app/Models/AccessoryOrder.php`, `app/Models/Product.php`, `app/Models/ProductImage.php`
- `app/Services/Crm/DocumentCodeGenerator.php`
- `app/Console/Commands/CleanAndSyncShowroomDataCommand.php`
- `app/Http/Controllers/Client/GuestAppointmentManageController.php`
- `app/Http/Requests/ProductRequest.php`, `app/Actions/Products/CreateProductAction.php`, `app/Actions/Products/UpdateProductAction.php`
- `app/Support/AdminPageGuideRegistry.php`, `bootstrap/app.php`, `routes/web.php`, `database/seeders/DatabaseSeeder.php`
- Toàn bộ 9 lớp Mailable & 13 mẫu view Email (`resources/views/emails/**`, `resources/views/mail/**`)
- `resources/views/appointments/guest-manage.blade.php`, `resources/views/livewire/booking-form-wizard.blade.php`, `resources/views/livewire/article-hub.blade.php`, `resources/views/welcome.blade.php`, `resources/views/admin/products/create.blade.php`, `resources/views/admin/products/edit.blade.php`, `resources/views/components/admin-layout.blade.php`
- `resources/css/app.css` và các view Public đồng bộ `rounded-none` (`0px`)

---

## 3. Kết Quả Kiểm Chứng Thực Tế
- `vendor\bin\pint --dirty --format agent`: `{"tool":"pint","result":"passed"}`
- `npm run build`: `✓ built in 2.15s` (`public/build/assets/app-DCyA5r7o.css` `120.04 kB`, `public/build/assets/app-Cq5J2GD5.js` `375.78 kB`)
- `php artisan migrate --force`: `2026_11_21_000002_add_customer_internal_code_and_accounting_closings ... DONE`
- `php artisan showroom:reset-crm-data --force`: Đã dọn sạch `12` lịch hẹn, `48` thông báo, `1` phiên AI chat (`2` tin nhắn) và bảo toàn 100% nhân sự & danh mục sản phẩm.
- `php artisan test --compact tests/Feature/Phase33UxRefinementSecureCodesAccountingAndProductEditorTest.php`: **`6 passed (133 assertions)`**
- `php artisan test --compact`: **`208 passed (3356 assertions)`** trong **`22.53s`** (Pass 100%, 0 lỗi).
