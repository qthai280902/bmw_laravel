<div align="center">
  <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/BMW.svg" alt="BMW Logo" width="90"/>

  <h1>BMW Showroom Online</h1>

  <p>
    Website Showroom BMW giúp khách hàng <strong>xem xe, so sánh mẫu xe, trò chuyện với Trợ lý AI, đặt lịch lái thử, đặt dịch vụ chăm sóc xe và mua phụ kiện chính hãng</strong> —
    đồng thời giúp nhân viên showroom <strong>theo dõi hồ sơ bằng mã kép, tự chia việc cho đội ngũ kinh doanh và xem thống kê hiệu quả làm việc</strong> ngay trên một hệ thống.
  </p>

  <p>
    <img src="https://img.shields.io/badge/Laravel-12-FF2D20.svg?style=for-the-badge&logo=laravel" alt="Laravel 12" />
    <img src="https://img.shields.io/badge/PHP-8.2+-777BB4.svg?style=for-the-badge&logo=php" alt="PHP 8.2+" />
    <img src="https://img.shields.io/badge/Livewire-4-FB70A9.svg?style=for-the-badge&logo=livewire" alt="Livewire 4" />
    <img src="https://img.shields.io/badge/Tr%E1%BB%A3_l%C3%BD-Gemini_AI-4285F4.svg?style=for-the-badge&logo=google" alt="Gemini AI" />
    <img src="https://img.shields.io/badge/Ki%E1%BB%83m_th%E1%BB%AD-202%20b%C3%A0i%20%C4%91%E1%BA%A1t-10B981.svg?style=for-the-badge" alt="202 bài kiểm thử đạt" />
  </p>
</div>

---

## 1. Dự án này làm được gì?

Hãy hình dung một showroom BMW thu nhỏ hoạt động trực tuyến:

- **Với khách ghé thăm website:** có thể xem ảnh và thông số xe, đặt hai hoặc nhiều mẫu cạnh nhau để so sánh, hỏi đáp với Trợ lý AI bất cứ lúc nào, đặt lịch lái thử, hẹn ngày mang xe đi bảo dưỡng hoặc đặt mua phụ kiện — **mọi thứ đều thực hiện ngay mà không cần đăng ký tài khoản**.
- **Với nhân viên showroom:** đăng nhập vào trang quản trị để nhận thông báo mỗi khi có khách mới, xem khách đã hỏi gì Trợ lý AI trước đó, phân công người phụ trách và theo dõi tiến độ chăm sóc từng khách.

Website sử dụng thống nhất font chữ tiếng Việt **Be Vietnam Pro** rõ nét, các thẻ và nút bấm bo góc nhẹ hiện đại; riêng email gửi tới khách và trang để khách tự đổi lịch được trình bày trên nền sáng tối giản để đọc thoải mái trên điện thoại.

> **Lưu ý:** Đây là dự án phục vụ **học tập và nghiên cứu**, không phải website chính thức của BMW Group.

---

## 2. Trải nghiệm dành cho khách hàng

### Xem, lọc và so sánh xe (`/catalog`, `/compare`, `/accessories`)
- Phân loại rõ **ô tô BMW**, **mô tô BMW Motorrad** và **phụ kiện chính hãng** (`/accessories`).
- Tìm kiếm theo tên xe, lọc theo dòng xe, khoảng giá và sắp xếp — kết quả hiển thị ngay tức thì mà không phải tải lại cả trang.
- Thanh so sánh giúp khách chọn các mẫu xe đang phân vân để đối chiếu trực tiếp tại `/compare`.

### Đặt lịch hẹn showroom (`/booking`)
Chỉ cần để lại tên và số điện thoại, khách có thể hẹn:
- Lái thử hoặc xem xe trực tiếp tại showroom
- Yêu cầu báo giá lăn bánh hoặc gặp chuyên viên tư vấn
- Định giá xe cũ để đổi sang xe mới

