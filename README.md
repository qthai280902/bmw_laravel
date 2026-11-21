<div align="center">
  <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/BMW.svg" alt="BMW Logo" width="90"/>

  <h1>BMW Showroom Online</h1>

  <p>
    Website Showroom BMW toàn diện: giúp khách hàng <strong>ngắm xe, so sánh mẫu xe, hỏi Trợ lý AI 24/7, đặt lịch lái thử, đặt dịch vụ chăm sóc xe và mua phụ kiện chính hãng</strong> —
    đồng thời giúp đội ngũ showroom <strong>điều phối hồ sơ theo Mã kép, phân công nhân viên Sales tự động và theo dõi biểu đồ hiệu suất</strong> trên cùng một hệ thống.
  </p>

  <p>
    <img src="https://img.shields.io/badge/Laravel-12-FF2D20.svg?style=for-the-badge&logo=laravel" alt="Laravel 12" />
    <img src="https://img.shields.io/badge/PHP-8.2+-777BB4.svg?style=for-the-badge&logo=php" alt="PHP 8.2+" />
    <img src="https://img.shields.io/badge/Livewire-4-FB70A9.svg?style=for-the-badge" alt="Livewire 4" />
    <img src="https://img.shields.io/badge/Tr%E1%BB%A3_l%C3%BD-Gemini_AI-4285F4.svg?style=for-the-badge&logo=google" alt="Gemini AI" />
    <img src="https://img.shields.io/badge/Ki%E1%BB%83m_th%E1%BB%AD-202%20b%C3%A0i%20%C4%91%E1%BA%A1t-10B981.svg?style=for-the-badge" alt="202 bài kiểm thử đạt" />
  </p>
</div>

---

## 1. Dự án này là gì?

Hãy hình dung một showroom xe hơi & mô tô BMW hoạt động trên nền tảng web:

- **Khách hàng** có thể xem bộ sưu tập xe, so sánh thông số, trò chuyện trực tiếp với Trợ lý ảo BMW, đặt lịch lái thử/bảo dưỡng hoặc đặt phụ kiện chính hãng — **không cần đăng ký tài khoản**.
- **Nhân viên showroom** đăng nhập vào khu quản trị (`/login` → `/dashboard` hoặc trang CRM theo vai trò) để nhận thông báo theo thời gian thực, duyệt lịch hẹn, phân công khách cho nhân viên Sales và xem biểu đồ hiệu suất làm việc.

Toàn bộ giao diện dùng thống nhất một bộ font tiếng Việt dễ đọc (**Be Vietnam Pro**) với các thẻ bo góc mềm hiện đại; riêng email gửi khách và trang khách tự quản lý lịch sử dụng tông nền sáng tối giản (**Clean Minimalist Luxury**) để dễ xem trên điện thoại.

> **Lưu ý:** Dự án được xây dựng phục vụ mục đích **học tập và nghiên cứu**, không phải website chính thức của BMW Group.

---

## 2. Khách hàng có thể làm gì?

### Xem xe, mô tô & so sánh mẫu xe (`/catalog`, `/compare`, `/accessories`)
- Duyệt danh mục **ô tô BMW**, **mô tô BMW Motorrad** và **phụ kiện chính hãng** (`/accessories`).
- Tìm kiếm theo tên, lọc theo dòng xe, mức giá và sắp xếp — kết quả cập nhật mượt mà không cần tải lại cả trang.
- Mở chi tiết từng mẫu để xem bộ ảnh, thông số kỹ thuật và giá niêm yết.
- Chọn nhiều mẫu xe vào thanh so sánh để đối chiếu cạnh nhau tại `/compare`.

### Đặt lịch hẹn showroom (`/booking`)
Chỉ cần nhập tên và số điện thoại, khách có thể đặt lịch:
- Lái thử xe hoặc xem xe trực tiếp
- Nhận báo giá hoặc gặp chuyên viên tư vấn
- Định giá xe cũ để đổi xe mới (Trade-in)

