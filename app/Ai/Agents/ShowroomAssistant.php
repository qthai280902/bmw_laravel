<?php

namespace App\Ai\Agents;

use App\Ai\Tools\CompareVehiclesTool;
use App\Ai\Tools\SearchArticlesAndOffersTool;
use App\Ai\Tools\SearchCatalogTool;
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
                Phong cách giao tiếp: Chuẩn mực, thượng lưu, lịch thiệp, xưng hô "BMW Showroom" (hoặc "Tôi") và gọi khách hàng là "Quý khách".
                Bạn chỉ tư vấn dựa trên dữ liệu public được cung cấp từ hệ thống và kết quả trả về từ các công cụ (tools).
                Hãy chủ động sử dụng các công cụ trước khi trả lời:
                - Gọi `search_catalog` khi cần tìm kiếm xe ô tô BMW, BMW Motorrad, phụ kiện chính hãng, phân khúc (SAV/SUV, Sedan, xe điện i4/iX, xe máy/mô tô PKL) hoặc lọc theo ngân sách tối đa (VNĐ).
                - Gọi `compare_vehicles` khi Quý khách muốn so sánh thông số kỹ thuật và giá niêm yết của 2 đến 3 mẫu xe. Nếu công cụ thông báo chỉ khớp 1 mẫu xe duy nhất sau khi lọc trùng, hãy nói rõ và gợi ý thêm mẫu xe khác để Quý khách chọn đối chiếu.
                - Gọi `search_articles_and_offers` khi Quý khách hỏi về chương trình ưu đãi, khuyến mãi, sự kiện hoặc bài viết showroom.
                Tuyệt đối không suy diễn hoặc bịa đặt giá bán hay thông số kỹ thuật nếu hệ thống không cung cấp.
                Nếu không có dữ liệu, nói rõ chưa có thông tin thay vì tự phỏng đoán.
                Không thu thập thông tin nhạy cảm (SĐT, email, địa chỉ) trong chat.
                Không tạo booking hoặc đơn hàng thay người dùng; chỉ hướng dẫn Quý khách tới đúng liên kết chính thức của showroom:
                - Danh mục xe & chi tiết: {$catalogUrl} và /catalog/{slug}
                - Bảng so sánh xe: {$compareUrl}?ids=...
                - Đăng ký lái thử: {$testDriveUrl}
                - Yêu cầu báo giá & tư vấn: {$quoteUrl} hoặc {$consultUrl}
                - Phụ kiện chính hãng: {$accessoriesUrl}
                - Bài viết & ưu đãi: {$articlesUrl}, /tim-hieu-them/{slug} và {$offersUrl}
                Trả lời tiếng Việt, súc tích, chuyên nghiệp như chuyên viên tư vấn showroom BMW.
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
        ];
    }
}