### Đặt dịch vụ chăm sóc xe (`/services` hoặc `/dich-vu`)
Trang dịch vụ giới thiệu sẵn các gói chăm sóc tiêu chuẩn (bảo dưỡng định kỳ, phủ bảo vệ sơn Ceramic, vệ sinh nội thất da, kiểm tra tổng quát). Khi khách bấm vào một gói, thông tin gói đó sẽ tự điền sẵn vào biểu mẫu đặt hẹn.

### Mua phụ kiện chính hãng (`/accessories`)
- Tự tính tổng tiền ngay khi khách tăng giảm số lượng.
- Nếu nhiều người bấm mua cùng một lúc khi hàng sắp hết, web vẫn giữ đúng số tồn kho thực tế, không để xảy ra tình trạng nhận đơn quá số hàng đang có.
- Khi đơn bị hủy, số hàng trong đơn tự động được cộng trả lại vào kho.

### Tự quản lý lịch hẹn qua đường link trong Email (`/appointments/{id}/manage`)
Ngay khi đặt lịch, khách nhận được email kèm một **đường link riêng có chữ ký bảo mật**. Bấm vào link này, khách có thể tự:
- **Xác nhận** chắc chắn sẽ đến showroom đúng giờ
- **Đổi sang ngày hoặc khung giờ khác** (`08:00` – `20:00`) hay **hủy lịch** nếu bận đột xuất mà không cần tạo tài khoản
- Xem **Mã khách hàng**, **Mã số trong ngày** và tên **Chuyên viên phụ trách**
- Tải tệp lịch `.ics` để lưu thẳng lịch hẹn vào Google Calendar hoặc lịch mặc định trên iPhone/Android.

---

## 3. Trợ lý AI BMW hỗ trợ 24/7

Ở góc phải màn hình luôn có ô trò chuyện **Trợ lý BMW** trả lời từng chữ theo thời gian thực:

- **Tư vấn chọn xe & gói dịch vụ** dựa trên kho xe, phụ kiện và bài viết thực tế đang có ở showroom.
- **Tính nhanh tiền trả góp mỗi tháng** dựa theo giá xe thật.
- **Kiểm tra phụ kiện còn hàng hay không** ngay trong lúc chat.
- **Nhận diện xe qua ảnh chụp**: khách gửi ảnh một mẫu BMW bất kỳ, trợ lý sẽ nhận diện dòng xe và gợi ý mẫu tương tự đang bán.
- **Nhớ mạch trò chuyện**: quay lại trang web hoặc mở sang tab trình duyệt khác vẫn thấy đoạn hội thoại cũ.
- **Bàn giao thông tin cho nhân viên**: nếu khách trò chuyện xong rồi chuyển sang đặt lịch, phần tóm tắt nhu cầu sẽ đi kèm hồ sơ để nhân viên nắm bắt ngay mà không cần hỏi lại từ đầu.

---

## 4. Trang Quản Trị Showroom (3 vai trò)

Nhân viên đăng nhập tại `/login` (chức năng tự đăng ký tài khoản ngoài trang chủ đã được khóa). Tùy vị trí công việc, mỗi người sẽ thấy đúng khu vực mình cần:

| Vai trò | Công việc phụ trách |
| --- | --- |
| **Quản Lý (`admin`)** | Quản lý tài khoản nhân sự, phân quyền, giao hoặc chuyển khách cho nhân viên Sales, xem biểu đồ hiệu quả làm việc của từng người, cùng toàn bộ tính năng bên dưới |
| **Tư Vấn Bán Hàng (`sales`)** | Theo dõi & xử lý lịch hẹn (`/admin/appointments`), lịch bảo dưỡng (`/admin/service-bookings`), đơn phụ kiện (`/admin/accessory-orders`), lịch sử chat AI (`/admin/ai-conversations`), danh bạ khách (`/admin/customers`) và xem **biểu đồ hiệu suất của chính mình** |
| **Marketing (`marketing`)** | Cập nhật danh sách xe & phụ kiện (`/admin/products`), dòng xe (`/admin/categories`), viết bài tin tức/ưu đãi (`/admin/articles`), xem danh bạ khách hàng và gửi email ưu đãi (`/admin/customers`) |

