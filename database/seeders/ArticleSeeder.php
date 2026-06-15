<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@bmw.com')->first();

        Article::query()
            ->where(function ($query): void {
                $query
                    ->whereRaw('LOWER(title) LIKE ?', ['%browser qa%'])
                    ->orWhereRaw('LOWER(slug) LIKE ?', ['%browser-qa%']);
            })
            ->update([
                'status' => Article::STATUS_DRAFT,
                'published_at' => null,
            ]);

        $articles = [
            [
                'title' => 'Ưu đãi mùa hè BMW 2026 cho khách hàng đặt lịch showroom',
                'category' => Article::CATEGORY_OFFERS,
                'cover_image' => 'images/cars/330i/lifestyle-showroom.png',
                'excerpt' => 'Cập nhật ưu đãi mùa hè dành cho khách hàng đặt lịch tư vấn, lái thử và cấu hình BMW trực tiếp tại showroom.',
                'body' => "## Trải nghiệm ưu đãi theo lịch hẹn\nKhách hàng đặt lịch trước được đội ngũ showroom chuẩn bị xe trưng bày, cấu hình tham khảo và tư vấn tài chính phù hợp với nhu cầu sử dụng.\n\n## Quyền lợi trong thời gian chương trình\nChương trình tập trung vào hỗ trợ tư vấn cá nhân, kiểm tra nhu cầu đổi xe, phụ kiện đi kèm và các lựa chọn bàn giao thuận tiện.\n\n## Cách tham gia\nKhách hàng chọn mẫu xe quan tâm trên catalog, gửi yêu cầu tư vấn hoặc báo giá, sau đó cố vấn showroom sẽ xác nhận khung giờ phù hợp.",
                'published_at' => now()->subDays(1),
            ],
            [
                'title' => 'Gói hỗ trợ tài chính khi sở hữu BMW mới',
                'category' => Article::CATEGORY_OFFERS,
                'cover_image' => 'images/cars/530i.png',
                'excerpt' => 'Các điểm cần chuẩn bị khi trao đổi phương án tài chính, đặt cọc và kế hoạch bàn giao BMW mới.',
                'body' => "## Chuẩn bị ngân sách sở hữu\nMột phương án tài chính tốt cần làm rõ ngân sách ban đầu, dòng tiền hàng tháng, thời hạn sở hữu dự kiến và nhu cầu đổi xe trong tương lai.\n\n## Tư vấn theo từng dòng xe\nSedan, SUV, xe điện và các mẫu M Performance có cấu trúc chi phí khác nhau, vì vậy showroom sẽ hỗ trợ so sánh theo cấu hình thực tế.\n\n## Minh bạch trước khi đặt xe\nKhách hàng nên yêu cầu báo giá chi tiết, thời gian giao xe dự kiến, điều kiện bảo hành và phụ kiện mong muốn trước khi xác nhận đặt cọc.",
                'published_at' => now()->subDays(2),
            ],
            [
                'title' => 'Chương trình lái thử và tư vấn cấu hình BMW tại showroom',
                'category' => Article::CATEGORY_SALES_PROGRAMS,
                'cover_image' => 'images/cars/bmw-m3-sedan/lifestyle-showroom.png',
                'excerpt' => 'Quy trình đặt lịch lái thử giúp khách hàng cảm nhận vận hành, khoang lái và công nghệ hỗ trợ người lái của BMW.',
                'body' => "## Lái thử có mục tiêu rõ ràng\nTrước buổi hẹn, khách hàng nên xác định nhu cầu chính như đi phố, đi xa, gia đình, hiệu năng hoặc trải nghiệm lái thể thao.\n\n## Tư vấn cấu hình sau lái thử\nSau khi trải nghiệm, cố vấn showroom sẽ gợi ý phiên bản, màu sắc, trang bị và phụ kiện dựa trên phản hồi thực tế của khách hàng.\n\n## Bước tiếp theo\nNếu mẫu xe phù hợp, khách hàng có thể yêu cầu báo giá, so sánh thêm phiên bản hoặc giữ lịch gặp cố vấn để hoàn thiện cấu hình.",
                'published_at' => now()->subDays(3),
            ],
            [
                'title' => 'Quy trình đặt xe BMW từ chọn cấu hình đến bàn giao',
                'category' => Article::CATEGORY_SALES_PROGRAMS,
                'cover_image' => 'images/cars/bmw-x5-m-competition/hero-front-three-quarter.png',
                'excerpt' => 'Tóm tắt các bước quan trọng trong hành trình chọn xe, xác nhận cấu hình, đặt cọc và nhận bàn giao BMW.',
                'body' => "## Chọn đúng nhu cầu trước khi chọn cấu hình\nKhách hàng nên bắt đầu bằng mục đích sử dụng, số người thường đi cùng, thói quen di chuyển và mức độ ưu tiên giữa tiện nghi, hiệu năng, công nghệ.\n\n## Xác nhận thông tin thương mại\nTrước khi đặt cọc, showroom cần thống nhất giá, màu xe, thời gian bàn giao dự kiến, phụ kiện và các hồ sơ liên quan.\n\n## Bàn giao tại showroom\nKhi nhận xe, khách hàng được hướng dẫn vận hành, kết nối My BMW, kiểm tra ngoại thất, nội thất và lịch bảo dưỡng đầu tiên.",
                'published_at' => now()->subDays(4),
            ],
            [
                'title' => 'Tuần lễ trải nghiệm BMW Performance tại showroom',
                'category' => Article::CATEGORY_SHOWROOM_EVENTS,
                'cover_image' => 'images/cars/bmw-m4-coupe/lifestyle-showroom.png',
                'excerpt' => 'Sự kiện dành cho khách hàng muốn khám phá phong cách vận hành thể thao và thiết kế M Performance.',
                'body' => "## Không gian dành cho người yêu cảm giác lái\nTuần lễ Performance tập trung vào thiết kế khí động học, vô-lăng, ghế ngồi, hệ dẫn động và các chi tiết giúp BMW khác biệt khi vận hành.\n\n## Hoạt động tại showroom\nKhách tham dự có thể xem xe trưng bày, đặt lịch tư vấn cấu hình, tìm hiểu phụ kiện M Performance và nhận lịch lái thử phù hợp.\n\n## Ai nên tham gia\nSự kiện phù hợp với khách hàng đang cân nhắc sedan thể thao, coupe, SUV hiệu năng cao hoặc muốn nâng cấp trải nghiệm lái hiện tại.",
                'published_at' => now()->subDays(5),
            ],
            [
                'title' => 'Sự kiện giới thiệu dải sản phẩm BMW mới',
                'category' => Article::CATEGORY_SHOWROOM_EVENTS,
                'cover_image' => 'images/cars/bmw-x3-m50-xdrive/lifestyle-showroom.png',
                'excerpt' => 'Showroom tổ chức tuần trưng bày các mẫu BMW mới, kết hợp tư vấn sản phẩm và đặt lịch trải nghiệm.',
                'body' => "## Dải sản phẩm được trưng bày theo nhu cầu\nKhách hàng có thể tham khảo sedan, SUV, xe điện, Motorrad và phụ kiện chính hãng trong cùng một không gian.\n\n## Trải nghiệm có cố vấn đồng hành\nĐội ngũ showroom giúp giải thích khác biệt giữa các dòng xe, gợi ý cấu hình và chuẩn bị lộ trình tư vấn sau sự kiện.\n\n## Đặt lịch trước để tối ưu thời gian\nViệc đặt hẹn giúp showroom chuẩn bị xe quan tâm, tài liệu cấu hình và khung giờ trao đổi riêng tư hơn.",
                'published_at' => now()->subDays(6),
            ],
            [
                'title' => 'Những điểm cần chú ý khi chọn BMW sedan cho gia đình',
                'category' => Article::CATEGORY_BMW_EXPERIENCE,
                'cover_image' => 'images/cars/330i/cockpit-interior.png',
                'excerpt' => 'Gợi ý đánh giá không gian, tiện nghi, vận hành và công nghệ an toàn khi chọn sedan BMW cho nhu cầu gia đình.',
                'body' => "## Không gian và thói quen sử dụng\nMột chiếc sedan gia đình cần cân bằng ghế ngồi, khoang hành lý, khả năng đi phố và sự thoải mái trong những chuyến xa.\n\n## Cảm giác lái vẫn là điểm khác biệt\nBMW sedan phù hợp với khách hàng muốn xe lịch lãm hằng ngày nhưng vẫn có phản hồi lái chính xác và nhiều cảm xúc.\n\n## Công nghệ hỗ trợ nên kiểm tra kỹ\nKhách hàng nên trải nghiệm màn hình, camera, hỗ trợ đỗ xe, điều hòa, kết nối điện thoại và các chế độ lái trong buổi xem xe.",
                'published_at' => now()->subDays(7),
            ],
            [
                'title' => 'Trải nghiệm khác biệt giữa BMW 3 Series, 5 Series và dòng M',
                'category' => Article::CATEGORY_BMW_EXPERIENCE,
                'cover_image' => 'images/cars/bmw-m5-touring/hero-front-three-quarter.png',
                'excerpt' => 'So sánh nhanh tinh thần sử dụng giữa 3 Series linh hoạt, 5 Series sang trọng và các mẫu M hiệu năng cao.',
                'body' => "## 3 Series cho người thích sự linh hoạt\nBMW 3 Series tạo cảm giác gọn, nhanh và phù hợp với khách hàng muốn cân bằng giữa di chuyển hằng ngày và cảm xúc lái.\n\n## 5 Series cho hành trình sang trọng hơn\n5 Series tập trung vào không gian, tiện nghi, công nghệ và sự điềm tĩnh trên những quãng đường dài.\n\n## Dòng M dành cho hiệu năng rõ rệt\nCác mẫu M ưu tiên sức mạnh, kiểm soát thân xe, âm thanh vận hành và thiết kế thể thao, phù hợp với người lái nhiều kinh nghiệm.",
                'published_at' => now()->subDays(8),
            ],
            [
                'title' => 'Lịch bảo dưỡng BMW và những mốc quan trọng',
                'category' => Article::CATEGORY_AFTERSALES,
                'cover_image' => 'images/cars/bmw-550e-xdrive-sedan/design-detail.png',
                'excerpt' => 'Những mốc bảo dưỡng cần lưu ý để xe BMW vận hành ổn định, an toàn và giữ giá trị sử dụng lâu dài.',
                'body' => "## Theo dõi lịch hẹn chủ động\nKhách hàng nên lưu lịch kiểm tra định kỳ, tình trạng lốp, dầu, phanh, lọc gió và các cảnh báo hiển thị trên xe.\n\n## Bảo dưỡng chính hãng giúp kiểm soát rủi ro\nKỹ thuật viên và phụ tùng chính hãng giúp đảm bảo xe được kiểm tra theo tiêu chuẩn phù hợp với từng dòng BMW.\n\n## Khi nào cần đặt lịch ngay\nNếu xe xuất hiện cảnh báo, âm thanh bất thường, rung lắc, giảm hiệu quả phanh hoặc điều hòa yếu, khách hàng nên đặt lịch kiểm tra sớm.",
                'published_at' => now()->subDays(9),
            ],
            [
                'title' => 'Phụ kiện chính hãng BMW giúp nâng cấp trải nghiệm lái',
                'category' => Article::CATEGORY_AFTERSALES,
                'cover_image' => 'images/accessories/tham-lot-san-m-performance/lifestyle-use.png',
                'excerpt' => 'Các nhóm phụ kiện đáng cân nhắc cho nội thất, bảo vệ xe, camera hành trình và phong cách M Performance.',
                'body' => "## Phụ kiện nên phù hợp với thói quen sử dụng\nKhách hàng thường xuyên đi xa có thể ưu tiên camera hành trình, thảm lót, phụ kiện chứa đồ hoặc các chi tiết bảo vệ nội thất.\n\n## Cá nhân hóa phong cách BMW\nNhóm M Performance giúp tăng cảm giác thể thao qua chi tiết carbon, mâm xe, vô-lăng hoặc các điểm nhấn ngoại thất.\n\n## Kiểm tra tương thích trước khi đặt hàng\nShowroom sẽ xác nhận phụ kiện phù hợp với đời xe, phiên bản và nhu cầu lắp đặt trước khi chốt đơn.",
                'published_at' => now()->subDays(10),
            ],
            [
                'title' => 'Cập nhật xe mới về showroom BMW',
                'category' => Article::CATEGORY_SHOWROOM_NEWS,
                'cover_image' => 'images/cars/hero.png',
                'excerpt' => 'Thông tin các mẫu xe mới, lịch trưng bày và cách đặt lịch xem xe trực tiếp tại showroom.',
                'body' => "## Xe mới được cập nhật theo từng đợt\nShowroom liên tục bổ sung xe trưng bày, xe lái thử và các cấu hình đang được khách hàng quan tâm.\n\n## Cách kiểm tra mẫu xe đang có\nKhách hàng có thể xem catalog public, mở trang chi tiết sản phẩm và gửi yêu cầu tư vấn để được xác nhận tình trạng xe.\n\n## Lợi ích khi đặt lịch xem xe\nĐặt lịch giúp showroom chuẩn bị xe, không gian tư vấn và tài liệu cấu hình trước khi khách hàng đến.",
                'published_at' => now()->subDays(11),
            ],
            [
                'title' => 'Không gian tư vấn và trải nghiệm BMW trực tuyến',
                'category' => Article::CATEGORY_SHOWROOM_NEWS,
                'cover_image' => 'images/cars/bmw-i4-m60-gran-coupe/lifestyle-showroom.png',
                'excerpt' => 'Showroom kết hợp catalog, landing page sản phẩm, so sánh và form tư vấn để hỗ trợ khách hàng trước khi đến trực tiếp.',
                'body' => "## Khám phá trước khi đến showroom\nKhách hàng có thể xem ảnh, thông số, CTA báo giá, so sánh xe và phụ kiện ngay trên website trước buổi hẹn.\n\n## Tư vấn trực tuyến giúp tiết kiệm thời gian\nThông tin từ form public giúp cố vấn hiểu nhu cầu trước, từ đó chuẩn bị cấu hình, giá và phương án trải nghiệm sát hơn.\n\n## Hành trình liền mạch\nTừ trang chủ, catalog, bài viết đến form tư vấn, toàn bộ luồng được thiết kế để đưa khách hàng đến cuộc hẹn showroom rõ ràng hơn.",
                'published_at' => now()->subDays(12),
            ],
        ];

        foreach ($articles as $article) {
            Article::updateOrCreate(
                ['slug' => Str::slug($article['title'])],
                [
                    'user_id' => $admin?->id,
                    'title' => $article['title'],
                    'category' => $article['category'],
                    'excerpt' => $article['excerpt'],
                    'body' => $article['body'],
                    'cover_image' => $article['cover_image'],
                    'status' => Article::STATUS_PUBLISHED,
                    'published_at' => $article['published_at'],
                    'seo_title' => $article['title'],
                    'seo_description' => $article['excerpt'],
                ],
            );
        }
    }
}