### Đặt dịch vụ chăm sóc xe (`/services` hoặc `/dich-vu`)
Website giới thiệu sẵn các gói chăm sóc xe chính hãng (Bảo dưỡng định kỳ, Phủ Ceramic, Chăm sóc nội thất da, Kiểm tra điện tử chuyên sâu). Chọn gói nào, biểu mẫu đặt lịch sẽ tự điền sẵn thông tin gói đó.

### Mua phụ kiện chính hãng (`/accessories`)
- Đặt mua trực tiếp từng phụ kiện với bộ tính tổng tiền theo số lượng theo thời gian thực.
- Khi nhiều khách đặt cùng lúc, hệ thống **khóa tồn kho an toàn** để không bao giờ bán vượt quá số lượng đang có trong kho.
- Nếu đơn hàng bị hủy, số lượng hàng tự động được hoàn trả về kho.

### Trang tự quản lý lịch hẹn qua Email (`/appointments/{id}/manage`)
Sau khi đặt lịch, khách nhận được email kèm **đường link bảo mật riêng (Signed URL)** để tự:
- **Xác nhận** chắc chắn sẽ đến đúng giờ
- **Đổi ngày/giờ hẹn** (trong khung `08:00` – `20:00`) hoặc **hủy lịch** mà không cần tạo mật khẩu
- Xem **Mã khách hàng**, **Mã số trong ngày** và tên **Chuyên viên phụ trách**
- Tải tệp lịch `.ics` để lưu thẳng vào Google Calendar hoặc Apple Calendar trên điện thoại.

---

## 3. Trợ lý AI BMW tư vấn 24/7

Ở góc dưới bên phải trang web luôn có nút mở **Trợ lý BMW** trả lời theo luồng chữ trực tiếp (streaming):

- **Tư vấn chọn xe & gói dịch vụ** dựa trên dữ liệu xe, phụ kiện và bài viết thật trong showroom (kết hợp tìm kiếm ngữ nghĩa RAG bằng Cloudflare Workers AI).
- **Ước tính tiền trả góp hàng tháng** dựa trên giá xe thực tế.
- **Kiểm tra phụ kiện còn hàng hay hết hàng** ngay trong cuộc trò chuyện.
- **Nhận diện xe qua hình ảnh**: khách có thể tải ảnh một chiếc BMW lên để AI đoán dòng xe và gợi ý các mẫu đang trưng bày.
- **Nhớ lịch sử trò chuyện**: tự khôi phục cuộc hội thoại khi khách mở lại trang hoặc đổi sang tab trình duyệt khác.
- **Chuyển giao thông tin cho nhân viên**: khi khách từ khung chat chuyển sang đặt lịch, toàn bộ tóm tắt nhu cầu và phân loại mức độ quan tâm sẽ được gắn thẳng vào hồ sơ lịch hẹn của khách.

---

## 4. Khu Quản Trị Showroom (CRM 3 vai trò)

Nhân viên đăng nhập tại `/login` (tài khoản công khai không bật đăng ký mới) và được phân quyền rõ ràng theo 3 vai trò:

| Vai trò | Quyền hạn chính |
| --- | --- |
| **Quản Lý (`admin`)** | Toàn quyền hệ thống: quản lý nhân sự, phân quyền, giao/chuyển hồ sơ cho Sales, xem biểu đồ hiệu suất của mọi nhân viên, xem toàn bộ CRM và nội dung |
| **Tư Vấn Bán Hàng (`sales`)** | Xử lý lịch hẹn (`/admin/appointments`), lịch dịch vụ (`/admin/service-bookings`), đơn phụ kiện (`/admin/accessory-orders`), lịch sử chat AI (`/admin/ai-conversations`), danh bạ khách hàng (`/admin/customers`) và xem **biểu đồ hiệu suất cá nhân** |
| **Marketing (`marketing`)** | Quản lý kho xe & phụ kiện (`/admin/products`), dòng xe (`/admin/categories`), bài viết (`/admin/articles`), xem danh bạ khách và gửi email ưu đãi (`/admin/customers`) |

