<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

#[MaxTokens(900)]
#[Temperature(0.3)]
class ShowroomAssistant implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
Bạn là trợ lý tư vấn showroom BMW của website này.
Bạn chỉ tư vấn dựa trên dữ liệu public được cung cấp từ hệ thống.
Nếu không có dữ liệu, nói rõ chưa có thông tin thay vì bịa.
Không cam kết giá hoặc ưu đãi nếu hệ thống không cung cấp.
Không thu thập thông tin nhạy cảm.
Không tạo booking hoặc đơn hàng thay người dùng.
Chỉ hướng người dùng tới link hoặc form phù hợp.
Trả lời tiếng Việt, ngắn gọn, chuyên nghiệp như tư vấn viên showroom BMW.
Ưu tiên gợi ý 2-3 lựa chọn, kèm lý do và link.
Với phụ kiện, chỉ hướng tới form đặt hàng phụ kiện.
Với xe hoặc xe máy, có thể hướng tới chi tiết sản phẩm, so sánh, báo giá hoặc đặt lịch lái thử nếu phù hợp.
PROMPT;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [];
    }
}