### Những tiện ích giúp nhân viên làm việc gọn nhẹ hơn
- **Mã kép dễ nhớ trên từng hồ sơ:**
  - **Mã khách hàng (`#SĐT_STT`)**: ví dụ `#0901234567_1`, `#0901234567_2` — chỉ cần gõ số điện thoại là nhìn ra ngay khách đã từng đặt bao nhiêu lần.
  - **Mã số trong ngày (`dd/mm/yyyy/NN`)**: ví dụ `21/11/2026/01`, `21/11/2026/02` — tự quay về `01` khi sang ngày mới theo giờ Việt Nam để bộ phận đón tiếp xếp thứ tự nhanh.
  - Cả hai mã đều xuất hiện trong bảng quản lý, ô tìm kiếm, bảng xem nhanh chi tiết, email và trang xem lịch của khách.
- **Quy trình cập nhật trạng thái đi theo một chiều rõ ràng:**
  - `Chờ duyệt` → chỉ chuyển tiếp sang `Đã xác nhận` hoặc `Đã hủy`
  - `Đã xác nhận` → chỉ chuyển tiếp sang `Hoàn thành` hoặc `Đã hủy`
  - Khi đã `Hoàn thành` hoặc `Đã hủy`, hồ sơ được khóa lại để tránh bấm nhầm làm sai số liệu kho hoặc gửi trùng thư. Nếu hai người cùng mở một hồ sơ và bấm xử lý lệch nhau vài giây, người bấm sau sẽ nhận thông báo nhắc thay vì ghi đè.
- **Tự động chia khách & Xem biểu đồ hiệu quả Sales:**
  - Khi có khách mới, hệ thống tự ưu tiên giao cho nhân viên Sales đang giữ ít việc nhất (nếu showroom chưa tạo tài khoản Sales nào thì để trống an toàn, không báo lỗi).
  - Quản lý có thể gán lại Sales phụ trách ngay trong bảng chi tiết bên cạnh hoặc bấm **Chia đều hồ sơ chưa phân công** ở trang Khách hàng (`/admin/customers`).
  - Trang thống kê (`/admin/users/{id}/performance`, hoặc `/admin/sales/{id}/performance` để Sales tự xem của mình) có 4 thẻ số liệu chính, biểu đồ cột so sánh theo thời gian, biểu đồ tròn phân bổ trạng thái và bảng hồ sơ đang theo dõi.
- **Nút hướng dẫn nhanh `(?)`:** đặt cạnh tiêu đề ở mọi màn hình quản trị để người mới mở xem nhanh cách dùng ngay tại chỗ.
- **Bộ lọc đồng bộ khắp nơi:** lọc theo Sales phụ trách, mốc thời gian (`Hôm nay`, `7 ngày`, `30 ngày`, `Tháng này`, `Tùy chọn ngày`), trạng thái, mức độ tiềm năng AI, tồn kho sản phẩm, hoặc lọc riêng những cuộc chat AI đã/chưa chốt được lịch hẹn.

---

## 5. Nhắc việc & Email tự động chạy nền

