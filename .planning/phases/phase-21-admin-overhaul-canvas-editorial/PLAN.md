# KẾ HOẠCH PHASE 21: ĐẠI TU TOÀN DIỆN ADMIN UI, TRANG ĐĂNG NHẬP, BÀI VIẾT PUBLIC & TRÌNH SOẠN THẢO CANVAS STUDIO

## 1. Mục tiêu cốt lõi
- Xóa bỏ cảm giác thô cứng, phẳng lì ở toàn bộ giao diện quản trị (Admin Portal), trang đăng nhập quản trị (Login), và trang chi tiết bài viết public.
- Chuyển đổi trải nghiệm viết bài sang trình soạn thảo trực quan **Canvas Studio Visual Block Editor**, quản lý nội dung theo các khối (Heading H2/H3, Đoạn văn, Hình ảnh & Caption, Trích dẫn/Highlight thông số) với Live Preview công khai và tab Markdown chuyên nghiệp.
- Tái cấu trúc trang chi tiết bài viết public thành layout báo chí thượng lưu (2-column luxury editorial) với Markdown render chuẩn xác, mục lục động (TOC) tự động sinh bằng Alpine.js, thẻ kêu gọi lái thử và danh sách bài liên quan.
- Tái thiết kế trang đăng nhập quản trị sang không gian điện ảnh (cinematic focus), bề mặt thẻ đa tầng `.bg-card-luxury`, quầng sáng ambient glow và viền sáng BMW Blue.
- Nâng cấp Admin CRM Shell và Dashboard: 4 thẻ thống kê luxury với icon SVG, bảng lịch hẹn và danh sách bài viết chuẩn phong cách BMW Modern Dark Luxury.
- Đảm bảo 100% giữ vững mốc **114 tests / 1147 assertions PASS**, tuyệt đối không phát sinh hồi quy (zero regression).

## 2. Phạm vi can thiệp (Zero Backend Mutation)
- `resources/css/app.css`: Nâng cấp typography và utility class cho `.article-content` (H2 blue accent bar, H3, blockquote, bullet list, ảnh có caption, liên kết).
- `resources/views/layouts/guest.blade.php`: Header thương hiệu BMW SHOWROOM Admin Portal, nền ambient showroom glow, bản quyền tiếng Việt.
- `resources/views/auth/login.blade.php`: Title Case labels, sleek inputs, gradient submit button BMW Blue.
- `resources/views/articles/show.blade.php`: Parse Markdown `{!! \Illuminate\Support\Str::markdown($article->body) !!}`, layout 2 cột, dynamic TOC sidebar, thẻ lái thử, bài liên quan.
- `resources/views/components/admin/sidebar-link.blade.php`: Active state với border-l-2 xanh `#1C69D4`, nền `#1C69D4]/15`, text Title Case trang nhã.
- `resources/views/components/admin-layout.blade.php`: Sidebar điều hướng, user profile footer, ambient canvas, breadcrumb header.
- `resources/views/admin/dashboard.blade.php`: 4 thẻ thống kê luxury với icon SVG, empty state trợ lý ảo với minh họa SVG.
- `resources/views/admin/articles/index.blade.php`: Bộ lọc tìm kiếm và bảng danh sách bài viết chuẩn luxury.
- `resources/views/admin/articles/_form.blade.php`: Canvas Studio Visual Block Editor với Alpine.js, 3 tabs (Khối Studio, Mã Markdown, Xem trước Live), đồng bộ 2 chiều vào textarea `name="body"`.

## 3. Quy trình nghiệm thu
1. Cấu hình CSS typography bài viết.
2. Thiết kế lại Guest Layout và Login Page.
3. Tái cấu trúc Article Show view.
4. Cập nhật Admin Layout, Sidebar link, Dashboard CRM, Article Index.
5. Triển khai Canvas Studio Editor trong `_form.blade.php`.
6. Chạy Pint định dạng code.
7. Chạy Vite build xác thực biên dịch assets.
8. Chạy toàn bộ 114 tests kiểm thử hồi quy.
