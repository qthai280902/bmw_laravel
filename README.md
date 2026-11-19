<div align="center">
  <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/BMW.svg" alt="BMW Logo" width="92"/>

  <h1>BMW Showroom & CRM Lead-Gen Platform</h1>

  <p>
    Nền tảng BMW Showroom trực tuyến chuẩn <strong>Dark Luxury (0px)</strong> kết hợp <strong>CRM Lead-Gen 3 vai trò</strong>, <strong>AI Concierge (SSE Streaming, Vision, Tool Calling, Structured Output & Hybrid RAG)</strong>, <strong>MCP Server</strong>, phân hệ <strong>Dịch vụ Chăm sóc xe</strong>, <strong>Email Automation</strong> và kiến trúc chịu tải cao trên Laravel 12.
  </p>

  <p>
    <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20.svg?style=for-the-badge&logo=laravel" alt="Laravel 12" />
    <img src="https://img.shields.io/badge/PHP-8.4%20%2F%208.5-777BB4.svg?style=for-the-badge&logo=php" alt="PHP 8.4 / 8.5" />
    <img src="https://img.shields.io/badge/Livewire-v4.4-FB70A9.svg?style=for-the-badge&logo=livewire" alt="Livewire v4" />
    <img src="https://img.shields.io/badge/Tailwind_CSS-v4-38B2AC.svg?style=for-the-badge&logo=tailwind-css" alt="Tailwind CSS v4" />
    <img src="https://img.shields.io/badge/Alpine.js-v3-8BC0D0.svg?style=for-the-badge&logo=alpine.js" alt="Alpine.js" />
    <img src="https://img.shields.io/badge/Gemini_AI_%2B_MCP-4285F4.svg?style=for-the-badge&logo=google" alt="Gemini AI + MCP" />
    <img src="https://img.shields.io/badge/Tests-189%20Passed%20(2645%20Assertions)-10B981.svg?style=for-the-badge" alt="Tests 189 Passed" />
  </p>
</div>

---

## Tổng Quan Hệ Thống

`bmw_laravel` mô phỏng toàn diện quy trình trưng bày, tư vấn và chăm sóc khách hàng của **BMW Showroom Vietnam** theo mô hình **100% không yêu cầu tài khoản khách hàng** (khách hàng đặt lịch, gửi yêu cầu, quản lý lịch qua Signed URL bảo mật và tương tác AI mượt mà không cần đăng nhập):

- **Public Showroom & Catalog**: Giới thiệu Ô tô BMW, BMW Motorrad, Phụ kiện chính hãng, So sánh đa mẫu xe (`/compare`), Trang chi tiết landing page có Scrollspy và hệ thống ảnh cục bộ.
- **Đặt lịch & Dịch vụ Chăm sóc xe (`/booking`, `/services`, `/dich-vu`)**: Hỗ trợ đầy đủ Lái thử (`test_drive`), Xem xe, Báo giá, Tư vấn chuyên viên và các gói Bảo dưỡng / Phủ Ceramic / Chăm sóc nội thất / Chẩn đoán ISTA; tích hợp **Guest Self-Service Portal** qua Signed URL 14 ngày (`/appointments/{appointment}/manage`) kèm tệp lịch `.ics` (RFC 5545).
- **Đặt hàng Phụ kiện Nguyên tử**: Tích hợp `Cache::lock` + `DB::transaction` + `lockForUpdate()` chống bán lố và tự động hoàn kho đúng 1 lần khi hủy đơn.
- **AI Showroom Concierge & MCP Server**: Trợ lý AI dùng `laravel/ai` (`Gemini` Multi-Key Rotation & Cooldown), hỗ trợ **SSE Streaming**, **Vision (nhận diện ảnh xe/phụ kiện)**, **Hybrid RAG** (Cloudflare Workers AI `@cf/google/embeddinggemma-2` + JSON Knowledge Base), **7 AI Tools** (tính trả góp thực từ DB, kiểm tra tồn kho, liệt kê gói dịch vụ...), **Structured Output JSON Schema**, và **Laravel MCP Server** (`/mcp/showroom`).
- **CRM & Admin Portal (3 vai trò RBAC)**: Phân quyền `admin` / `sales` / `marketing`, Dashboard tổng hợp song song, **Quick Inspector Drawer** có Timeline tương tác 2 chiều & bàn giao ngữ cảnh AI, Chuông thông báo Real-time (`NotificationBell`), Nhật ký kiểm toán (`AdminActivitySubscriber`), và Hệ thống Email **BMW Concierge** chịu lỗi (`failover`, `GmailQuotaGuard`, mã hóa hàng đợi `ShouldBeEncrypted`).