| Lúc nào | Việc tự động diễn ra |
| --- | --- |
| Khách vừa đặt lịch hoặc mua phụ kiện | Gửi thư báo đã tiếp nhận (kèm Mã kép) và rung chuông báo cho Sales/Quản lý |
| Nhân viên bấm Duyệt & Xác nhận | Gửi thẻ đón tiếp VIP + brochure xe + tệp lịch `.ics` + link để khách tự đổi giờ nếu cần |
| Có khách mức ưu tiên cao (**HOT LEAD**) | Gửi thư báo khẩn cho đội kinh doanh |
| Trước giờ hẹn 24 tiếng (quét mỗi giờ) | Gửi thư nhắc lịch kèm tệp `.ics` (mỗi lịch chỉ nhắc 1 lần) |
| Mỗi giờ (`07:00` – `21:00` giờ VN) | Tự hủy các lịch `Chờ duyệt` quá 24 giờ chưa ai xử lý; sau khi hoàn tất lái thử 2 tiếng sẽ gửi thư cảm ơn kèm bảng ước tính trả góp tham khảo |
| `02:00` sáng hàng ngày (giờ VN) | Cập nhật chỉ mục tìm kiếm thông minh cho Trợ lý AI |
| `03:30` sáng hàng ngày (giờ VN) | Dọn bớt lịch sử chat của khách vãng lai đã cũ quá 60 ngày (vẫn giữ trọn vẹn các cuộc chat của khách đã đặt lịch) |
| `18:00` chiều hàng ngày (giờ VN) | Gửi báo cáo tổng kết trong ngày về hộp thư quản trị |
| Chủ nhật hàng tuần (`03:00`–`04:00` VN) | Dọn thông báo đã đọc quá 30 ngày, dọn tác vụ lỗi quá 7 ngày và xóa ảnh dư trong thư mục `products/` & `articles/` không còn dùng |

Để tránh chạm ngưỡng giới hạn gửi thư hàng ngày của Gmail, hệ thống có bộ đếm an toàn (mặc định tối đa `420` email khuyến mãi/ngày để luôn chừa suất cho email đặt lịch quan trọng) và chế độ gửi dự phòng (`smtp,log`).

---

## 6. Hướng dẫn cài đặt ban đầu

### Chuẩn bị môi trường
- **Chạy trực tiếp trên máy:** PHP `>= 8.2`, Composer, Node.js `>= 20`, NPM, MySQL 8 (như XAMPP/WAMP hoặc MySQL Service; hoặc SQLite nếu chỉ muốn chạy thử nhanh).
- **Chạy qua Docker:** Docker Desktop / Docker Engine kèm Docker Compose.

### Cài đặt trên máy cá nhân (Windows / macOS / Linux)

```bash
# 1. Tải mã nguồn về
git clone https://github.com/<your-username>/bmw_laravel.git
cd bmw_laravel

# 2. Cài các gói PHP và JavaScript cần thiết
composer install
npm install

# 3. Tạo file cấu hình và khóa bảo mật
cp .env.example .env
php artisan key:generate

# 4. Mở .env điền thông tin Database (MySQL hoặc SQLite), rồi tạo bảng và nạp dữ liệu mẫu
php artisan migrate --seed
php artisan storage:link

# 5. Đóng gói file CSS/JS lần đầu
npm run build
```

> **Lưu ý quan trọng khi chạy bằng `composer run dev`:**
> Lệnh `composer run dev` giúp mở nhanh khoảng **80% nhu cầu hằng ngày** (web server, Vite cập nhật giao diện nóng, hàng đợi mặc định), nhưng **chưa tự chạy hết 100% tính năng nền** vì 3 lý do:
> 1. **Không tự bật MySQL:** Bạn nhớ bật sẵn dịch vụ MySQL (XAMPP/WAMP hoặc Windows Service) trước khi chạy lệnh.
> 2. **Hàng đợi ưu tiên `notifications`:** `composer run dev` chỉ lắng nghe hàng đợi `default`, trong khi thư đặt lịch/xác nhận được gửi qua hàng đợi ưu tiên `notifications`. Để email đi tức thì, cần chạy worker nghe cả `--queue=notifications,default`.
> 3. **Chưa bật bộ hẹn giờ (`schedule:work`):** Các tác vụ theo giờ (nhắc lịch 24h, tự hủy lịch quá hạn, thư hậu mãi, báo cáo 18:00) chỉ tự chạy khi có `php artisan schedule:work`.

#### Chạy nhanh (xem giao diện, thêm/sửa dữ liệu, thử AI chat cơ bản)
```powershell
composer run dev
```

