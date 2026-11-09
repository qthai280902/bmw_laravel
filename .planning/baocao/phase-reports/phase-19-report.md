# Báo Cáo Nghiệm Thu Phase 19 — Khắc Phục Triệt Để Lỗi Typography Tiếng Việt & Chuẩn Hóa Font Be Vietnam Pro

- **Ngày hoàn thành:** 2026-11-08 +07:00
- **Trạng thái:** **PASS 100%**
- **Phạm vi:** Frontend Typography, Tailwind CSS v4 `@theme` Configuration, Public Blade Views (`welcome`, `articles/index`, `articles/show`, `services`, `experiences`, `products/show`).

---

## 1. Nguyên Nhân Gốc Rễ (Root Cause Analysis)

1. **Lạm dụng `uppercase` kết hợp `tracking-widest` / `tracking-[0.2em]` trên văn bản tiếng Việt:**
   - Nhiều thẻ `<p>` (đoạn mô tả Hero, chú thích đặt cọc, nội dung dịch vụ/trải nghiệm) và hầu hết các tiêu đề dài nhiều dòng (`h1`, `h2`, `h3`, `h4`) bị gán cứng class `uppercase` cùng `tracking-widest` hoặc `tracking-[0.2em]`.
   - Khi hiển thị tiếng Việt có mật độ dấu phụ cao (`ư`, `ơ`, `â`, `ê` kết hợp dấu `huyền`, `sắc`, `hỏi`, `ngã`, `nặng`), việc ép chữ in hoa toàn bộ và giãn khoảng cách chữ quá rộng khiến các ký tự rời rạc, giảm nghiêm trọng tốc độ đọc (readability) và phá vỡ nhịp điệu thị giác.
2. **Đồng bộ pipeline nạp font `Be Vietnam Pro` trong Tailwind CSS v4:**
   - Cần đặt `@import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap');` ở ngay dòng 1 của `resources/css/app.css` (trước `@import 'tailwindcss';`) và khai báo tường minh `--font-sans: 'Be Vietnam Pro', ui-sans-serif, system-ui, sans-serif;` trong khối `@theme`, `:root` và `body`.

---

## 2. Chi Tiết Các Hạng Mục Đã Thực Hiện

### 2.1. Cấu hình `resources/css/app.css` (Tailwind CSS v4)
- Nhúng `@import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap');` tại dòng 1 trước `@import 'tailwindcss';`.
- Định nghĩa trong `@theme`:
  ```css
  --font-sans: 'Be Vietnam Pro', ui-sans-serif, system-ui, sans-serif;
  --font-display: 'Be Vietnam Pro', ui-sans-serif, system-ui, sans-serif;
  ```
- Thiết lập `:root` và `body` sử dụng `var(--font-sans)`, đồng thời chuẩn hóa `@layer base` cho `h1–h6` (`letter-spacing: -0.02em; line-height: 1.25;`) và `p` (`line-height: 1.625; letter-spacing: normal;`).

### 2.2. Chuẩn hóa Typography trên 6 File Giao Diện Public
| File | Thay đổi chính |
| :--- | :--- |
| `resources/views/articles/show.blade.php` | Gỡ bỏ `uppercase` khỏi tiêu đề bài viết `h1`, các thẻ `h2` trong nội dung bài viết, giá trị chuyên mục sidebar và tiêu đề bài viết liên quan `h2`. Áp dụng `leading-relaxed` cho đoạn tóm tắt (excerpt) và nội dung chính. |
| `resources/views/articles/index.blade.php` | Gỡ bỏ `uppercase` khỏi tiêu đề trang `h1` (`Tìm hiểu thêm`), thông báo trống `h2` và tiêu đề card bài viết `h2`. Áp dụng `leading-relaxed text-zinc-400` cho mô tả và excerpt. |
| `resources/views/welcome.blade.php` | Gỡ bỏ `uppercase` khỏi Hero `h1`, các tiêu đề section `h2` (`Dòng sản phẩm`, `Mẫu nổi bật`, `Tìm hiểu thêm`, `Từ khám phá đến tư vấn...`), tiêu đề card dòng xe `h3`, bài viết `h3` và quy trình showroom `h3`. Áp dụng `leading-relaxed` cho toàn bộ thẻ `<p>`. |
| `resources/views/services.blade.php` | Gỡ bỏ `uppercase` và `tracking-widest` / `tracking-[0.2em]` khỏi header `h2`, Hero `h1`, Hero `<p>`, các tiêu đề dịch vụ `h2`, đoạn văn `<p>` mô tả dịch vụ và footer `h3`. Chuyển về `tracking-tight` cho tiêu đề và `leading-relaxed` cho đoạn văn. |
| `resources/views/experiences.blade.php` | Gỡ bỏ `uppercase` và `tracking-widest` / `tracking-[0.2em]` khỏi header `h2`, Hero `h1`, Hero `<p>`, các tiêu đề trải nghiệm `h2`, đoạn văn `<p>` mô tả trải nghiệm và footer `h3`. Chuyển về `tracking-tight` và `leading-relaxed`. |
| `resources/views/products/show.blade.php` | Gỡ bỏ `uppercase` khỏi Hero `h1` (`$product->name`), toàn bộ tiêu đề `h2` & `h3` các phân khu (Tổng quan, Điểm nổi bật, Thiết kế, Công nghệ, Thư viện hình ảnh, Thông số kỹ thuật, Dịch vụ & Tài chính, Đặt lịch, Mẫu xe cùng phân khúc), tiêu đề modal `h4`, đoạn văn đặt cọc `<p>` và chú thích modal `<p>`. Áp dụng `tracking-tight` và `leading-relaxed`. |

---

## 3. Kết Quả Kiểm Chứng (Verification Logs)

- **Laravel Pint:** `php vendor/bin/pint --format agent` -> `{"tool":"pint","result":"passed"}`
- **Clear Cache:** `php artisan optimize:clear` -> `DONE` (config, cache, compiled, events, routes, views)
- **Vite Asset Build:** `npm run build` -> Thành công (`public/build/assets/app-izHYdINk.css` 82.20 kB, `public/build/assets/app-BM1UHsC0.js` 91.70 kB trong 1.24s)
- **Automated Test Suite:** `php artisan test` -> **114 passed (1147 assertions)** — PASS 100%, không gãy bất kỳ test nào.