---

## Kiến Trúc & Công Nghệ Cốt Lõi

| Lớp | Công nghệ & Mô hình triển khai |
| --- | --- |
| **Backend** | PHP `8.4 / 8.5`, Laravel `12.x`, Livewire `4.4+`, Form Requests, Eloquent Observers, Event Subscribers |
| **Frontend** | Blade Components, Tailwind CSS `v4`, Alpine.js `v3` (Single-Alpine ESM), Vite `6.x`, Custom 0px Dropdown (`<x-ui.custom-select>`) |
| **AI & Search** | `laravel/ai` (`^0.7.2`), `laravel/mcp` (`^0.6`), Google Gemini Multi-Key Pool, Cloudflare Workers AI Embeddings (`768 dims`) |
| **Hiệu năng & Đồng thời** | `Cache::flexible` (Stale-While-Revalidate), `Cache::lock` (Atomic Inventory), `ShowroomConcurrency` (`sync`-safe / `process`) |
| **Mail & Queue** | `failover` mailer (`smtp -> log`), `ShouldBeEncrypted`, `RateLimited('gmail-campaign')`, `GmailQuotaGuard`, 8 tác vụ Scheduler (`Asia/Ho_Chi_Minh`) |
| **Database & Infra** | MySQL / PostgreSQL (production), SQLite `:memory:` (testing), Docker Compose (`app`, `queue`, `scheduler` + `json-file` log rotation) |

---

## Các Phân Hệ Nổi Bật

### 1. Public Showroom, So Sánh & Phân Hệ Dịch Vụ
- **Livewire Interactive UX**: `CatalogBrowser` và `ArticleHub` lọc/tìm kiếm/phân trang không tải lại trang (`WithBmwPagination` chuẩn `0px`), đồng bộ `Compare` và hiệu ứng scroll reveal sau Livewire morph.
- **So sánh xe (`/compare?ids=1,2`)**: Chuẩn hóa ID, loại bỏ trùng lặp và tự động chặn phụ kiện khỏi bảng so sánh xe.
- **Dịch vụ chăm sóc xe (`/services`, `/dich-vu`)**: Trình bày 4 gói dịch vụ chính hãng (Bảo dưỡng định kỳ CBS, Phủ Ceramic 9H+, Chăm sóc nội thất Merino & khoang máy, Kiểm tra điện tử BMW ISTA) liên kết pre-fill sang `BookingFormWizard`.

### 2. Đặt Lịch, Đơn Phụ Kiện & Khóa Kho Nguyên Tử
- **Đặt lịch đa luồng (`/booking`)**: Hỗ trợ `test_drive`, `quote`, `consult`, `advisor_meeting`, `trade_in`, `maintenance`, `detailing`, `car_wash`; tự động chấm mức độ tiềm năng (`HOT` / `WARM` / `COLD` Lead) và liên kết `ai_visitor_id`.
- **Quản lý lịch cho khách (Không cần đăng nhập)**: Khách nhận Signed URL 14 ngày trong email để tự **Xác nhận**, **Đổi ngày/giờ**, hoặc **Hủy lịch** kèm file `.ics`.
- **Chống bán lố phụ kiện (`AccessoryStockService`)**: Khóa nguyên tử `Cache::lock("accessory-stock:{$id}", 10)->block(5)` + `lockForUpdate()`, trả `HTTP 409` nếu hết hàng/kẹt lock, và tự hoàn kho idempotent khi đơn chuyển trạng thái `cancelled`.