#### Chạy đầy đủ 100% tính năng (bao gồm gửi email ngay lập tức & toàn bộ lịch tự động)
Mở các cửa sổ terminal song song:
```powershell
# Cửa sổ 1: Chạy Web Server + Vite cập nhật giao diện nóng
composer run dev

# Cửa sổ 2: Chạy worker xử lý cả hàng đợi email ưu tiên 'notifications' và 'default'
php artisan queue:work --queue=notifications,default --tries=3 --sleep=3

# Cửa sổ 3: Chạy bộ lập lịch giả lập Cron mỗi phút
php artisan schedule:work
```
*(Mỗi lệnh `queue:work` và `schedule:work` chạy liên tục nên cần nằm ở cửa sổ terminal riêng).*

Sau đó mở trình duyệt vào **http://127.0.0.1:8000**

---

### Cài đặt bằng Docker Compose

File `docker-compose.yml` dựng sẵn **5 container**: `app`, `nginx` (cổng `8080`), `mysql` (`mysql:8.4`, cổng `3307`), `queue` (xử lý sẵn `--queue=notifications,default`) và `scheduler` (`schedule:work`).

```bash
cp .env.example .env
docker compose up -d --build

docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
npm install && npm run build
```
Mở trình duyệt tại **http://localhost:8080**

---

## 7. Danh sách tất cả các lệnh thường dùng trong dự án

### A. Khởi chạy & Đóng gói giao diện
| Lệnh | Công dụng |
| --- | --- |
| `composer run dev` | Mở cùng lúc web server Laravel, `queue:listen` cơ bản và `npm run dev` |
| `php artisan serve` | Chỉ chạy web server cục bộ (`http://127.0.0.1:8000`) |
| `npm run dev` | Bật Vite theo dõi thay đổi CSS/JS theo thời gian thực |
| `npm run build` | Biên dịch CSS (Tailwind v4) & JS bản gọn nhẹ vào `public/build/` |
| `php artisan queue:work --queue=notifications,default --sleep=3 --tries=3 --max-time=3600` | Chạy worker xử lý đầy đủ email ưu tiên, thông báo và đồng bộ AI |
| `php artisan schedule:work` | Chạy bộ hẹn giờ tự động các tác vụ định kỳ trên máy cá nhân |

### B. Dữ liệu, Bảo trì & Nghiệp vụ Showroom
| Lệnh | Công dụng |
| --- | --- |
| `php artisan migrate:fresh --seed` | Xóa làm lại toàn bộ Database sạch kèm dữ liệu mẫu (xe, bài viết, 3 Admin + 3 Sales, mã kép) |
| `php artisan migrate` | Cập nhật thêm bảng/cột mới mà **không làm mất** dữ liệu đang có |
| `php artisan db:seed` | Nạp bổ sung dữ liệu mẫu từ `DatabaseSeeder` |
| `php artisan storage:link` | Tạo đường dẫn hiển thị ảnh tải lên trong `public/storage` |
| `php artisan showroom:clean-and-sync-data --dry-run` | Kiểm tra trước những dữ liệu rác, hồ sơ thiếu mã kép hoặc chưa chia Sales (chưa ghi vào DB) |
| `php artisan showroom:clean-and-sync-data` | Dọn dữ liệu rác thử nghiệm, tạo đủ 3 Sales, cấp bù Mã kép, chia đều hồ sơ chưa có Sales và làm mới cache |
| `php artisan showroom:prune-orphan-media --dry-run` | Liệt kê ảnh thừa trong `products/` và `articles/` không còn sản phẩm/bài viết nào trỏ tới |
| `php artisan showroom:prune-orphan-media` | Xóa các ảnh thừa đã cũ hơn 24 giờ để tiết kiệm ổ cứng |
| `php artisan showroom:prune-orphan-media --force-recent` | Ép xóa cả ảnh thừa vừa tải lên dưới 24 giờ |
| `php artisan vehicle:sync` | Đồng bộ lại danh mục xe ô tô và mô tô mẫu mà không xóa bản ghi hiện có |

