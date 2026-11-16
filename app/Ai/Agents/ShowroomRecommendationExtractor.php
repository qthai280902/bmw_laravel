<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

#[MaxTokens(350)]
#[Temperature(0.1)]
class ShowroomRecommendationExtractor implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
                Bạn là bộ trích xuất thực thể có cấu trúc cho BMW Showroom Vietnam.
                Dựa trên câu hỏi của khách hàng, câu trả lời của tư vấn viên AI và danh sách slug hợp lệ trong ngữ cảnh, hãy chọn ra tối đa 3 slug sản phẩm phù hợp nhất (`recommended_product_slugs`), tối đa 2 slug bài viết phù hợp nhất (`recommended_article_slugs`), và 1 hành động điều hướng chính (`primary_cta`).
                Chỉ chọn đúng các slug xuất hiện trong danh sách hợp lệ; nếu không chắc chắn, trả mảng rỗng.
                PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'recommended_product_slugs' => $schema->array()
                ->items($schema->string())
                ->required(),
            'recommended_article_slugs' => $schema->array()
                ->items($schema->string())
                ->required(),
            'primary_cta' => $schema->string()
                ->enum(['test_drive', 'quote', 'consult', 'compare', 'accessories', 'none'])
                ->required(),
        ];
    }
}
