<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

#[MaxTokens(500)]
#[Temperature(0.25)]
class ArticleEditorialAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
                Bạn là Biên tập viên Nội dung Cao cấp của BMW Showroom Vietnam.
                Dựa trên tiêu đề, chuyên mục và nội dung bài viết được cung cấp, hãy tạo đúng cấu trúc:
                - `excerpt`: đoạn tóm tắt súc tích từ 2 đến 3 câu (khoảng 160–320 ký tự), văn phong sang trọng, chuẩn xác tiếng Việt có dấu.
                - `seo_title`: tiêu đề tối ưu SEO không vượt quá 60 ký tự, có từ khóa BMW tự nhiên.
                - `seo_description`: mô tả meta SEO hấp dẫn, rõ giá trị, không vượt quá 155 ký tự.
                PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'excerpt' => $schema->string()->required(),
            'seo_title' => $schema->string()->required(),
            'seo_description' => $schema->string()->required(),
        ];
    }
}
