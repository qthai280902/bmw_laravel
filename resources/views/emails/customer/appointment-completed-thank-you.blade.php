@extends('emails.layouts.bmw-concierge')

@php
    $branch = $branchCategory ?? ($appointment->isServiceBooking() ? 'service' : ($appointment->type === \App\Enums\AppointmentType::TradeIn ? 'trade_in' : 'sales'));
    $isServiceMode = $branch === 'service';
    $isTradeInMode = $branch === 'trade_in';
    $purchasedMode = $branch === 'sales' && (bool) ($isPurchased ?? (($outcome ?? 'visited_no_purchase') === 'purchased'));

    if (! isset($advisorFormatted) || ! isset($advisorName)) {
        if (method_exists(\App\Mail\Customer\AppointmentConfirmationMail::class, 'resolveAdvisorDisplay')) {
            $advisorDisplay = \App\Mail\Customer\AppointmentConfirmationMail::resolveAdvisorDisplay($appointment);
            $advisorName = $advisorDisplay['name'];
            $advisorFormatted = $advisorDisplay['formatted'];
        } else {
            $advisorName = 'Đội ngũ Chuyên viên BMW Concierge';
            $advisorFormatted = 'Đội ngũ Chuyên viên BMW Concierge (BMW Showroom Vietnam)';
        }
    }

    $car = $appointment->product;
    $meta = \App\Services\Crm\InteractionTimelineRecorder::normalizeMetaArray($appointment->meta_data);
    $customerCarModel = $meta['customer_car_model'] ?? null;
    $servicePackageName = $appointment->servicePackageLabel();
    $resolvedServiceNote = $serviceCompletionNote ?? ($meta['service_completion_note'] ?? null);
    $resolvedServiceCost = $serviceActualCost ?? (isset($meta['service_actual_cost']) && $meta['service_actual_cost'] !== null ? (int) $meta['service_actual_cost'] : null);
    $resolvedNextReminder = $serviceNextReminderLabel ?? ($meta['service_next_reminder_label'] ?? null);
    $resolvedTradeInNote = $tradeInNote ?? ($meta['trade_in_note'] ?? null);
    $customerOrderCode = $appointment->publicReferenceCode();

    $emailTitle = match (true) {
        $isServiceMode => 'Hoàn Tất Bảo Dưỡng & Chăm Sóc Xe | Xưởng Dịch Vụ BMW Chính Hãng',
        $isTradeInMode => 'Tổng Kết Thẩm Định Xe | BMW Certified Trade-In',
        $purchasedMode => 'Chúc Mừng Sở Hữu Xe BMW & Đặc Quyền Chăm Sóc 1 Tháng',
        default => 'Thư Cảm Ơn Quý Khách Đã Tham Quan & Trải Nghiệm Tại BMW Showroom',
    };
    $accentHex = '#1C69D4';
    $headerSubtitle = match (true) {
        $isServiceMode => 'BMW SERVICE EXCELLENCE · HOÀN TẤT BÀN GIAO',
        $isTradeInMode => 'BMW CERTIFIED TRADE-IN · THẨM ĐỊNH CHÍNH HÃNG',
        $purchasedMode => 'CHÚC MỪNG CHỦ NHÂN BMW MỚI',
        default => 'THƯ CẢM ƠN TỪ BMW CONCIERGE',
    };
@endphp

@section('title', $emailTitle)
@section('accent_color', $accentHex)
@section('header_subtitle', $headerSubtitle)

@section('badge')
    @if($isServiceMode)
        <span style="display: inline-block; padding: 6px 14px; background-color: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 9999px; color: #1C69D4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 700;">
            Hoàn tất dịch vụ &bull; Đã bàn giao
        </span>
    @elseif($isTradeInMode)
        <span style="display: inline-block; padding: 6px 14px; background-color: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 9999px; color: #1C69D4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 700;">
            Đã hoàn tất thẩm định
        </span>
    @elseif($purchasedMode)
        <span style="display: inline-block; padding: 6px 14px; background-color: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 9999px; color: #1C69D4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 700;">
            Đã ký hợp đồng &bull; Đặc quyền chủ xe
        </span>
    @else
        <span style="display: inline-block; padding: 6px 14px; background-color: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 9999px; color: #1C69D4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 700;">
            Tri ân khách tham quan
        </span>
    @endif
@endsection

