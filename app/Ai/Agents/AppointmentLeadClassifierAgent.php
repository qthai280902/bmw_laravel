<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

#[MaxTokens(300)]
#[Temperature(0.1)]
class AppointmentLeadClassifierAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
                Bạn là Chuyên gia Chấm điểm Lead CRM cho BMW Showroom Vietnam.
                Phân tích loại lịch hẹn, dòng xe quan tâm, ngày hẹn và ghi chú của khách để đánh giá mức độ ưu tiên:
                - `lead_priority`: `'hot'` (muốn mua ngay, đặt cọc, hỏi giá lăn bánh gấp, đặt lịch trong 1–3 ngày kèm nhu cầu cụ thể), `'warm'` (lái thử, xem xe, trade-in, báo giá có kế hoạch), `'normal'` (tham khảo chung hoặc chăm sóc/rửa xe định kỳ).
                - `lead_intent`: `'ready_to_buy' | 'test_drive_experience' | 'price_comparison' | 'aftersales_service' | 'general_inquiry'`.
                - `urgent`: `true` nếu có dấu hiệu cần liên hệ gấp.
                - `readiness_score`: điểm sẵn sàng từ 1 đến 100.
                - `ai_lead_note`: 1 dòng tóm tắt ngắn gọn tiếng Việt (`<= 200` ký tự) gợi ý cách tư vấn viên tiếp cận.
                PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'lead_priority' => $schema->string()
                ->enum(['hot', 'warm', 'normal'])
                ->required(),
            'lead_intent' => $schema->string()
                ->enum(['ready_to_buy', 'test_drive_experience', 'price_comparison', 'aftersales_service', 'general_inquiry'])
                ->required(),
            'urgent' => $schema->boolean()->required(),
            'readiness_score' => $schema->integer()->min(1)->max(100)->required(),
            'ai_lead_note' => $schema->string()->required(),
        ];
    }
}
