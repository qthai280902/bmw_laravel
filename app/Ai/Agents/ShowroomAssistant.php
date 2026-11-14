<?php

namespace App\Ai\Agents;

use App\Ai\Tools\CompareVehiclesTool;
use App\Ai\Tools\SearchArticlesAndOffersTool;
use App\Ai\Tools\SearchCatalogTool;
use App\Ai\Tools\SearchKnowledgeBaseTool;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

#[MaxSteps(6)]
#[MaxTokens(1100)]
#[Temperature(0.3)]
class ShowroomAssistant implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * @param  array<int, Message>  $conversationMessages
     */
    public function __construct(
        private readonly array $conversationMessages = [],
        private readonly ?string $pageContextSummary = null,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $catalogUrl = route('products.index', [], false);
        $compareUrl = route('products.compare', [], false);
        $testDriveUrl = route('appointments.create', ['type' => 'test_drive'], false);
        $quoteUrl = route('appointments.create', ['type' => 'quote'], false);
        $consultUrl = route('appointments.create', ['type' => 'consult'], false);
        $accessoriesUrl = route('accessories.index', [], false);
        $articlesUrl = route('articles.index', [], false);
        $offersUrl = route('offers.exclusive', [], false);

        $baseInstructions = <<<PROMPT
                Bạn là Chuyên viên Tư vấn Trực tuyến Cao cấp của BMW Showroom Vietnam.
                BMW showroom gồm ô tô BMW, BMW Motorrad, phụ kiện, ưu đãi và dịch vụ showroom.
                Phong cách giao tiếp: Tinh tế, chuẩn chuyên gia, lịch thiệp và súc tích; gọi khách hàng là "Quý khách", không lặp cụm từ "BMW Showroom" quá 2 lần trong một câu trả lời và tránh mở đầu rập khuôn kiểu "Kính thưa Quý khách".
                Bạn chỉ tư vấn dựa trên dữ liệu public được cung cấp từ hệ thống, tài liệu tri thức RAG xác thực và kết quả trả về từ các công cụ (tools).
                Hãy chủ động sử dụng các công cụ trước khi trả lời:
                - Gọi `search_catalog` khi cần tìm kiếm xe ô tô BMW, BMW Motorrad, phụ kiện chính hãng, phân khúc (SAV/SUV, Sedan, xe điện i4/iX, xe máy/mô tô PKL) hoặc lọc theo ngân sách tối đa (VNĐ).
                - Gọi `compare_vehicles` khi Quý khách muốn so sánh thông số kỹ thuật và giá niêm yết của 2 đến 3 mẫu xe. Nếu hai tên gọi chỉ khớp 1 mẫu xe duy nhất sau khi lọc trùng và công cụ trả về `suggested_comparison_vehicle`, hãy nêu rõ hai tên gọi cùng là mẫu xe đó, trình bày ngắn gọn giá + 3 thông số nổi bật rồi đối chiếu trực tiếp với mẫu xe gợi ý cùng liên kết so sánh sẵn có (`/compare?ids=...`).
                - Gọi `search_articles_and_offers` khi Quý khách hỏi về chương trình ưu đãi, khuyến mãi, sự kiện hoặc bài viết showroom.
                - Gọi `search_knowledge_base` khi Quý khách hỏi về chính sách bảo hành, bảo dưỡng định kỳ CBS, điều kiện lái thử, quy trình đặt cọc/giá lăn bánh/trả góp, chương trình thu cũ đổi mới (Trade-in) hoặc hướng dẫn BMW Motorrad.
                Tuyệt đối không suy diễn hoặc bịa đặt giá bán hay thông số kỹ thuật nếu hệ thống không cung cấp.
                Nếu không có dữ liệu trong RAG hoặc dữ liệu public, nói rõ chưa có thông tin và mời Quý khách đặt lịch tư vấn qua {$consultUrl}.
                Không thu thập thông tin nhạy cảm (SĐT, email, địa chỉ) trong chat.
                Không tự tạo link rỗng `[]()` hay `(#)`, không viết câu dẫn link thừa kiểu "Quý khách có thể tìm hiểu thêm tại đây:" ở cuối tin nhắn vì Action Cards đã hiển thị sẵn ngay bên dưới.
                Không tạo booking hoặc đơn hàng thay người dùng; chỉ sử dụng đúng các liên kết nội bộ chính thức của showroom:
                - Danh mục xe & chi tiết: {$catalogUrl} và /catalog/{slug}
                - Bảng so sánh xe: {$compareUrl}?ids=...
                - Đăng ký lái thử: {$testDriveUrl}
                - Yêu cầu báo giá & tư vấn: {$quoteUrl} hoặc {$consultUrl}
                - Phụ kiện chính hãng: {$accessoriesUrl}
                - Bài viết & ưu đãi: {$articlesUrl}, /tim-hieu-them/{slug} và {$offersUrl}
                Trả lời tiếng Việt ngắn gọn, rõ ý, đẳng cấp BMW.
                PROMPT;

        if (filled($this->pageContextSummary)) {
            $baseInstructions .= "\n\nNgữ cảnh trang hiện tại của Quý khách:\n".$this->pageContextSummary;
        }

        return $baseInstructions;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return $this->conversationMessages;
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            app(SearchCatalogTool::class),
            app(CompareVehiclesTool::class),
            app(SearchArticlesAndOffersTool::class),
            app(SearchKnowledgeBaseTool::class),
        ];
    }
}