@section('content')
    @if($isServiceMode)
        <h1 style="margin: 0 0 12px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.35;">
            Cảm ơn Quý khách đã tin tưởng Xưởng Dịch vụ Chính hãng BMW
        </h1>
        <p style="margin: 0 0 22px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; color: #475569; line-height: 1.6;">
            Kính chào Quý khách <strong style="color: #0F172A;">{{ $appointment->customer_name }}</strong>, đội ngũ Kỹ thuật viên chứng chỉ BMW toàn cầu cùng Cố vấn dịch vụ <strong style="color: #1C69D4;">{{ $advisorFormatted }}</strong> trân trọng thông báo quy trình chăm sóc / bảo dưỡng cho phương tiện <strong style="color: #0F172A;">{{ $customerCarModel ?: 'BMW của Quý khách' }}</strong> đã hoàn tất theo tiêu chuẩn kỹ thuật chính hãng.
        </p>

        {{-- BMW Dark Luxury Service Completion Card --}}
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 24px 0; background-color: #0B1220; border: 1px solid #1E293B; border-radius: 14px; overflow: hidden;">
            <tr>
                <td style="height: 4px; background-color: #1C69D4; font-size: 0; line-height: 0;">&nbsp;</td>
            </tr>
            <tr>
                <td style="padding: 20px 22px; background-color: #0B1220; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                    <div style="font-size: 10px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: #70A7FF; margin-bottom: 8px;">
                        BIÊN BẢN TÓM TẮT DỊCH VỤ &bull; {{ $outcome === 'service_inspected_advised' ? 'ĐÃ KIỂM TRA & TƯ VẤN KỸ THUẬT' : 'ĐÃ HOÀN TẤT & BÀN GIAO XE' }}
                    </div>
                    <div style="font-size: 18px; font-weight: 700; color: #F8FAFC; line-height: 1.35; margin-bottom: 10px;">
                        {{ $servicePackageName ?: $appointment->typeLabel() }}
                    </div>
                    @if(filled($resolvedServiceNote))
                        <div style="padding: 12px 14px; background-color: #131D31; border: 1px solid #2A3B59; border-radius: 8px; font-size: 13px; color: #E2E8F0; line-height: 1.55; margin-bottom: 12px;">
                            <strong style="color: #70A7FF;">Ghi chú kỹ thuật / Hạng mục thực hiện:</strong><br>
                            {{ $resolvedServiceNote }}
                        </div>
                    @endif
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                        @if($resolvedServiceCost !== null && $resolvedServiceCost > 0)
                            <tr>
                                <td style="padding: 4px 0; font-size: 13px; color: #94A3B8; width: 44%;">Chi phí dịch vụ quyết toán</td>
                                <td style="padding: 4px 0; font-size: 14px; font-weight: 700; color: #70A7FF; width: 56%;">{{ number_format($resolvedServiceCost, 0, ',', '.') }} VNĐ</td>
                            </tr>
                        @endif
                        @if(filled($resolvedNextReminder))
                            <tr>
                                <td style="padding: 4px 0; font-size: 13px; color: #94A3B8; width: 44%;">Khuyến nghị kỳ bảo dưỡng tới</td>
                                <td style="padding: 4px 0; font-size: 13px; font-weight: 600; color: #F8FAFC; width: 56%;">{{ $resolvedNextReminder }}</td>
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>
    @elseif($isTradeInMode)
        <h1 style="margin: 0 0 12px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.35;">
            Trân trọng cảm ơn Quý khách đã sử dụng dịch vụ Thẩm định BMW Certified Trade-In
        </h1>
        <p style="margin: 0 0 22px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; color: #475569; line-height: 1.6;">
            Kính chào Quý khách <strong style="color: #0F172A;">{{ $appointment->customer_name }}</strong>, chuyên viên thẩm định <strong style="color: #1C69D4;">{{ $advisorFormatted }}</strong> đã hoàn tất quy trình kiểm tra kỹ thuật &amp; định giá minh bạch cho phương tiện của Quý khách ({{ $outcome === 'trade_in_closed' ? 'Đã thống nhất phương án thu mua' : 'Hồ sơ định giá đang được bảo lưu để Quý khách cân nhắc' }}).
        </p>
        @if(filled($resolvedTradeInNote))
            <div style="padding: 14px 18px; background-color: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 12px; margin-bottom: 22px; font-size: 14px; color: #1E3A8A; line-height: 1.55;">
                <strong>Ghi chú thẩm định từ Chuyên viên:</strong> {{ $resolvedTradeInNote }}
            </div>
        @endif
    @elseif($purchasedMode)
        <h1 style="margin: 0 0 12px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.35;">
            Chúc mừng Quý khách chính thức gia nhập cộng đồng chủ nhân BMW
        </h1>
        <p style="margin: 0 0 22px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; color: #475569; line-height: 1.6;">
            Kính chào Quý khách <strong style="color: #0F172A;">{{ $appointment->customer_name }}</strong>, BMW Showroom Vietnam cùng chuyên viên <strong style="color: #1C69D4;">{{ $advisorFormatted }}</strong> trân trọng cảm ơn Quý khách đã tin tưởng lựa chọn <strong style="color: #0F172A;">{{ $car?->name ?? 'dòng xe BMW chính hãng' }}</strong> trong buổi đón tiếp vừa qua.
        </p>

        {{-- Khối Quà Tặng Đặc Quyền Rửa Xe & Chăm Sóc Xe 1 Tháng --}}
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #0B1220; border: 1px solid #1E293B; border-radius: 12px; margin-bottom: 24px; overflow: hidden;">
            <tr>
                <td style="height: 3px; background-color: #1C69D4; font-size: 0; line-height: 0;">&nbsp;</td>
            </tr>
            <tr>
                <td style="padding: 20px 22px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                    <div style="font-size: 11px; font-weight: 700; color: #70A7FF; letter-spacing: 0.8px; text-transform: uppercase; margin-bottom: 8px;">
                        ĐẶC QUYỀN CHỦ XE MỚI &bull; GÓI CHĂM SÓC &amp; RỬA XE MIỄN PHÍ 01 THÁNG
                    </div>
                    <p style="margin: 0 0 14px 0; font-size: 14px; color: #CBD5E1; line-height: 1.55;">
                        Nhằm tri ân quyết định sở hữu xe của Quý khách, BMW Showroom gửi tặng đặc quyền <strong style="color: #FFFFFF;">Rửa xe tiêu chuẩn Detailing &amp; Chăm sóc bề mặt sơn/nội thất miễn phí trong 01 tháng đầu tiên</strong> tại xưởng dịch vụ chính hãng:
                    </p>
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #FFFFFF; border: 1px dashed #1C69D4; border-radius: 8px;">
                        <tr>
                            <td style="padding: 14px 16px;">
                                <div style="font-size: 11px; font-weight: 600; color: #64748B; text-transform: uppercase;">Mã đặc quyền BMW Care</div>
                                <div style="font-size: 20px; font-weight: 800; color: #1C69D4; letter-spacing: 1px; margin-top: 4px;">{{ $voucherCode }}</div>
                                <div style="font-size: 12px; font-weight: 600; color: #0F172A; margin-top: 6px;">
                                    Hiệu lực sử dụng đến hết ngày: <span style="color: #1C69D4;">{{ $voucherExpiresLabel }}</span> (01 tháng)
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    @else
        <h1 style="margin: 0 0 12px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.35;">
            Trân trọng cảm ơn Quý khách đã dành thời gian ghé thăm BMW Showroom
        </h1>
        <p style="margin: 0 0 22px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; color: #475569; line-height: 1.6;">
            Kính chào Quý khách <strong style="color: #0F172A;">{{ $appointment->customer_name }}</strong>, đội ngũ BMW Showroom Vietnam cùng chuyên viên <strong style="color: #1C69D4;">{{ $advisorFormatted }}</strong> chân thành cảm ơn Quý khách đã đến tham quan và trực tiếp cảm nhận <strong style="color: #0F172A;">{{ $car?->name ?? 'các dòng xe BMW chính hãng' }}</strong>.
        </p>
        <p style="margin: 0 0 22px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; color: #475569; line-height: 1.6;">
            Chúng tôi hy vọng buổi đón tiếp đã mang lại những góc nhìn trọn vẹn về thiết kế, cảm giác lái và công nghệ của BMW. Khi Quý khách cần thêm bất kỳ thông tin cấu hình hay phương án tài chính nào trong tương lai, chuyên viên phụ trách luôn sẵn sàng hỗ trợ theo lịch trình thuận tiện nhất của Quý khách.
        </p>
    @endif

    {{-- Thông tin hồ sơ --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B; width: 38%;">
                Mã tham chiếu
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 700; color: {{ $accentHex }}; width: 62%;">
                {{ $customerOrderCode }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                {{ $isServiceMode ? 'Hạng mục dịch vụ' : 'Hạng mục đón tiếp' }}
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 600; color: #0F172A;">
                {{ $servicePackageName ?: $appointment->typeLabel() }}
                @if(! $isServiceMode && $car)
                    &mdash; {{ $car->name }}
                @elseif($isServiceMode && filled($customerCarModel))
                    &mdash; {{ $customerCarModel }}
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                {{ $isServiceMode ? 'Cố vấn dịch vụ phụ trách' : 'Chuyên viên phụ trách' }}
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 600; color: #0F172A;">
                {{ $advisorFormatted }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; font-size: 13px; font-weight: 500; color: #64748B;">
                {{ $isServiceMode ? 'Địa điểm Xưởng dịch vụ' : 'Địa điểm Showroom' }}
            </td>
            <td style="padding: 13px 18px; font-size: 14px; font-weight: 600; color: #0F172A;">
                {{ $isServiceMode ? 'Xưởng Dịch Vụ BMW Chính Hãng — 123 Nguyễn Văn Linh, Q.7, TP.HCM' : 'BMW Showroom Flagship — 123 Nguyễn Văn Linh, Q.7, TP.HCM' }}
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="left">
                <a href="{{ $isServiceMode ? route('appointments.create', ['type' => 'maintenance']) : route('products.index') }}"
                   style="display: inline-block; background-color: {{ $accentHex }}; color: #FFFFFF; text-decoration: none; padding: 13px 26px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 13px; font-weight: 700; letter-spacing: 0.4px; border-radius: 10px;">
                    {{ $isServiceMode ? 'ĐẶT LỊCH BẢO DƯỠNG KỲ TIẾP THEO' : 'KHÁM PHÁ SHOWROOM TRỰC TUYẾN' }}
                </a>
            </td>
        </tr>
    </table>
@endsection