### Điểm nổi bật khi vận hành hàng ngày
- **Hệ thống Mã kép trên mọi hồ sơ lịch hẹn & đơn phụ kiện**:
  - **Mã khách hàng (`#SĐT_STT`)**: ví dụ `#0901234567_1`, `#0901234567_2` — giúp gõ SĐT là tra ra ngay khách đã đặt bao nhiêu lần.
  - **Mã ngày (`dd/mm/yyyy/NN`)**: ví dụ `21/11/2026/01`, `21/11/2026/02` — tự động reset về `01` mỗi ngày theo giờ Việt Nam (`Asia/Ho_Chi_Minh`) để lễ tân/xưởng đón khách theo thứ tự trong ngày.
  - Có mặt đồng bộ trên bảng quản trị, ô tìm kiếm, bảng chi tiết bên cạnh (Quick Inspector Drawer), trang chi tiết đơn phụ kiện, trang tự quản lý của khách và toàn bộ email gửi đi.
- **Quy trình chuyển trạng thái một chiều (State Machine)**:
  - `Chờ duyệt` → chỉ đi tiếp sang `Đã xác nhận` hoặc `Đã hủy`
  - `Đã xác nhận` → chỉ đi tiếp sang `Hoàn thành` hoặc `Đã hủy`
  - `Hoàn thành` & `Đã hủy` → khóa cứng để tránh sửa nhầm hoặc hoàn kho/trừ kho sai; nếu 2 nhân viên bấm cùng lúc trên 2 tab khác nhau, hệ thống tự chặn thao tác trùng lặp.
- **Điều phối Sales tự động & Thống kê Hiệu suất**:
  - Hồ sơ mới tự động chia cho nhân viên Sales đang giữ ít việc nhất (hoặc để trống an toàn nếu showroom chưa tạo tài khoản Sales nào).
  - Quản lý có thể gán/chuyển Sales trực tiếp ngay trong bảng chi tiết bên cạnh (Quick Inspector Drawer) hoặc bấm **Chia đều hồ sơ chưa phân công** ở trang Khách hàng (`/admin/customers`).
  - Trang thống kê hiệu suất (`/admin/users/{id}/performance` hoặc `/admin/sales/{id}/performance` cho Sales tự xem) hiển thị 4 chỉ số tổng hợp, **Biểu đồ cột nhóm SVG** (Lịch hẹn xe / Dịch vụ / Phụ kiện), **Biểu đồ Donut SVG** phân bổ trạng thái và bảng hồ sơ đang giữ (không phụ thuộc thư viện biểu đồ ngoài).
- **Nút hướng dẫn nhanh `(?)`**: xuất hiện ngay cạnh tiêu đề ở mọi trang quản trị để nhân viên mới mở xem quy trình thao tác tại chỗ.
- **Bộ lọc đồng bộ toàn hệ thống**: lọc nhanh theo Sales phụ trách, khoảng thời gian (`Hôm nay`, `7 ngày`, `30 ngày`, `Tháng này`, `Tùy chọn ngày`), trạng thái, mức độ Lead AI (`HOT` / `TIỀM NĂNG` / `THAM KHẢO`), tình trạng tồn kho & khoảng giá sản phẩm, trạng thái chốt lịch từ hội thoại AI.

---

## 5. Email tự động & Lịch chạy nền (Scheduler)