### 3. AI Showroom Concierge, Hybrid RAG & MCP Server
- **SSE Streaming & Vision**: `POST /ai/showroom-assistant/stream` & `/api/ai/assistant/stream` trả từng token mượt mà kèm fallback JSON tự động; hỗ trợ đính kèm hình ảnh xe/phụ kiện để nhận diện từ catalog thật.
- **Hybrid Memory & Cross-Tab Sync**: Lưu cookie `bmw_ai_visitor_id` 365 ngày (`HttpOnly`), đồng bộ đa tab qua `BroadcastChannel` và khôi phục hội thoại qua `/history`.
- **Tool Calling & Structured Output**:
  - `ShowroomAiToolkit`: `calculate_financial_estimate`, `check_accessory_stock`, `list_service_packages` cùng `search_catalog`, `compare_vehicles`, `search_articles_and_offers`, `search_knowledge_base`.
  - `StructuredOutputParser` + `ShowroomStructuredAssistant`: chuẩn hóa JSON Schema (`intent`, `lead_level`, `recommended_product_ids`, `recommended_service_package`, `prefill_booking`), lọc nghiêm ngặt ID sản phẩm active có thật trong DB.
- **Laravel MCP Server**: Đăng ký `BmwShowroomServer` tại `/mcp/showroom` (và lệnh Artisan `mcp:start bmw-showroom`) phục vụ client AI ngoài.

### 4. Admin CRM, Phân Quyền 3 Vai Trò & Kiểm Soát Truy Vấn
- **RBAC 3 vai trò (`admin`, `sales`, `marketing`)**:
  - `admin`: Toàn quyền hệ thống, quản lý nhân sự & xem Nhật ký kiểm toán (`AdminActivityLog`).
  - `sales`: Dashboard, Lịch hẹn Tư vấn/Lái thử, Lịch Dịch vụ, Đơn Phụ kiện, Hội thoại AI, Nhận chuông `HOT LEAD` & cảnh báo mail.
  - `marketing`: Quản lý Danh mục, Sản phẩm, Bài viết CMS, Chiến dịch Email khuyến mãi, Cài đặt giao diện.
- **Quick Inspector Drawer & Timeline 2 Chiều**: Không làm phình bảng CRM (xóa bỏ cuộn ngang desktop), bấm nút Con mắt để mở Drawer xem chi tiết, Timeline trao đổi 2 chiều, và ngữ cảnh + badge phân loại AI (`INTENT`, `LEAD`, `TOOL`).
- **Ngân sách SQL nghiêm ngặt**: Giữ vững **`<= 8 SQL queries`** (kể cả khi cache lạnh) trên `/admin/appointments`, `/admin/service-bookings`, `/admin/accessory-orders`.

### 5. Email Resilience, Task Scheduling & Quản Trị Tài Nguyên
- **BMW Concierge Email Suite**: 6+ mẫu thư phân tách rõ vai trò (*Hệ thống tiếp nhận*, *Xác nhận chính thức + Thẻ VIP + Brochure HTML*, *Nhắc lịch 24h*, *Hậu mãi sau lái thử kèm bảng ước tính trả góp 20%/60 tháng*, *Cảnh báo HOT LEAD ưu tiên cao*, *Báo cáo tổng kết cuối ngày 18:00*), đầy đủ bản `text:` và inline images.
- **Failover & Quota Shield**: Tự động cảnh báo lên chuông CRM nếu `failover` rơi sang `log`; `GmailQuotaGuard` (`420 email/ngày` theo `Asia/Ho_Chi_Minh`) tự tạm dừng chiến dịch marketing khi chạm ngưỡng mềm để bảo toàn suất cho thư đặt lịch & HOT LEAD; bảo vệ API AI Chat (`15/phút`, `60/giờ`) và Form Public bằng `RateLimiter`.
- **Quản trị Media & Docker Hygiene**: `LocalMediaManager` + Observers tự dọn ảnh không còn tham chiếu trong `products/` & `articles/`; lệnh `showroom:prune-orphan-media` (`Isolatable`, cửa sổ an toàn 24 giờ) chạy mỗi Chủ nhật `04:00`; xoay vòng log Docker `json-file` (`10m` x `3`) và Laravel `daily` (14 ngày).

---

## Cấu Trúc Thư Mục Nổi Bật