### C. Tác vụ Email, Nhắc lịch, AI & Kiểm tra lịch chạy
| Lệnh | Công dụng |
| --- | --- |
| `php artisan appointments:send-reminders` | Gửi ngay email nhắc lịch + tệp `.ics` cho các lịch hẹn trong 24 giờ tới |
| `php artisan appointments:cleanup-and-followup` | Quét hủy các lịch `pending` quá hạn > 24h và hẹn lịch gửi email cảm ơn + ước tính trả góp |
| `php artisan showroom:daily-summary` | Tạo và gửi ngay email báo cáo tổng kết vận hành cuối ngày tới hộp thư quản trị |
| `php artisan schedule:list` | Xem danh sách 8 lịch chạy tự động đã đăng ký cùng giờ chạy tiếp theo |
| `php artisan ai:sync-embeddings` | Đồng bộ vector tìm kiếm ngữ nghĩa cho sản phẩm, bài viết và tri thức Showroom |
| `php artisan ai:sync-embeddings --force` | Tái tạo lại toàn bộ vector từ đầu bất chấp dữ liệu cũ |
| `php artisan ai:prune-conversations --days=60` | Dọn lịch sử chat vãng lai cũ quá 60 ngày (bảo toàn 100% lịch sử chat gắn với khách đã đặt lịch) |
| Endpoint MCP | Server MCP `BmwShowroomServer` mở tra cứu xe & tri thức tại route `/mcp/showroom` (hoặc tên cục bộ `bmw-showroom`) |

### D. Kiểm thử tự động & Chuẩn hóa mã nguồn
| Lệnh | Công dụng |
| --- | --- |
| `php artisan test --compact` | Chạy toàn bộ bộ kiểm thử tự động (**202 tests / 3.218 assertions**) |
| `php artisan test --compact tests/Feature/Phase32TypographyDualCodesSalesPerformanceAndHelpTest.php` | Chạy test Phase 32 (Font chữ, Mã kép, Điều phối & Biểu đồ Sales, Nút `(?)`, Bộ lọc) |
| `php artisan test --compact tests/Feature/Phase31CrmStateMachineAndActionHubTest.php` | Chạy test Phase 31 (Quy trình trạng thái 1 chiều & Khối thao tác CRM) |
| `php artisan test --compact tests/Feature/Phase30UiRedesignAndCleanEmailsTest.php` | Chạy test Phase 30 (Giao diện Email sáng & Admin Cockpit) |
| `php artisan test --compact tests/Feature/Phase29PerformanceLocksRateLimitingStorageAndAiTest.php` | Chạy test Phase 29 (Giới hạn `<= 8 SQL queries`, khóa kho, giới hạn tốc độ, dọn ảnh, AI) |
| `php artisan test --compact --filter=ten_ham_test` | Lọc chạy nhanh đúng một bài test cụ thể |
| `vendor\bin\pint --dirty --format agent` | Tự động chỉnh sửa chuẩn trình bày code PHP cho các file vừa sửa |
| `npm run lint` | Kiểm tra và tự sửa lỗi ESLint trong JS/TS |
| `npm run format` / `npm run format:check` | Định dạng hoặc kiểm tra định dạng giao diện trong `resources/` bằng Prettier |

### E. Xử lý sự cố Cache & Hàng đợi (Queue Troubleshooting)
| Lệnh | Công dụng |
| --- | --- |
| `php artisan optimize:clear` | Xóa sạch toàn bộ cache (cấu hình, đường dẫn, giao diện, sự kiện, cache ứng dụng) sau khi sửa `.env` hoặc code |
| `php artisan optimize` | Tạo sẵn file cache cấu hình/route/view để tăng tốc khi chạy Production |
| `php artisan queue:restart` | Báo các tiến trình Worker tự khởi động lại để nhận code mới sau khi sửa Mailable / Listener / Job |
| `php artisan queue:failed` | Xem danh sách các tác vụ chạy ngầm bị lỗi và nguyên nhân |
| `php artisan queue:retry all` | Chạy lại toàn bộ các tác vụ vừa nằm trong danh sách lỗi |
| `php artisan queue:flush` | Xóa sạch lịch sử `failed_jobs` cũ sau khi đã xử lý xong |
| `php artisan event:list` | Kiểm tra danh sách Event & Listener đang hoạt động |

