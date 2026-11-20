<?php

namespace App\Support;

class AdminPageGuideRegistry
{
    /**
     * Retrieve contextual Vietnamese guide metadata for a given Admin route name.
     *
     * @return array{title: string, summary: string, bullets: array<int, string>}|null
     */
    public static function forRoute(?string $routeName): ?array
    {
        if (! is_string($routeName) || $routeName === '') {
            return null;
        }

        $guides = self::all();

        return $guides[$routeName] ?? null;
    }

    /**
     * Return all Admin page guides indexed by route name.
     *
     * @return array<string, array{title: string, summary: string, bullets: array<int, string>}>
     */
    public static function all(): array
    {
        return [
            'dashboard' => [
                'title' => 'Tổng quan điều hành Showroom',
                'summary' => 'Theo dõi nhanh lưu lượng khách đặt lịch, đơn phụ kiện và tình trạng tồn kho theo thời gian thực.',
                'bullets' => [
                    'Các thẻ KPI tổng hợp dữ liệu đã cache để tải tức thì.',
                    'Nhấp vào các mục cần xử lý gấp để chuyển thẳng sang bảng CRM tương ứng.',
                ],
            ],
            'admin.appointments.index' => [
                'title' => 'Điều phối Lịch hẹn Showroom',
                'summary' => 'Quản lý khách đặt lái thử, tư vấn xe mới và bàn giao xe theo quy trình một chiều.',
                'bullets' => [
                    'Mỗi lịch hẹn có Mã kép: #SĐT_STT để tra cứu theo khách và dd/mm/yyyy/NN để điều phối theo ngày.',
                    'Chuyển trạng thái theo đúng vòng đời: Chờ duyệt → Đã xác nhận → Hoàn thành hoặc Đã hủy.',
                    'Admin có thể điều phối hoặc bàn giao Sales phụ trách trực tiếp trong Quick Inspector Drawer.',
                ],
            ],
            'admin.service-bookings.index' => [
                'title' => 'Điều phối Dịch vụ Chăm sóc xe',
                'summary' => 'Theo dõi lịch bảo dưỡng, sửa chữa và chăm sóc xe định kỳ tại xưởng BMW.',
                'bullets' => [
                    'Tra cứu nhanh bằng SĐT, tên xe, Mã khách hàng (#SĐT_STT) hoặc Mã ngày (dd/mm/yyyy/NN).',
                    'Mở Drawer chi tiết để xem trước các email xác nhận, nhắc lịch và hậu mãi sẽ gửi cho khách.',
                ],
            ],
            'admin.accessory-orders.index' => [
                'title' => 'Quản lý Đơn hàng Phụ kiện',
                'summary' => 'Xử lý đơn đặt mua phụ kiện chính hãng kèm trừ và hoàn tồn kho tự động an toàn.',
                'bullets' => [
                    'Hủy đơn hàng sẽ tự động hoàn lại đúng số lượng tồn kho cho sản phẩm.',
                    'Tìm kiếm hỗ trợ cả mã đơn #ACC-ID, Mã khách hàng (#SĐT_STT) và Mã ngày (dd/mm/yyyy/NN).',
                ],
            ],
            'admin.accessory-orders.show' => [
                'title' => 'Chi tiết Đơn hàng Phụ kiện',
                'summary' => 'Kiểm tra thông tin giao hàng, nhân sự phụ trách, nhật ký trạng thái và xem trước email.',
                'bullets' => [
                    'Trạng thái đã Hoàn thành hoặc Đã hủy sẽ được khóa cố định để bảo toàn số liệu kho.',
                ],
            ],
            'admin.products.index' => [
                'title' => 'Kho Xe & Phụ kiện chính hãng',
                'summary' => 'Quản trị toàn bộ danh mục xe trưng bày, giá niêm yết và tồn kho phụ kiện.',
                'bullets' => [
                    'Dùng bộ lọc Tồn kho và Khoảng giá để kiểm tra nhanh các mặt hàng sắp hết hàng.',
                    'Mọi cập nhật giá hoặc trạng thái sẽ tự động làm mới cache trang Public.',
                ],
            ],
            'admin.products.create' => [
                'title' => 'Thêm sản phẩm mới vào kho',
                'summary' => 'Nhập thông số kỹ thuật, hình ảnh chuẩn và phân loại xe hoặc phụ kiện.',
                'bullets' => [
                    'Ảnh tải lên sẽ được kiểm tra định dạng và dọn dẹp tự động nếu thay thế.',
                ],
            ],
            'admin.products.edit' => [
                'title' => 'Cập nhật thông tin sản phẩm',
                'summary' => 'Điều chỉnh giá niêm yết, tồn kho, bộ ảnh và thông số kỹ thuật.',
                'bullets' => [
                    'Kiểm tra kỹ tồn kho phụ kiện trước khi lưu để tránh lệch số với đơn đang mở.',
                ],
            ],
            'admin.categories.index' => [
                'title' => 'Quản lý Dòng xe & Nhóm sản phẩm',
                'summary' => 'Tổ chức phân cấp các dòng xe BMW Series, M Performance và Phụ kiện.',
                'bullets' => [
                    'Chỉ xóa danh mục khi không còn sản phẩm hoạt động thuộc danh mục đó.',
                ],
            ],
            'admin.categories.create' => [
                'title' => 'Tạo Dòng xe / Danh mục mới',
                'summary' => 'Khai báo tên dòng xe, đường dẫn URL thân thiện và phân loại.',
                'bullets' => [
                    'Đường dẫn (slug) sẽ tự động chuẩn hóa tiếng Việt không dấu.',
                ],
            ],
            'admin.categories.edit' => [
                'title' => 'Chỉnh sửa Danh mục',
                'summary' => 'Cập nhật tên hiển thị và mô tả cho nhóm sản phẩm.',
                'bullets' => [
                    'Thay đổi sẽ đồng bộ ngay sang bộ lọc trên trang Public.',
                ],
            ],
            'admin.articles.index' => [
                'title' => 'Quản lý Tin tức & Tạp chí BMW',
                'summary' => 'Xuất bản bài viết ra mắt xe, cẩm nang chăm sóc và sự kiện showroom.',
                'bullets' => [
                    'Lọc nhanh bài viết theo chuyên mục, trạng thái xuất bản hoặc tìm kiếm tiêu đề.',
                ],
            ],
            'admin.articles.create' => [
                'title' => 'Soạn thảo Bài viết mới',
                'summary' => 'Xây dựng nội dung theo khối trực quan hoặc Markdown kết hợp trợ lý AI.',
                'bullets' => [
                    'Có thể chuyển qua lại giữa chế độ Khối Studio, Markdown và Xem trước công khai.',
                ],
            ],
            'admin.articles.edit' => [
                'title' => 'Chỉnh sửa Bài viết',
                'summary' => 'Cập nhật nội dung, ảnh đại diện và trạng thái phát hành.',
                'bullets' => [
                    'Kiểm tra tab Xem trước để đảm bảo bố cục hiển thị đồng nhất trên trang Public.',
                ],
            ],
            'admin.customers.index' => [
                'title' => 'Danh bạ Khách hàng & Phân bổ Sales',
                'summary' => 'Tổng hợp lịch sử đặt hẹn, đơn phụ kiện và điểm ưu tiên chốt đơn của từng khách hàng.',
                'bullets' => [
                    'Admin có thể bấm Chia đều hồ sơ chưa phân công để cân bằng tải cho đội Sales.',
                    'Nhấp Lọc theo Sales phụ trách hoặc Độ ưu tiên để ưu tiên gọi lại khách HOT.',
                ],
            ],
            'admin.ai-conversations.index' => [
                'title' => 'Lịch sử Hội thoại Trợ lý AI',
                'summary' => 'Theo dõi các phiên khách hàng trò chuyện với Trợ lý BMW AI và tỷ lệ chuyển đổi lịch hẹn.',
                'bullets' => [
                    'Dùng bộ lọc Mức độ khách (Hot/Warm/Cold) và Đã chốt lịch hẹn để tìm khách tiềm năng bị sót.',
                ],
            ],
            'admin.ai-conversations.show' => [
                'title' => 'Chi tiết Phiên Hội thoại AI',
                'summary' => 'Đọc lại toàn văn trao đổi, hàm tool đã gọi và đánh giá chất lượng tư vấn.',
                'bullets' => [
                    'Huy hiệu Intent và Lead Level cho biết nhu cầu chính của khách trong phiên chat.',
                ],
            ],
            'admin.users.index' => [
                'title' => 'Quản trị Nhân sự & Phân quyền',
                'summary' => 'Cấp tài khoản, điều chỉnh vai trò (Admin / Sales / Marketing) và theo dõi hiệu suất Sales.',
                'bullets' => [
                    'Bấm "Xem thống kê" trên từng nhân viên Sales để mở biểu đồ hiệu suất và danh sách hồ sơ.',
                    'Mọi thay đổi vai trò sẽ tự động làm mới danh sách chia lead tự động.',
                ],
            ],
            'admin.users.performance' => [
                'title' => 'Thống kê Hiệu suất Sales',
                'summary' => 'Phân tích số lượng hồ sơ được giao, tỷ lệ hoàn thành và doanh thu phụ kiện của nhân viên.',
                'bullets' => [
                    'Di chuột lên từng cột hoặc lát Donut để xem chi tiết số liệu theo thời gian thực.',
                    'Có thể lọc lại theo khoảng thời gian (7 ngày, 30 ngày, tháng này hoặc tùy chọn) và trạng thái.',
                ],
            ],
            'admin.sales.performance' => [
                'title' => 'Thống kê Hiệu suất Sales',
                'summary' => 'Phân tích số lượng hồ sơ được giao, tỷ lệ hoàn thành và doanh thu phụ kiện của nhân viên.',
                'bullets' => [
                    'Di chuột lên từng cột hoặc lát Donut để xem chi tiết số liệu theo thời gian thực.',
                    'Có thể lọc lại theo khoảng thời gian (7 ngày, 30 ngày, tháng này hoặc tùy chọn) và trạng thái.',
                ],
            ],
            'admin.site-settings.edit' => [
                'title' => 'Thiết lập Giao diện & Showroom',
                'summary' => 'Tùy chỉnh thông tin thương hiệu, banner trang chủ, hotline và địa chỉ showroom.',
                'bullets' => [
                    'Thay đổi có hiệu lực ngay sau khi lưu mà không cần khởi động lại hệ thống.',
                ],
            ],
        ];
    }
}
