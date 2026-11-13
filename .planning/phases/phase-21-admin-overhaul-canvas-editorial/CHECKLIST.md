# CHECKLIST PHASE 21: ĐẠI TU TOÀN DIỆN ADMIN UI, TRANG ĐĂNG NHẬP, BÀI VIẾT PUBLIC & CANVAS STUDIO

## 1. CSS & Typography Bài viết
- [x] Thêm định dạng `.article-content h2` với thanh dọc xanh BMW Blue (`::before: 6px w, #1C69D4`).
- [x] Thêm định dạng `.article-content h3`, `.article-content p` với màu `text-zinc-300` và độ tương phản cao.
- [x] Thêm định dạng blockquote viền trái 3px `#1C69D4`, nền `#121215`, đổ bóng shadow.
- [x] Thêm định dạng danh sách (ul/ol) và chú thích ảnh (`<figcaption>`).

## 2. Trang Đăng nhập & Guest Layout
- [x] Bỏ viền gạch chân xanh thô ở logo, thay bằng biểu tượng hình thoi BMW nghiêng 45 độ và text Title Case `Hệ Thống Quản Trị Nội Bộ (Admin Portal)`.
- [x] Ứng dụng bề mặt thẻ `.bg-card-luxury` kết hợp thanh đón sáng phía trên.
- [x] Form controls với nhãn Title Case (`Tài khoản Email`, `Mật khẩu bảo mật`).
- [x] Nút submit gradient BMW Blue `#1C69D4` -> `#154fa0` bo góc 0px.
- [x] Chân trang bản quyền tiếng Việt: `© 2026 BMW Group Vietnam. Hệ thống quản trị đặc quyền.`

## 3. Trang Chi tiết Bài viết Public
- [x] Render nội dung bài viết qua `{!! \Illuminate\Support\Str::markdown($article->body) !!}`.
- [x] Tiêu đề H1 lớn, phân cấp rõ ràng, nhãn chuyên mục và ngày tháng thanh thoát.
- [x] Đoạn trích dẫn tóm tắt (Lead excerpt) đóng khung viền trái xanh nổi bật.
- [x] Bố cục 2 cột (minmax 760px nội dung chính + sidebar cố định).
- [x] Mục lục động (TOC) tự động trích xuất các thẻ H2 qua Alpine.js.
- [x] Thẻ kêu gọi đăng ký lái thử cùng chuyên gia.
- [x] Thẻ danh sách bài viết liên quan đồng điệu nhận diện thương hiệu.

## 4. Admin Shell & Dashboard CRM
- [x] Sidebar Link: Active state với border-l-2 `#1C69D4`, nền trong suốt xanh sang trọng.
- [x] Admin Layout: Sidebar tối giản, menu Title Case, footer nhân sự tinh tế.
- [x] Dashboard CRM: 4 thẻ thống kê luxury (Tổng lead, Hôm nay, Chờ xử lý, Hội thoại AI).
- [x] Empty state Trợ lý ảo: SVG icon + thông điệp "Chưa ghi nhận hội thoại mới từ Trợ lý ảo".
- [x] Bảng 10 lịch hẹn mới nhất và bảng quản trị bài viết chuẩn BMW Modern Dark Luxury.

## 5. Trình Soạn Thảo Canvas Studio
- [x] Thành phần Alpine.js `articleCanvasStudio()` quản lý mảng block linh hoạt.
- [x] Hỗ trợ 4 loại khối: Tiêu đề H2/H3, Đoạn văn, Hình ảnh & Chú thích, Trích dẫn/Thông số.
- [x] Đổi thứ tự khối (lên/xuống) và xóa khối với 1 click.
- [x] Toolbar thêm khối nhanh với icon trực quan.
- [x] 3 Tabs điều hướng: Khối Studio, Mã Markdown thô, Xem trước công khai (Live Preview).
- [x] Đồng bộ hai chiều liên tục vào textarea `name="body"` đảm bảo 100% tương thích HTTP request backend.

## 6. Kiểm Thử & Kiểm Soát Chất Lượng
- [x] Laravel Pint định dạng code đạt chuẩn PSR-12: PASSED.
- [x] Vite Build biên dịch CSS và JS sạch sẽ không lỗi: PASSED.
- [x] Bộ kiểm thử 114 tests / 1147 assertions đạt kết quả tuyệt đối: 100% PASS.