### F. Đẩy code & Cập nhật lên Server thực tế (Ví dụ với VPS dùng Docker)
```bash
# 1. Trên máy cá nhân — kiểm tra và đẩy code lên Git
git status
git add .
git commit -m "Update features"
git push origin main

# 2. Trên máy chủ Ubuntu VPS (ví dụ server riêng của bạn) — cập nhật thủ công từng bước
ssh user@your-server-ip
cd /path/to/bmw_laravel
git pull origin main
docker compose up -d --build
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app npm ci && docker compose exec app npm run build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan showroom:clean-and-sync-data
docker compose exec app php artisan optimize
docker compose exec app php artisan queue:restart
```
*(Nếu trên server bạn có viết sẵn script triển khai nhanh, ví dụ `./scripts/deploy.sh`, chỉ cần chạy trực tiếp script đó sau khi kiểm tra test pass ở máy cá nhân).*

---

## 8. Tài khoản mẫu để trải nghiệm

Sau khi nạp dữ liệu mẫu (`php artisan migrate --seed` hoặc `php artisan showroom:clean-and-sync-data`), có thể đăng nhập vào khu quản trị tại **`/login`**:

| Vai trò | Tên hiển thị | Email | Mật khẩu |
| --- | --- | --- | --- |
| **Quản Lý (`admin`)** | Admin BMW Showroom | `admin@bmw.com` | `12345678` |
| **Quản Lý (`admin`)** | Quản Lý 1 | `quanly1@bmw.com` | `12345678` |
| **Quản Lý (`admin`)** | Quản Lý Showroom | `thai@bmw.com` | `12345678` |
| **Sales (`sales`)** | Trần Quang Minh | `sales.minh@bmw.com` | `12345678` |
| **Sales (`sales`)** | Nguyễn Thùy Linh | `sales.linh@bmw.com` | `12345678` |
| **Sales (`sales`)** | Lê Anh Đức | `sales.duc@bmw.com` | `12345678` |

> Nên đổi mật khẩu ngay nếu triển khai công khai trên Internet. Các tài khoản nội bộ thêm mới từ trang `/admin/users` cần dùng email thuộc tên miền `@bmw.com` (hoặc cấu hình thêm vào biến `ADMIN_EMAILS` trong `.env`).

---

## 9. Các cấu hình quan trọng trong `.env`

- **Trợ lý AI Gemini:** `AI_ASSISTANT_ENABLED=true`, điền `GEMINI_API_KEY=` hoặc nhiều key xoay vòng qua `GEMINI_API_KEYS=` (`GEMINI_KEY_ROTATION=round_robin`). Nếu để trống, website vẫn hoạt động bình thường với câu trả lời dự phòng an toàn.
- **Tìm kiếm ngữ nghĩa RAG (tùy chọn):** `AI_EMBEDDINGS_ENABLED=true`, điền `CLOUDFLARE_ACCOUNT_ID=` và `CLOUDFLARE_API_TOKEN=` (hỗ trợ nhiều cặp `_1`, `_2` để tự chuyển khi hết hạn mức).
- **Email & Hạn mức Gmail:** `MAIL_MAILER=failover` (hoặc `log` khi chạy thử trên máy), `MAIL_SHOWROOM_ADMIN="advisor@bmw.com"`, `MAIL_CAMPAIGN_RATE_PER_MINUTE=20`, `MAIL_DAILY_SOFT_LIMIT=420`.

---

## 10. Lưu ý bản quyền

BMW, BMW Motorrad, logo, tên dòng xe và hình ảnh liên quan thuộc quyền sở hữu của **BMW Group**. Dự án này chỉ dùng cho mục đích học tập và nghiên cứu kỹ thuật, không đại diện cho thương hiệu BMW.
