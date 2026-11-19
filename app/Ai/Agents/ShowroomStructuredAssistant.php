<?php

namespace App\Ai\Agents;

use App\Ai\Tools\CalculateFinancialEstimateTool;
use App\Ai\Tools\CheckAccessoryStockTool;
use App\Ai\Tools\CompareVehiclesTool;
use App\Ai\Tools\ListServicePackagesTool;
use App\Ai\Tools\SearchArticlesAndOffersTool;
use App\Ai\Tools\SearchCatalogTool;
use App\Ai\Tools\SearchKnowledgeBaseTool;
use App\Services\Ai\StructuredOutputParser;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

#[MaxSteps(6)]
#[MaxTokens(1200)]
#[Temperature(0.25)]
class ShowroomStructuredAssistant implements Agent, Conversational, HasStructuredOutput, HasTools
{
    use Promptable;

    /**
     * @param  array<int, Message>  $conversationMessages
     */
    public function __construct(
        private readonly array $conversationMessages = [],
        private readonly ?string $pageContextSummary = null,
    ) {}

    public function instructions(): Stringable|string
    {
        return app(ShowroomAssistant::class, [
            'conversationMessages' => $this->conversationMessages,
            'pageContextSummary' => $this->pageContextSummary,
        ])->instructions()
            ."\n\nBắt buộc trả về đúng JSON Schema có cấu trúc: reply_markdown, intent, lead_level, recommended_product_ids (chỉ ID tồn tại trong ngữ cảnh/DB), recommended_service_package (khớp chính xác 1 trong 4 gói dịch vụ nếu intent=service_booking), và prefill_booking.";
    }

    /**
     * @return Message[]
     */
    public function messages(): iterable
    {
        return $this->conversationMessages;
    }

    /**
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            app(SearchCatalogTool::class),
            app(CompareVehiclesTool::class),
            app(SearchArticlesAndOffersTool::class),
            app(SearchKnowledgeBaseTool::class),
            app(CalculateFinancialEstimateTool::class),
            app(CheckAccessoryStockTool::class),
            app(ListServicePackagesTool::class),
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'reply_markdown' => $schema->string()
                ->description('Nội dung tư vấn chi tiết bằng tiếng Việt chuẩn mực BMW Concierge (Markdown).')
                ->required(),
            'intent' => $schema->string()
                ->enum(StructuredOutputParser::VALID_INTENTS)
                ->required(),
            'lead_level' => $schema->string()
                ->enum(StructuredOutputParser::VALID_LEAD_LEVELS)
                ->required(),
            'recommended_product_ids' => $schema->array()
                ->items($schema->integer())
                ->required(),
            'recommended_service_package' => $schema->string()
                ->nullable(),
            'prefill_booking' => $schema->object([
                'type' => $schema->string()->enum(StructuredOutputParser::VALID_BOOKING_TYPES)->required(),
                'product_id' => $schema->integer()->nullable(),
                'service_package' => $schema->string()->nullable(),
                'summary' => $schema->string()->nullable(),
            ])->nullable(),
        ];
    }
}
