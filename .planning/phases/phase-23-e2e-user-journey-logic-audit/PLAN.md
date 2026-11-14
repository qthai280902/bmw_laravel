# PHASE 23 PLAN: KIỂM TOÁN TOÀN DIỆN CHỨC NĂNG NGƯỜI DÙNG (GUEST & REAL USER) & KIỂM THỬ LOGIC NGHIỆP VỤ END-TO-END

**Trạng thái**: Đang thực thi & nghiệm thu  
**Mục tiêu**: Lập ma trận kiểm kê toàn bộ chức năng Guest & Real User trên toàn hệ thống, phát hiện và vá triệt để các lỗ hổng logic nghiệp vụ ở lớp Request/Controller/Service/Model, và xây dựng bộ kiểm thử E2E (`tests/Feature/Phase23UserJourneyE2ETest.php`).

---

## 1. Ma trận kiểm kê chức năng Guest & Real User

| Phân hệ | Routes / Endpoints | Guest (Khách vãng lai) | Real User (Đã đăng nhập `auth`) | Kiểm soát bảo mật & Logic |
| --- | --- | --- | --- | --- |
| **Catalog & Discovery** | `GET /`, `GET /catalog`, `GET /accessories`, `GET /catalog/{slug}`, `GET /api/products-by-category` | Duyệt mẫu xe nổi bật, lọc theo loại, phân khúc, khoảng giá, từ khóa (`search`), xem chi tiết sản phẩm | Giống Guest + giữ phiên đăng nhập | Scope `active()`, chặn sản phẩm `is_active = false` hoặc xóa mềm (`404`) |
| **So sánh mẫu xe** | `GET /compare?ids=...` | So sánh 2–4 mẫu xe (`car`/`motorbike`) | Giống Guest | Khử trùng lặp ID, lọc bỏ ID phụ kiện, xe ẩn & ID lỗi |
| **Đặt lịch hẹn 5 luồng** | `GET/POST /booking`, `GET /booking/success`, `GET /appointments` | Gửi 5 luồng (`test_drive`, `quote`, `consult`, `trade_in`, `service`/`maintenance`) với tên & SĐT | Tự gắn `user_id` & fallback tên/email từ `Auth::user()`, xem lại tại `GET /appointments` | Chặn ngày quá khứ (`after:now`), regex SĐT, chặn xe ẩn & phụ kiện, lưu `meta_data` & `showroom`, bắn `AppointmentCreated` (`notifications` queue) |
| **Đặt hàng phụ kiện** | `GET/POST /accessories/{product:slug}/order` | Đặt phụ kiện với số lượng >= 1, địa chỉ & SĐT | Tự điền sẵn & fallback `customer_name` / `customer_email` từ `Auth::user()` | Chặn phụ kiện `is_active = false` (`404`) và slug ô tô/mô tô (`404`), tính `total_amount = price * quantity`, bắn `AccessoryOrderCreated` |
| **Bài viết & Ưu đãi** | `GET /tim-hieu-them`, `/tim-hieu-them/{slug}`, `/offers`, `/services`, `/experiences`, `/contact`, `/privacy-policy` | Lọc chuyên mục, đọc bài `published`, gửi form `trade-in` và `quick-booking` | Giống Guest | Chặn bài `draft` và bài hẹn giờ tương lai (`published_at > now()`) trả `404` |
| **Trợ lý AI & RAG** | `POST /ai/showroom-assistant`, `POST /ai/showroom-assistant/reset` | Hỏi đáp 4 Tools + RAG, ngữ cảnh `product_slug`, Action Cards 0px, làm mới hội thoại | Giống Guest | Throttle (`12,1` / `20,1`), đồng bộ `context_product_id` vào CRM |
| **Auth & RBAC** | `GET/POST /login`, `POST /logout`, `/profile`, `/dashboard`, `/admin/*` | Redirect `302 -> /login` với `/appointments`, `/profile`, `/dashboard`, `/admin/*`; `/register` trả `404` | Truy cập được `/appointments` & `/profile` (`200`); `403 Forbidden` toàn bộ `/dashboard` và `/admin/*` | `AdminMiddleware` yêu cầu email `@bmw.com` |

---

## 2. Lỗ hổng logic đã phát hiện & Bản vá Phase 23

1. **`AccessoryOrderController`**: Bổ sung kiểm tra `(bool) $product->is_active` cùng `canOrderAccessory()` trên cả `create()` và `store()` để chặn đặt phụ kiện ngừng kinh doanh (`404`).
2. **`AccessoryOrder` & Admin UI (`Zero N+1`)**: Bổ sung accessor `getTotalAmountAttribute(): int` (`price * quantity`), giữ eager-load `with(['product.category'])` trong `Admin\AccessoryOrderController::index()`, hiển thị cột **Tổng tiền** trên bảng và trang chi tiết Admin, đồng thời pre-fill & fallback tên/email cho Real User trên `accessory-orders/create.blade.php` và `StoreAccessoryOrderRequest`.
3. **`StoreAppointmentRequest`, `Client\AppointmentController` & `appointments/create.blade.php`**:
   - Ánh xạ `type=service` sang `maintenance` + lưu `meta_data['service_type'] = 'service'`.
   - Cho phép `product_id` là `nullable` đối với `consult` và `advisor_meeting` (tư vấn chung showroom).
   - Chặn mọi `product_id` trỏ tới phụ kiện hoặc sản phẩm `is_active = false`, validate định dạng số điện thoại (`regex`).
   - Áp dụng `trim(strip_tags(...))` có chọn lọc cho các trường text tự do (`guest_name`, `customer_name`, `customer_address`, `notes`, `customer_car_model`, `customer_car_condition`, `showroom`, `meta_data` strings), không động vào `email` hay `appointment_date`.
   - Bảo toàn cột `appointments.showroom`, gộp `customer_car_model`/`customer_car_condition` vào `meta_data`, và fallback `guest_name`/`guest_email` từ `Auth::user()` khi khách hàng đã đăng nhập.
4. **`VehicleSearchService::search()` & `products/index.blade.php`**: Thêm lọc từ khóa `search`/`keyword`/`q` qua `name`, `slug`, `description` và ô input `<input name="search">` chuẩn **0px border-radius** BMW Dark Luxury.
