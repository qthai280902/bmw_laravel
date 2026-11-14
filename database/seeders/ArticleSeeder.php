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
            [
                'title' => 'BMW i4 M60 Gran Coupe: Khi DNA Đường Đua M Hòa Nhịp Kỷ Nguyên Thuần Điện',
                'slug' => 'bmw-i4-m60-gran-coupe-dna-duong-dua-m-thuan-dien',
                'category' => Article::CATEGORY_BMW_EXPERIENCE,
                'cover_image' => 'images/cars/bmw-i4-m60-gran-coupe/hero-front-three-quarter.png',
                'excerpt' => 'Sự kết hợp táo bạo giữa dáng vẻ Gran Coupe 4 cửa thanh lịch, hệ dẫn động hai mô-tơ điện xDrive đầy uy lực và khoang lái số hóa BMW Curved Display mở ra chuẩn mực cảm xúc mới cho kỷ nguyên di chuyển không phát thải.',
                'seo_title' => 'BMW i4 M60 Gran Coupe | Trải nghiệm hiệu năng thuần điện M Performance',
                'seo_description' => 'Khám phá thiết kế Gran Coupe khí động học, khả năng bứt tốc tức thì và khoang lái BMW Curved Display trên mẫu xe thuần điện BMW i4 M60 Gran Coupe tại BMW Showroom.',
                'body' => "## Ngôn ngữ thiết kế Gran Coupe đậm chất khí động học\n\nBMW i4 M60 Gran Coupe phá vỡ định kiến rằng xe thuần điện phải đánh đổi cá tính thẩm mỹ lấy hiệu suất khí động học. Sở hữu tỷ lệ thân xe trường dài đặc trưng của dòng Coupe 4 cửa, đường mái dốc mượt mà về phía đuôi cùng lưới tản nhiệt quả thận kép khép kín viền M đặc trưng, chiếc xe toát lên thần thái đĩnh đạc ngay cả khi đứng yên.\n\n![Diện mạo thể thao đầy cuốn hút của BMW i4 M60 Gran Coupe](/images/cars/bmw-i4-m60-gran-coupe/side-profile.png)\n\nCác chi tiết khí động học tinh xảo như hốc hút gió cản trước sắc lẹm, gương chiếu hậu M thiết kế tối ưu luồng gió và bộ mâm hợp kim kích thước lớn không chỉ tạo điểm nhấn thị giác mà còn giúp tối ưu hóa quãng đường di chuyển trên mỗi lần sạc đầy.\n\n## Sức mạnh tức thì từ hệ dẫn động điện M xDrive\n\nKhác với động cơ đốt trong cần thời gian đạt vòng tua tối ưu, hệ truyền động điện trên BMW i4 M60 Gran Coupe giải phóng mô-men xoắn cực đại ngay khoảnh khắc người lái chạm chân ga. Sự phối hợp nhịp nhàng giữa hai mô-tơ điện đặt ở cầu trước và cầu sau giúp phân bổ lực kéo biến thiên thông minh theo từng mili-giây.\n\n> \"Sự tĩnh lặng tuyệt đối của khoang cabin thuần điện kết hợp cùng độ bám đường sắc bén đặc trưng của bộ phận BMW M tạo nên một trải nghiệm cầm lái đầy mê hoặc trên cả phố thị lẫn cao tốc.\"\n\n### Những giá trị vận hành nổi bật trên BMW i4 M60\n\n- **Phân bổ trọng lượng tối ưu**: Cụm pin điện áp cao được đặt phẳng dưới sàn xe giúp hạ thấp trọng tâm đáng kể, mang lại sự ổn định vượt trội khi vào cua ở tốc độ cao.\n- **Hệ thống treo thích ứng M**: Tự động điều chỉnh độ cứng giảm xóc theo điều kiện mặt đường và chế độ lái (*Comfort*, *Sport*, *Eco Pro*).\n- **Âm thanh vận hành độc bản**: Hiệu ứng âm thanh mô phỏng được tinh chỉnh riêng cho dòng xe điện hiệu năng cao, giữ trọn cảm xúc kết nối giữa người lái và cỗ máy.\n\n## Khoang lái số hóa BMW Curved Display và đặc quyền hậu mãi\n\nBước vào bên trong, người lái được chào đón bởi màn hình cong nguyên khối **BMW Curved Display** hướng nhẹ về phía ghế lái theo triết lý *Driver-Oriented* kinh điển. Hệ điều hành BMW iDrive thế hệ mới cho phép điều khiển trực quan qua cảm ứng, núm xoay trung tâm hoặc ra lệnh giọng nói tự nhiên.\n\n![Không gian vận hành đậm chất công nghệ trên các dòng xe BMW thế hệ mới](/images/cars/bmw-i4-m60-gran-coupe/cockpit-interior.png)\n\nTại BMW Showroom, mẫu xe thuần điện BMW i4 M60 Gran Coupe hiện có mức giá niêm yết **4.599.000.000 VNĐ**, đi kèm chính sách bảo hành chính hãng 3 năm không giới hạn số km cho xe và **8 năm hoặc 160.000 km** cho cụm pin cao áp. Quý khách có thể [xem hồ sơ chi tiết BMW i4 M60](/catalog/bmw-i4-m60-gran-coupe) hoặc [đăng ký lái thử trực tiếp](/booking?type=test_drive) ngay hôm nay.",
                'published_at' => now()->subDays(3)->subHours(1),
            ],
            [
                'title' => 'BMW 330i Sedan hay BMW 530i Sedan: Lựa Chọn Nào Xứng Tầm Phong Thái Của Bạn?',
                'slug' => 'so-sanh-bmw-330i-sedan-va-bmw-530i-sedan-chon-xe-nao',
                'category' => Article::CATEGORY_BMW_EXPERIENCE,
                'cover_image' => 'images/cars/330i/hero-front-three-quarter.png',
                'excerpt' => 'Cùng mang trong mình tinh thần vận hành chuẩn mực của thương hiệu xứ Bavaria, nhưng BMW 330i Sedan và BMW 530i Sedan lại đại diện cho hai cá tính riêng biệt: một bên sắc sảo, linh hoạt đầy hứng khởi; một bên bề thế, tĩnh tại chuẩn doanh nhân.',
                'seo_title' => 'So sánh BMW 330i Sedan và BMW 530i Sedan | Tư vấn từ BMW Showroom',
                'seo_description' => 'Đặt lên bàn cân chi tiết thiết kế, không gian nội thất, cảm giác lái và giá bán giữa BMW 330i Sedan và BMW 530i Sedan tại BMW Showroom.',
                'body' => "## Hai biểu tượng sedan làm nên tên tuổi BMW\n\nTrong dải sản phẩm của BMW, 3 Series và 5 Series luôn giữ vị thế trụ cột nhờ sự cân bằng hoàn hảo giữa cảm giác lái thể thao và tính tiện dụng hằng ngày. Tại BMW Showroom, **BMW 330i Sedan** (niêm yết **2.499.000.000 VNĐ**) và **BMW 530i Sedan** (niêm yết **3.199.000.000 VNĐ**) là hai phiên bản nhận được nhiều sự quan tâm nhất từ khách hàng yêu thích dòng sedan hạng sang.\n\n![BMW 330i Sedan với tỷ lệ thân xe gọn gàng và sắc sảo](/images/cars/330i/hero-front-three-quarter.png)\n\n## BMW 330i Sedan: Nhịp đập thể thao đầy phấn khích\n\nĐược mệnh danh là chuẩn mực của dòng sedan thể thao cỡ nhỏ, BMW 330i Sedan chinh phục những chủ nhân trẻ trung, yêu thích tự mình cầm lái mỗi ngày.\n\n- **Phản hồi vô-lăng sắc bén**: Thân xe gọn gàng cùng hệ thống treo tinh chỉnh thể thao giúp 330i xoay trở linh hoạt trong đô thị đông đúc và mang lại cảm giác kết nối mặt đường chân thực trên những cung đường đèo.\n- **Thiết kế M Sport cá tính**: Các đường gân dập nổi sắc nét trên nắp ca-pô kết hợp cùng cụm đèn pha LED thích ứng tạo diện mạo năng động, hiện đại.\n\n## BMW 530i Sedan: Sự bề thế và tĩnh tại của doanh nhân thành đạt\n\nNếu 330i hướng tới sự phấn khích thuần túy sau vô-lăng, thì BMW 530i Sedan lại nâng tầm trải nghiệm sang trọng cho cả người lái lẫn hành khách phía sau.\n\n![BMW 530i Sedan mang phong thái sang trọng và đĩnh đạc](/images/cars/bmw-530i-sedan/hero-front-three-quarter.png)\n\n### Điểm khác biệt tạo nên đẳng cấp của 530i Sedan\n\n- **Không gian khoang hành khách rộng rãi**: Chiều dài cơ sở lớn hơn mang lại khoảng để chân thoáng đãng cho hàng ghế sau, phù hợp cho các chuyến công tác dài ngày hoặc tiếp đón đối tác quan trọng.\n- **Khả năng cách âm và độ êm ái vượt trội**: Hệ thống treo được tinh chỉnh thiên về sự đầm chắc, triệt tiêu tối đa dao động mặt đường nhưng vẫn giữ được độ chính xác đặc trưng của BMW khi tăng tốc.\n\n> \"Chọn BMW 330i khi bạn tìm kiếm niềm vui cầm lái thuần khiết mỗi sáng; chọn BMW 530i khi bạn cần một không gian thư thái, uy nghi để cân bằng giữa công việc và gia đình.\"\n\n## Trải nghiệm thực tế để đưa ra quyết định chính xác\n\nMọi thông số trên giấy tờ đều chỉ là bước khởi đầu. Cách tốt nhất để cảm nhận sự khác biệt giữa hai mẫu sedan này là trực tiếp cầm lái trên cùng một cung đường thử. Quý khách có thể sử dụng công cụ [đối chiếu thông số trực tiếp](/compare), [đặt lịch lái thử cả hai mẫu xe](/booking?type=test_drive) hoặc [nhận bảng tính chi phí lăn bánh chi tiết](/booking?type=quote) từ chuyên viên tư vấn showroom.",
                'published_at' => now()->subDays(3)->subHours(2),
            ],
            [
                'title' => 'BMW X3 M50 xDrive Thế Hệ Mới: Chuẩn Mực SAV Hiệu Năng Cao Cho Mọi Hành Trình',
                'slug' => 'bmw-x3-m50-xdrive-the-he-moi-chuan-muc-sav-hieu-nang-cao',
                'category' => Article::CATEGORY_SHOWROOM_NEWS,
                'cover_image' => 'images/cars/bmw-x3-m50-xdrive/hero-front-three-quarter.png',
                'excerpt' => 'Sở hữu diện mạo cơ bắp thế hệ mới, kích thước rộng rãi chuẩn gia đình hiện đại cùng khả năng tăng tốc 0–60 mph chỉ trong 4,4 giây, BMW X3 M50 xDrive định nghĩa lại sự đa dụng của một chiếc SAV hạng sang.',
                'seo_title' => 'BMW X3 M50 xDrive Thế Hệ Mới | Thiết kế, thông số & đặt lịch tư vấn',
                'seo_description' => 'Tìm hiểu chi tiết BMW X3 M50 xDrive thế hệ mới với thông số tăng tốc 4.4s, kích thước rộng rãi và công nghệ bảo dưỡng CBS tại BMW Showroom.',
                'body' => "## Diện mạo SAV thế hệ mới: Vững chãi và đầy uy lực\n\nTrong phân khúc xe gầm cao hạng sang cỡ trung, BMW X3 luôn giữ vị thế biểu tượng của sự linh hoạt và bền bỉ. Phiên bản hiệu năng cao **BMW X3 M50 xDrive** nâng tầm thiết kế đó với ngôn ngữ tạo hình tối giản, bề mặt thân xe liền lạc kết hợp cùng những đường cắt dứt khoát đậm chất M Performance.\n\n![BMW X3 M50 xDrive nổi bật với tỷ lệ thiết kế cơ bắp và hiện đại](/images/cars/bmw-x3-m50-xdrive/side-profile.png)\n\nVới kích thước tổng thể **4.755 x 1.920 x 1.660 mm**, chiếc SAV mang lại tầm nhìn thoáng đãng cho người lái, khoảng sáng gầm tự tin trước mọi điều kiện giao thông đô thị lẫn những chuyến dã ngoại cuối tuần cùng gia đình.\n\n## Hiệu năng vận hành bứt phá trên từng cung đường\n\nẨn sau vẻ ngoài lịch lãm của một chiếc xe gia đình là trái tim hiệu năng cao đáng nể:\n\n- **Khả năng bứt tốc ấn tượng**: BMW X3 M50 xDrive đạt khả năng tăng tốc **0–60 mph chỉ trong 4,4 giây**, mang lại cảm giác phấn khích ngang ngửa nhiều mẫu sedan thể thao thuần túy.\n- **Hệ dẫn động bốn bánh toàn thời gian xDrive**: Tự động phân bổ mô-men xoắn linh hoạt giữa cầu trước và cầu sau, đảm bảo độ bám đường tối ưu ngay cả khi mặt đường trơn trượt hoặc vào cua gấp.\n- **Khoang hành lý linh hoạt**: Hàng ghế sau có thể gập phẳng theo tỷ lệ thông minh, dễ dàng mở rộng không gian chứa đồ cho những bộ gậy golf hoặc hành lý cho kỳ nghỉ dài ngày.\n\n> \"BMW X3 M50 xDrive là câu trả lời trọn vẹn cho những chủ nhân không muốn phải lựa chọn giữa sự tiện nghi cho gia đình và cảm xúc phấn khích sau tay lái.\"\n\n## An tâm đồng hành với dịch vụ bảo dưỡng thông minh CBS\n\nMỗi chiếc BMW bàn giao từ showroom đều được tích hợp hệ thống theo dõi bảo dưỡng thông minh **Condition Based Servicing (CBS)**, tự động phân tích tình trạng dầu động cơ, má phanh và các hạng mục vận hành theo thói quen lái thực tế để nhắc lịch chăm sóc chính xác.\n\nQuý khách quan tâm có thể [xem thông số chi tiết BMW X3 M50 xDrive](/catalog/bmw-x3-m50-xdrive) hoặc [đặt lịch tư vấn trực tiếp tại showroom](/booking?type=consult) để được hỗ trợ cấu hình phiên bản phù hợp nhất.",
                'published_at' => now()->subDays(3)->subHours(3),
            ],
        ];

        foreach ($articles as $article) {
            Article::updateOrCreate(
                ['slug' => $article['slug'] ?? Str::slug($article['title'])],
                [
                    'user_id' => $admin?->id,
                    'title' => $article['title'],
                    'category' => $article['category'],
                    'excerpt' => $article['excerpt'],
                    'body' => $article['body'],
                    'cover_image' => $article['cover_image'],
                    'status' => Article::STATUS_PUBLISHED,
                    'published_at' => $article['published_at'],
                    'seo_title' => $article['seo_title'] ?? $article['title'],
                    'seo_description' => $article['seo_description'] ?? $article['excerpt'],
                ],
            );
        }
    }
}