| Thời điểm | Hành động tự động |
| --- | --- |
| Khách vừa đặt lịch / đặt phụ kiện | Gửi thư tiếp nhận yêu cầu (kèm mã kép & thông tin xe/phụ kiện) + báo chuông cho Sales/Admin |
| Nhân viên duyệt & xác nhận lịch | Gửi thẻ đón tiếp VIP + brochure xe HTML + tệp lịch `.ics` + link tự quản lý |
| Phát hiện khách **HOT LEAD** | Gửi email cảnh báo khẩn mức ưu tiên cao cho đội kinh doanh |
| Trước giờ hẹn 24 giờ (`hourly`) | Gửi email nhắc lịch kèm tệp `.ics` (mỗi lịch chỉ gửi 1 lần) |
| Mỗi giờ (`07:00` – `21:00` VN) | Tự động hủy các lịch `Chờ duyệt` quá hạn > 24 giờ và hẹn gửi thư cảm ơn kèm bảng dự toán trả góp sau khi hoàn tất 2 giờ |
| `02:00` mỗi ngày (VN) | Đồng bộ vector tìm kiếm ngữ nghĩa cho sản phẩm, bài viết và tri thức AI |
| `03:30` mỗi ngày (VN) | Dọn lịch sử chat vãng lai cũ quá 60 ngày (giữ nguyên 100% các cuộc chat đã gắn với khách đặt lịch) |
| `18:00` mỗi ngày (VN) | Gửi email tổng kết hoạt động cuối ngày cho quản trị viên |
| Chủ nhật hàng tuần (`03:00`–`04:00` VN) | Xóa thông báo đã đọc quá 30 ngày, dọn `failed_jobs` quá 7 ngày (`168h`), dọn ảnh mồ côi trong `products/` & `articles/` quá 24 giờ |

Hệ thống còn tích hợp **Gmail Quota Guard** (mặc định giới hạn mềm `420` email/ngày cho chiến dịch khuyến mãi để luôn chừa suất cho email giao dịch quan trọng) và cơ chế gửi dự phòng `failover` (`smtp,log`).

---

## 6. Cài đặt & Chạy dự án

### Yêu cầu môi trường
- **Cách 1 (Chạy trực tiếp):** PHP `>= 8.2`, Composer, Node.js `>= 20`, NPM, MySQL 8 (hoặc SQLite cho môi trường thử nghiệm nhanh).
- **Cách 2 (Chạy bằng Docker):** Docker Desktop / Docker Engine + Docker Compose.

### Cách 1: Chạy trực tiếp trên máy (Local)

```bash
# 1. Tải mã nguồn về máy
git clone <repository-url>
cd bmw_laravel

# 2. Cài đặt thư viện PHP và JavaScript
composer install
npm install

# 3. Tạo file cấu hình và khóa ứng dụng
cp .env.example .env
php artisan key:generate

# 4. Chỉnh lại kết nối DB trong .env (MySQL hoặc SQLite), sau đó tạo bảng và dữ liệu mẫu
php artisan migrate --seed
php artisan storage:link

# 5. Đóng gói giao diện CSS/JS
npm run build

# 6. Khởi động đồng thời Web server, Queue worker và Vite (hoặc chạy riêng từng lệnh bên dưới)
composer run dev
```

Mở trình duyệt tại **http://127.0.0.1:8000**

### Cách 2: Chạy bằng Docker Compose

File `docker-compose.yml` đã cấu hình sẵn **5 service**: `app` (PHP-FPM), `nginx` (cổng mặc định `8080`), `mysql` (`mysql:8.4`, cổng `3307`), `queue` (xử lý hàng đợi email & AI) và `scheduler` (`schedule:work`).

```bash
# 1. Khởi tạo .env và bật toàn bộ container
cp .env.example .env
docker compose up -d --build

# 2. Cài thư viện, sinh key, tạo DB mẫu và build frontend trong container app
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
npm install && npm run build
```

Mở trình duyệt tại **http://localhost:8080**

---

## 7. Tổng hợp toàn bộ các lệnh chạy trong dự án

