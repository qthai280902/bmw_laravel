<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'code' => 'installment-due-reminder',
                'name' => 'Nhắc lịch thanh toán trả góp định kỳ',
                'category' => EmailTemplate::CATEGORY_INSTALLMENT,
                'subject' => '[BMW Financial] Nhắc lịch thanh toán kỳ trả góp {{vehicle_name}} ngày {{next_due_date}}',
                'eyebrow' => 'BMW Financial Services · Nhắc lịch kỳ hạn',
                'heading' => 'Nhắc lịch thanh toán kỳ trả góp định kỳ',
                'body_content' => "Kính chào Quý khách {{customer_name}},\n\nBMW Showroom Vietnam xin trân trọng nhắc Quý khách về kỳ thanh toán hợp đồng tài chính cho mẫu xe {{vehicle_name}} tại {{bank_name}}.\n\nQuý khách vui lòng kiểm tra số dư tài khoản thanh toán trước ngày {{next_due_date}} để đảm bảo giao dịch trích nợ tự động diễn ra thuận lợi và duy trì lịch sử tín dụng ưu tiên.\n\nMọi hỗ trợ tra cứu lịch trả nợ hoặc chứng từ ngân hàng, Quý khách vui lòng phản hồi trực tiếp email này để Chuyên viên {{advisor_name}} đồng hành ngay lập tức.",
                'highlight_title' => 'Thông tin kỳ thanh toán sắp tới',
                'highlight_content' => "Ngân hàng đối tác: {{bank_name}}\nSố tiền dự kiến kỳ này: {{monthly_payment}}\nLãi suất áp dụng hiện tại: {{interest_rate}}\nNgày đến hạn thanh toán: {{next_due_date}}",
                'cta_label' => 'LIÊN HỆ CHUYÊN VIÊN TÀI CHÍNH',
                'cta_url' => url('/booking'),
                'accent_color' => '#1C69D4',
                'status' => EmailTemplate::STATUS_ACTIVE,
            ],
            [
                'code' => 'installment-interest-adjustment',
                'name' => 'Thông báo điều chỉnh chính sách lãi suất trả góp',
                'category' => EmailTemplate::CATEGORY_INSTALLMENT,
                'subject' => '[BMW Financial] Thông báo cập nhật lãi suất trả góp {{vehicle_name}} ({{interest_rate}})',
                'eyebrow' => 'BMW Financial Services · Chính sách lãi suất',
                'heading' => 'Thông báo điều chỉnh chính sách lãi suất vay mua xe BMW',
                'body_content' => "Kính chào Quý khách {{customer_name}},\n\nBMW Showroom Vietnam cùng đối tác tài chính {{bank_name}} xin gửi thông báo cập nhật chính sách lãi suất áp dụng cho hồ sơ trả góp mẫu xe {{vehicle_name}} của Quý khách.\n\nMức lãi suất mới được cập nhật là {{interest_rate}} với khoản thanh toán tham chiếu hàng tháng {{monthly_payment}} (kỳ gần nhất ngày {{next_due_date}}).\n\nNếu Quý khách có nhu cầu tái cấu trúc kỳ hạn hoặc kiểm tra phương án tất toán ưu đãi, Chuyên viên {{advisor_name}} luôn sẵn sàng hỗ trợ qua Hotline {{showroom_hotline}}.",
                'highlight_title' => 'Chi tiết cập nhật lãi suất & kỳ hạn',
                'highlight_content' => "Mẫu xe: {{vehicle_name}}\nNgân hàng: {{bank_name}}\nLãi suất áp dụng mới: {{interest_rate}}\nSố tiền dự kiến / tháng: {{monthly_payment}}",
                'cta_label' => 'ĐẶT LỊCH TƯ VẤN TÀI CHÍNH',
                'cta_url' => url('/booking'),
                'accent_color' => '#0284C7',
                'status' => EmailTemplate::STATUS_ACTIVE,
            ],
            [
                'code' => 'bmw-latest-loyalty-program',
                'name' => 'Chương trình ưu đãi & chăm sóc khách hàng BMW mới nhất',
                'category' => EmailTemplate::CATEGORY_PROMOTION,
                'subject' => '[BMW Showroom] Chương trình ưu đãi đặc quyền mới nhất dành riêng cho {{customer_name}}',
                'eyebrow' => 'BMW Loyalty & Privilege · Ưu đãi mới nhất',
                'heading' => 'Đặc quyền ưu đãi & chăm sóc khách hàng BMW mới nhất',
                'body_content' => "Kính chào Quý khách {{customer_name}},\n\nBMW Showroom Vietnam trân trọng giới thiệu chương trình ưu đãi mới nhất dành cho Quý khách hàng đang quan tâm và sở hữu {{vehicle_name}}.\n\nTrong tháng này, Showroom áp dụng gói hỗ trợ phí trước bạ linh hoạt, ưu đãi gói chăm sóc Detailing chính hãng và kiểm tra chẩn đoán điện tử ISTA miễn phí tại xưởng dịch vụ Flagship.\n\nQuý khách có thể phản hồi trực tiếp thư này hoặc liên hệ Chuyên viên {{advisor_name}} qua Hotline {{showroom_hotline}} để được giữ suất ưu đãi tốt nhất.",
                'highlight_title' => 'Quyền lợi nổi bật trong chương trình',
                'highlight_content' => "Ưu đãi tài chính BMW Financial với lãi suất cạnh tranh.\nTặng gói kiểm tra kỹ thuật toàn diện và chăm sóc xe tiêu chuẩn BMW Đức.\nƯu tiên lịch đón tiếp riêng tại BMW Lounge.",
                'cta_label' => 'KHÁM PHÁ CHƯƠNG TRÌNH ƯU ĐÃI',
                'cta_url' => url('/booking'),
                'accent_color' => '#059669',
                'status' => EmailTemplate::STATUS_ACTIVE,
            ],
        ];

        foreach ($templates as $attributes) {
            EmailTemplate::query()->firstOrCreate(
                ['code' => $attributes['code']],
                $attributes,
            );
        }
    }
}