```text
app/
├── Ai/
│   ├── Agents/          # ShowroomAssistant, ShowroomStructuredAssistant, LeadClassifier, Editorial, Extractor
│   └── Tools/           # 7 Laravel AI Tools (Catalog, Compare, Articles, RAG, Finance, Stock, Service)
├── Console/Commands/    # ai:prune-conversations, ai:sync-embeddings, appointments:*, showroom:daily-summary, showroom:prune-orphan-media
├── Events/ & Listeners/ # PromotionCampaignDispatched (ShouldBeEncrypted + RateLimited), CatalogEmbeddingSyncRequested (Debounced), AdminActivitySubscriber
├── Http/
│   ├── Controllers/     # Public, Admin/*, Ai/*
│   ├── Middleware/      # AdminMiddleware, EnsureUserHasRole
│   └── Requests/        # Shared validation rules (Web + Livewire)
├── Livewire/            # CatalogBrowser, ArticleHub, BookingFormWizard, AccessoryOrderCalculator, Admin/*
├── Mail/                # BMW Concierge templates (HTML + plain-text + Brochure/ICS attachments)
├── Mcp/                 # BmwShowroomServer & MCP Tools
├── Observers/           # ProductObserver, ProductImageObserver, ArticleObserver, CategoryObserver, AppointmentObserver
└── Services/
    ├── Ai/              # ShowroomAssistantService, ShowroomAiToolkit, StructuredOutputParser, GeminiKeyPool, Hybrid RAG
    ├── Cache/           # ShowroomCacheService (Cache::flexible)
    ├── Concurrency/     # ShowroomConcurrency (sync-safe wrapper)
    ├── Crm/             # InteractionTimelineRecorder
    ├── Inventory/       # AccessoryStockService (Cache::lock + lockForUpdate)
    ├── Mail/            # GmailQuotaGuard
    └── Media/           # LocalMediaManager

.planning/
├── STATE.md
├── ROADMAP.md
├── kientruc/            # Tài liệu kiến trúc AI, CRM, Security, Testing
└── baocao/phase-reports/
```

---

## Cài Đặt & Chạy Dự Án

### Yêu cầu môi trường
- **PHP**: `>= 8.4` (đã kiểm thử ổn định trên `PHP 8.4` & `8.5`)
- **Composer**: `2.x`
- **Node.js / npm**: `Node >= 20`
- **Database**: MySQL `8.0+` hoặc PostgreSQL (SQLite `:memory:` cho test)

### 1. Cài đặt Local
```bash
git clone https://github.com/qthai280902/bmw_laravel.git
cd bmw_laravel

composer install
npm ci

cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
npm run build
```

> Trên Windows PowerShell: thay `cp .env.example .env` bằng `Copy-Item .env.example .env`.

### 2. Chạy Local Development
```bash
# Chạy đồng thời server, queue worker, pail logs & vite (theo composer.json)
composer run dev
```
Hoặc chạy từng dịch vụ riêng lẻ:
```bash
php artisan serve
php artisan queue:work --queue=default,notifications
php artisan schedule:work
npm run dev
```

### 3. Chạy Production với Docker Compose
```bash
docker compose up -d --build
```
`docker-compose.yml` khởi chạy đồng thời 3 service dùng chung image & cấu hình xoay vòng log `json-file` (`max-size: 10m`, `max-file: 3`):
- `app`: Web application (`php-fpm` / web server).
- `queue`: Laravel Queue Worker xử lý mail & đồng bộ vector AI.
- `scheduler`: `php artisan schedule:work` chạy 8 tác vụ định kỳ theo múi giờ `Asia/Ho_Chi_Minh`.

---

## Biến Môi Trường Quan Trọng (`.env.example`)

