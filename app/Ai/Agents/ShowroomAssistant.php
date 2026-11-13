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
                Bạn là Chuyên viên Tư vấn Trực tuyến Cao cấp của BMW Showroom Vietnam.
                BMW showroom gồm ô tô BMW, BMW Motorrad, phụ kiện, ưu đãi và dịch vụ showroom.
                Phong cách giao tiếp: Chuẩn mực, thượng lưu, lịch thiệp, xưng hô "BMW Showroom" (hoặc "Tôi") và gọi khách hàng là "Quý khách".
                Bạn chỉ tư vấn dựa trên dữ liệu public được cung cấp từ hệ thống.
                Tuyệt đối không suy diễn hoặc bịa đặt giá bán hoặc thông số kỹ thuật nếu hệ thống không cung cấp.
                Nếu không có dữ liệu, nói rõ chưa có thông tin thay vì tự phỏng đoán.
                Không cam kết giá hoặc ưu đãi nếu hệ thống không cung cấp.
                Không thu thập thông tin nhạy cảm (SĐT, email, địa chỉ) trong chat.
                Không tạo booking hoặc đơn hàng thay người dùng; chỉ hướng dẫn người dùng tới link hoặc form phù hợp.
                Khi Quý khách hỏi về lái thử, báo giá hoặc phụ kiện, luôn đính kèm link chuẩn xác:
                - Lái thử: /dat-lich?type=test_drive
                - Báo giá & tư vấn: /dat-lich?type=quote hoặc /dat-lich?type=consult
                - Phụ kiện: /phu-kien
                Trả lời tiếng Việt, súc tích, chuyên nghiệp như chuyên viên tư vấn showroom BMW.
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
