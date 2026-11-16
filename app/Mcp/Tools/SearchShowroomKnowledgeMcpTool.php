<?php

namespace App\Mcp\Tools;

use App\Services\Ai\ShowroomKnowledgeBaseService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Tra cứu tri thức chính thống của BMW Showroom Vietnam (bảo hành, bảo dưỡng định kỳ CBS, pin xe điện, trả góp, thu cũ đổi mới, phụ kiện và bài viết/ưu đãi) bằng Hybrid RAG.')]
class SearchShowroomKnowledgeMcpTool extends Tool
{
    public function __construct(
        private ShowroomKnowledgeBaseService $knowledgeBase,
    ) {}

    public function handle(Request $request): Response
    {
        $query = trim((string) ($request->get('query') ?? ''));

        if ($query === '') {
            return Response::error('Vui lòng cung cấp câu hỏi hoặc từ khóa chính sách cần tra cứu.');
        }

        $limit = max(1, min(6, (int) ($request->get('limit') ?? 4)));
        $chunks = $this->knowledgeBase->retrieveRelevantChunks($query, $limit);

        $payload = [
            'query' => $query,
            'count' => count($chunks),
            'chunks' => $chunks,
        ];

        return Response::text(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Câu hỏi hoặc chủ đề cần tra cứu (ví dụ: chính sách bảo hành pin xe điện BMW, thu cũ đổi mới).')
                ->required(),
            'limit' => $schema->integer()
                ->description('Số lượng đoạn tri thức tối đa trả về (mặc định 4, tối đa 6).')
                ->nullable(),
        ];
    }
}