```env
# AI Concierge & Multi-Key Rotation
AI_ASSISTANT_ENABLED=true
AI_ASSISTANT_PROVIDER=gemini
AI_ASSISTANT_MODEL=gemini-2.5-flash
GEMINI_API_KEY=
GEMINI_API_KEYS=
GEMINI_KEY_COOLDOWN_SECONDS=120
GEMINI_KEY_ROTATION=round_robin

# Hiệu năng, Concurrency & Rate Limit
SHOWROOM_CONCURRENCY_DRIVER=process
AI_CHAT_RATE_LIMIT_PER_MINUTE=15
AI_CHAT_RATE_LIMIT_PER_HOUR=60

# Mail Resilience & Gmail Quota Guard
MAIL_MAILER=failover
MAIL_FAILOVER_MAILERS=smtp,log
MAIL_SHOWROOM_ADMIN=admin@bmw.com
MAIL_CAMPAIGN_RATE_PER_MINUTE=20
MAIL_DAILY_SOFT_LIMIT=420

# Logging Hygiene
LOG_CHANNEL=daily
LOG_DAILY_DAYS=14
```

---

## Lịch Tác Vụ Tự Động (`php artisan schedule:list`)

| Tác vụ | Lịch chạy (`Asia/Ho_Chi_Minh`) | Mô tả |
| --- | --- | --- |
| `ai:prune-conversations` | Hàng ngày `03:30` | Dọn phiên chat AI cũ quá hạn lưu trữ |
| `appointments:send-reminders` | Mỗi giờ (`0 * * * *`) | Gửi email nhắc lịch trước 24h cho khách |
| `appointments:cleanup-and-followup` | Mỗi giờ (`07:00–21:00`) | Hủy lịch treo quá 24h & gửi thư hậu mãi sau lái thử |
| `showroom:daily-summary` | Hàng ngày `18:00` | Gửi báo cáo tổng kết vận hành showroom cuối ngày |
| `ai:sync-embeddings` | Hàng ngày `02:00` | Đồng bộ vector RAG (Chạy nền) |
| `prune-read-admin-notifications` | Chủ nhật `03:00` | Xóa thông báo CRM đã đọc quá 30 ngày |
| `queue:prune-failed --hours=168` | Chủ nhật `03:15` | Dọn failed jobs quá 7 ngày |
| `showroom:prune-orphan-media` | Chủ nhật `04:00` | Dọn tệp ảnh mồ côi (`products/`, `articles/`) quá 24h |

---

## Tài Khoản Demo (Sau Khi Chạy `php artisan migrate --seed`)

Đăng ký công khai đã được tắt; seeder mặc định tạo sẵn các tài khoản quản trị trong `DatabaseSeeder`:

| Vai trò | Email | Mật khẩu |
| --- | --- | --- |
| Admin | `admin@bmw.com` | `12345678` |
| Quản lý | `quanly1@bmw.com` | `12345678` |
| Quản lý | `thai@bmw.com` | `12345678` |

> Vui lòng đổi mật khẩu ngay sau khi triển khai lên môi trường staging/production.

---

## Kiểm Thử & Chất Lượng Mã Nguồn

### Bộ lệnh xác minh chuẩn
```bash
php artisan optimize:clear
vendor/bin/pint --test
npm run build
php artisan test --compact
```

Trên Windows PowerShell:
```powershell
php artisan optimize:clear; vendor\bin\pint --dirty --format agent; npm run build; php artisan test --compact
```

### Trạng thái kiểm thử gần nhất (Phase 29)
- `php artisan test --compact`: **189 passed (2645 assertions)** trong **21.18s**
- `vendor\bin\pint --dirty --format agent`: **PASS**
- `npm run build`: **PASS** (`55 modules transformed`)
- `php artisan schedule:list` & `php artisan showroom:prune-orphan-media --dry-run`: **PASS**

---

## Bảo Mật & Lưu Ý Thương Hiệu

- Không commit `.env` (`git ls-files .env` trả về rỗng); `.env.example` chỉ chứa placeholder.
- Không truyền dữ liệu nhạy cảm của CRM/Admin vào AI prompt; toàn bộ test tự động fake AI Gateway và Mailer, không gọi API ngoài.
- Toàn bộ tên xe, logo và hình ảnh **BMW / BMW Motorrad** được sử dụng cho mục đích học tập, nghiên cứu kiến trúc Laravel E-commerce, CRM và AI Assistant; không sử dụng cho mục đích thương mại chính thức nếu chưa có quyền sở hữu thương hiệu phù hợp.