### A. Lệnh khởi động & phát triển hàng ngày
| Lệnh | Công dụng |
| --- | --- |
| `composer run dev` | Chạy cùng lúc `php artisan serve`, `php artisan queue:listen --tries=1` và `npm run dev` |
| `php artisan serve` | Chỉ chạy web server nội bộ Laravel (`http://127.0.0.1:8000`) |
| `npm run dev` | Bật Vite theo dõi và biên dịch nóng CSS/JS khi chỉnh sửa giao diện |
| `npm run build` | Biên dịch CSS/JS bản production vào `public/build/` |
| `php artisan queue:work --queue=notifications,default --sleep=3 --tries=3 --max-time=3600` | Chạy tiến trình xử lý hàng đợi email, thông báo và đồng bộ AI |
| `php artisan schedule:work` | Chạy bộ lập lịch nền cục bộ để kích hoạt tự động các tác vụ định kỳ |

### B. Lệnh cơ sở dữ liệu & đồng bộ dữ liệu Showroom
| Lệnh | Công dụng |
| --- | --- |
| `php artisan migrate` | Chạy các file migration tạo/cập nhật bảng dữ liệu |
| `php artisan migrate --seed` | Tạo bảng và nạp sẵn toàn bộ dữ liệu mẫu (xe, phụ kiện, bài viết, Admin & 3 Sales) |
| `php artisan db:seed` | Nạp lại/bổ sung dữ liệu mẫu từ `DatabaseSeeder` |
| `php artisan storage:link` | Tạo liên kết `public/storage` để hiển thị ảnh đã tải lên |
| `php artisan showroom:clean-and-sync-data` | Dọn dữ liệu rác thử nghiệm, tạo đủ 3 tài khoản Sales, cấp bù Mã kép còn thiếu, chia đều hồ sơ chưa có Sales, dọn ảnh mồ côi và xóa cache Showroom |
| `php artisan showroom:clean-and-sync-data --dry-run` | Xem trước số lượng bản ghi sẽ được xử lý mà **không ghi thay đổi** vào database |
| `php artisan showroom:prune-orphan-media` | Quét và xóa các tệp ảnh không còn dùng trong `products/` và `articles/` |
| `php artisan showroom:prune-orphan-media --dry-run` | Liệt kê danh sách ảnh mồ côi mà không xóa tệp vật lý |
| `php artisan showroom:prune-orphan-media --force-recent` | Bỏ qua quy tắc giữ an toàn 24 giờ cho tệp vừa tải lên |
| `php artisan vehicle:sync` | Đồng bộ danh mục xe ô tô và mô tô từ seeder mà không xóa dữ liệu đang có |

### C. Lệnh tự động hóa Email, Nhắc lịch & Báo cáo
| Lệnh | Công dụng |
| --- | --- |
| `php artisan appointments:send-reminders` | Gửi email nhắc lịch + tệp `.ics` cho các lịch hẹn sắp diễn ra trong 24 giờ tới |
| `php artisan appointments:cleanup-and-followup` | Tự động hủy lịch `pending` quá hạn > 24h và hẹn giờ gửi email cảm ơn + ước tính trả góp |
| `php artisan showroom:daily-summary` | Tạo và gửi email báo cáo tổng kết vận hành cuối ngày tới quản trị viên |
| `php artisan schedule:list` | Kiểm tra danh sách 8 lịch chạy tự động định kỳ của dự án |

### D. Lệnh Trợ lý AI & RAG Vector Search + MCP
| Lệnh | Công dụng |
| --- | --- |
| `php artisan ai:sync-embeddings` | Tạo/cập nhật vector ngữ nghĩa (`@cf/google/embeddinggemma-2`) cho sản phẩm, bài viết và tri thức showroom |
| `php artisan ai:sync-embeddings --force` | Tái tạo lại toàn bộ vector embedding bất chấp hash cũ |
| `php artisan ai:prune-conversations --days=60` | Dọn lịch sử chat của khách vãng lai cũ quá 60 ngày (luôn giữ nguyên hội thoại gắn với khách đã đặt lịch) |
| Endpoint MCP | Server MCP `BmwShowroomServer` phục vụ công cụ tra cứu xe & tri thức tại đường dẫn web `/mcp/showroom` hoặc cục bộ tên `bmw-showroom` |

