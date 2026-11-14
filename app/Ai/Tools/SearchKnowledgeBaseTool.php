<?php

namespace App\Ai\Tools;

use App\Services\Ai\ShowroomKnowledgeBaseService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchKnowledgeBaseTool implements Tool
{
    public function __construct(private readonly ShowroomKnowledgeBaseService $knowledgeBase) {}

    public function name(): string
    {
        return 'search_knowledge_base';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Tra cứu cơ sở tri thức RAG của showroom BMW về chính sách bảo hành, bảo dưỡng CBS, quy trình đăng ký lái thử, đặt cọc/giá lăn bánh/trả góp, chương trình thu cũ đổi mới (Trade-in), hướng dẫn BMW Motorrad và dữ liệu showroom liên quan.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $query = trim((string) ($request['query'] ?? ''));

        if ($query === '') {
            return json_encode([
                'status' => 'no_match',
                'total_found' => 0,
                'chunks' => [],
                'consult_url' => route('appointments.create', ['type' => 'consult'], false),
                'message' => 'Vui lòng cung cấp câu hỏi hoặc từ khóa cụ thể để tra cứu tri thức showroom.',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
        }

        $chunks = $this->knowledgeBase->retrieveRelevantChunks($query, 4, true);

        return json_encode([
            'status' => count($chunks) > 0 ? 'ok' : 'no_match',
            'total_found' => count($chunks),
            'chunks' => $chunks,
            'consult_url' => route('appointments.create', ['type' => 'consult'], false),
            'message' => count($chunks) > 0
                ? null
                : 'Chưa có thông tin cụ thể cho nội dung này trong tài liệu showroom. Vui lòng hướng dẫn Quý khách đặt lịch tư vấn trực tiếp với chuyên viên.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Câu hỏi hoặc chủ đề cần tra cứu (ví dụ: chính sách bảo hành pin xe điện, trả góp, thu cũ đổi mới, quy trình lái thử, bảo dưỡng CBS).')
                ->required(),
        ];
    }
}