### E. Lệnh kiểm thử, định dạng code & dọn cache
| Lệnh | Công dụng |
| --- | --- |
| `php artisan test --compact` | Chạy toàn bộ bộ kiểm thử tự động PHPUnit (hiện đạt **202 passed / 3,218 assertions**) |
| `php artisan test --compact tests/Feature/TenFileTest.php` | Chạy riêng một tệp kiểm thử cụ thể |
| `vendor\bin\pint --dirty --format agent` | Kiểm tra và tự sửa chuẩn code PHP theo Laravel Pint cho các tệp vừa chỉnh sửa |
| `npm run lint` | Kiểm tra và tự sửa lỗi ESLint trong mã JavaScript/TypeScript |
| `npm run format` / `npm run format:check` | Định dạng hoặc kiểm tra định dạng code trong `resources/` bằng Prettier |
| `php artisan optimize:clear` | Xóa sạch toàn bộ cache cấu hình, route, view và cache ứng dụng |
| `php artisan event:list` | Kiểm tra danh sách Events & Listeners đang đăng ký trong hệ thống |

---

## 8. Tài khoản mẫu dùng thử

Sau khi chạy `php artisan migrate --seed` (hoặc `php artisan showroom:clean-and-sync-data`), có thể đăng nhập vào khu quản trị tại **`/login`**:

| Vai trò | Họ tên / Ghi chú | Email | Mật khẩu |
| --- | --- | --- | --- |
| **Quản Lý (`admin`)** | Admin BMW Showroom | `admin@bmw.com` | `12345678` |
| **Quản Lý (`admin`)** | Nguyễn Quốc Thái | `quanly1@bmw.com` | `12345678` |
| **Quản Lý (`admin`)** | Nguyễn Quốc Thái - Quản Lý | `thai@bmw.com` | `12345678` |
| **Sales (`sales`)** | Trần Quang Minh | `sales.minh@bmw.com` | `12345678` |
| **Sales (`sales`)** | Nguyễn Thùy Linh | `sales.linh@bmw.com` | `12345678` |
| **Sales (`sales`)** | Lê Anh Đức | `sales.duc@bmw.com` | `12345678` |

> Bạn cũng có thể tạo thêm tài khoản Marketing hoặc Sales mới ngay trong trang **Nhân sự** (`/admin/users`) bằng tài khoản Quản Lý. Các email thuộc hệ thống showroom sử dụng đuôi `@bmw.com` (hoặc khai báo thêm trong biến `ADMIN_EMAILS`).

---

## 9. Các biến môi trường (`.env`) quan trọng

- **Trợ lý AI Gemini**: `AI_ASSISTANT_ENABLED=true`, `GEMINI_API_KEY=` hoặc nhiều key xoay vòng qua `GEMINI_API_KEYS=` (`GEMINI_KEY_ROTATION=round_robin`). Nếu bỏ trống key, website vẫn chạy bình thường và trợ lý trả lời theo chế độ dự phòng.
- **Tìm kiếm ngữ nghĩa RAG (tùy chọn)**: `AI_EMBEDDINGS_ENABLED=true`, cấu hình `CLOUDFLARE_ACCOUNT_ID` và `CLOUDFLARE_API_TOKEN` (hoặc cặp đa tài khoản `_1`, `_2` để tự failover).
- **Email & Hạn mức Gmail**: `MAIL_MAILER=failover` (hoặc `log` khi dev local), `MAIL_SHOWROOM_ADMIN="advisor@bmw.com"`, `MAIL_CAMPAIGN_RATE_PER_MINUTE=20`, `MAIL_DAILY_SOFT_LIMIT=420`.

---

## 10. Bản quyền & Lưu ý thương hiệu

BMW, BMW Motorrad, logo, tên dòng xe và hình ảnh liên quan thuộc quyền sở hữu của **BMW Group**. Mã nguồn này được xây dựng phục vụ mục đích học tập, nghiên cứu kỹ thuật Laravel/Livewire/AI, không đại diện thương mại cho BMW.
